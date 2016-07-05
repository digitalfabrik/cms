<?php

class AbsoluteLinks{
	public $custom_post_query_vars = array();
	public $taxonomies_query_vars = array();

	function __construct() {
		//init_query_vars is using $wp_taxonomies
		//we have to change priority of our action
		//to make sure that all custom taxonomies are already registered
		add_action( 'init', array( $this, 'init_query_vars' ), 1000 );
	}

	function init_query_vars() {
		global $wp_post_types, $wp_taxonomies;

		//custom posts query vars
		foreach ( $wp_post_types as $k => $v ) {
			if ( $k === 'post' || $k === 'page' ) {
				continue;
			}
			if ( $v->query_var ) {
				$this->custom_post_query_vars[ $k ] = $v->query_var;
			}
		}
		//taxonomies query vars
		foreach ( $wp_taxonomies as $k => $v ) {
			if ( $k === 'category' ) {
				continue;
			}
			if ( $k == 'post_tag' && !$v->query_var ) {
				$tag_base     = get_option( 'tag_base', 'tag' );
				$v->query_var = $tag_base;
			}
			if ( $v->query_var ) {
				$this->taxonomies_query_vars[ $k ] = $v->query_var;
			}
		}

	}

	function _process_generic_text( $source_text, &$alp_broken_links ) {
		global $wpdb, $wp_rewrite, $sitepress, $sitepress_settings;
		$sitepress_settings = $sitepress->get_settings();

		$default_language = $sitepress->get_default_language();
		$current_language = $sitepress->get_current_language();

		$cache_key_args = array( $default_language, $current_language, md5( $source_text ), md5( implode( '', $alp_broken_links ) ) );
		$cache_key      = md5( json_encode( $cache_key_args ) );
		$cache_group    = '_process_generic_text';
		$found          = false;

		$text = wp_cache_get( $cache_key, $cache_group, false, $found );

		if ( $found ) {
			return $text;
		}

		$filtered_icl_post_language = filter_input( INPUT_POST, 'icl_post_language', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		$text = $source_text;

		if ( !isset( $wp_rewrite ) ) {
			require_once ABSPATH . WPINC . '/rewrite.php';
			$wp_rewrite = new WP_Rewrite();
		}

		if ( $current_language == $default_language ) {
			$rewrite = $wp_rewrite->wp_rewrite_rules();
		} else {
			remove_filter( 'option_rewrite_rules', array( $sitepress, 'rewrite_rules_filter' ) );
			if(class_exists('WPML_Slug_Translation')) {
				remove_filter( 'option_rewrite_rules', array( 'WPML_Slug_Translation', 'rewrite_rules_filter' ), 1 );
			}

			$rewrite = $wp_rewrite->wp_rewrite_rules();

			if(class_exists('WPML_Slug_Translation')) {
				add_filter( 'option_rewrite_rules', array( 'WPML_Slug_Translation', 'rewrite_rules_filter' ), 1, 1 );
			}
		}

		$rewrite = $this->all_rewrite_rules( $rewrite );

		$home_url = $sitepress->language_url( empty( $filtered_icl_post_language ) ? false : $filtered_icl_post_language );

		if ( $sitepress_settings[ 'language_negotiation_type' ] == 3 ) {
			$home_url = preg_replace( "#\?lang=([a-z-]+)#i", '', $home_url );
		}
		$home_url = str_replace( "?", "\?", $home_url );

		if ( $sitepress_settings[ 'urls' ][ 'directory_for_default_language' ] ) {
			$default_language = $sitepress->get_default_language();

			$home_url = str_replace( $default_language . "/", "", $home_url );

		}

		$int1 = preg_match_all( '@<a([^>]*)href="((' . rtrim( $home_url, '/' ) . ')?/([^"^>^\[^\]]+))"([^>]*)>@i', $text, $alp_matches1 );
		$int2 = preg_match_all( '@<a([^>]*)href=\'((' . rtrim( $home_url, '/' ) . ')?/([^\'^>^\[^\]]+))\'([^>]*)>@i', $text, $alp_matches2 );

		$alp_matches = array();
		for ( $i = 0; $i < 6; $i++ ) {
			$alp_matches[ $i ] = array_merge( (array)$alp_matches1[ $i ], (array)$alp_matches2[ $i ] );
		}

		if ( $int1 || $int2 ) {
			$url_parts           = parse_url( $this->get_home_url_with_no_lang_directory() );
			$url_parts[ 'path' ] = isset( $url_parts[ 'path' ] ) ? $url_parts[ 'path' ] : '';
			foreach ( $alp_matches[ 4 ] as $k => $m ) {
				if ( 0 === strpos( $m, 'wp-content' ) ) {
					continue;
				}

				$lang = false;

				if ( $sitepress_settings[ 'language_negotiation_type' ] == 1 ) {
					$m_orig = $m;
					$exp    = explode( '/', $m, 2 );
					$lang   = $exp[ 0 ];
					if ( $this->does_lang_exist( $lang ) ) {
						$m = $exp[ 1 ];
					} else {
						$m = $m_orig;
						unset( $m_orig );
						$lang = false;
					}
				}

				$pathinfo       = '';
				$req_uri        = '/' . $m;
				$req_uri_array  = explode( '?', $req_uri );
				$req_uri        = $req_uri_array[ 0 ];
				$req_uri_params = '';
				if ( isset( $req_uri_array[ 1 ] ) ) {
					$req_uri_params = $req_uri_array[ 1 ];
				}
				// separate anchor
				$req_uri_array = explode( '#', $req_uri );
				$req_uri       = $req_uri_array[ 0 ];
				$anchor        = isset( $req_uri_array[ 1 ] ) ? $req_uri_array[ 1 ] : false;
				$home_path     = parse_url( get_home_url() );
				if ( isset( $home_path[ 'path' ] ) ) {
					$home_path = $home_path[ 'path' ];
				} else {
					$home_path = '';
				}
				$home_path = trim( $home_path, '/' );

				$req_uri  = str_replace( $pathinfo, '', rawurldecode( $req_uri ) );
				$req_uri  = trim( $req_uri, '/' );
				$req_uri  = preg_replace( "|^$home_path|", '', $req_uri );
				$req_uri  = trim( $req_uri, '/' );
				$pathinfo = trim( $pathinfo, '/' );
				$pathinfo = preg_replace( "|^$home_path|", '', $pathinfo );
				$pathinfo = trim( $pathinfo, '/' );

				if ( !empty( $pathinfo ) && !preg_match( '|^.*' . $wp_rewrite->index . '$|', $pathinfo ) ) {
					$request = $pathinfo;
				} else {
					// If the request uri is the index, blank it out so that we don't try to match it against a rule.
					if ( $req_uri == $wp_rewrite->index ) {
						$req_uri = '';
					}
					$request = $req_uri;
				}

				$request_match = $request;

				$permalink_query_vars = array();

				foreach ( (array)$rewrite as $match => $query ) {

					// If the requesting file is the anchor of the match, prepend it
					// to the path info.
					if ( ( !empty( $req_uri ) ) && ( strpos( $match, $req_uri ) === 0 ) && ( $req_uri != $request ) ) {
						$request_match = $req_uri . '/' . $request;
					}

					if ( preg_match( "!^$match!", $request_match, $matches ) || preg_match( "!^$match!", urldecode( $request_match ), $matches ) ) {
						// Got a match.

						// Trim the query of everything up to the '?'.
						$query = preg_replace( "!^.+\?!", '', $query );

						// Substitute the substring matches into the query.
						$query = addslashes( WP_MatchesMapRegex::apply( $query, $matches ) );

						// Parse the query.
						parse_str( $query, $permalink_query_vars );

						break;
					}
				}

				$post_name = $category_name = $tax_name = false;

				if ( isset( $permalink_query_vars[ 'pagename' ] ) ) {
					$icl_post_lang = !is_null( $filtered_icl_post_language ) ? $filtered_icl_post_language : $current_language;
					$sitepress->switch_lang( $icl_post_lang );
					$page_by_path = get_page_by_path( $permalink_query_vars[ 'pagename' ] );
					$sitepress->switch_lang( $current_language );

					if ( !empty( $page_by_path->post_type ) ) {
						$post_name = $permalink_query_vars[ 'pagename' ];
						$post_type = 'page';
					} else {
						$post_name = $permalink_query_vars[ 'pagename' ];
						$post_type = 'post';
					}

				} elseif ( isset( $permalink_query_vars[ 'name' ] ) ) {
					$post_name = $permalink_query_vars[ 'name' ];
					$post_type = 'post';
				} elseif ( isset( $permalink_query_vars[ 'category_name' ] ) ) {
					$category_name = $permalink_query_vars[ 'category_name' ];
				} elseif ( isset( $permalink_query_vars[ 'p' ] ) ) { // case or /archives/%post_id
					$post_data_prepared = $wpdb->prepare( "SELECT post_type, post_name FROM {$wpdb->posts} WHERE id=%d", $permalink_query_vars[ 'p' ] );
					list( $post_type, $post_name ) = $wpdb->get_row( $post_data_prepared, ARRAY_N );
				} else {
					if ( empty( $this->custom_post_query_vars ) or empty( $this->taxonomies_query_vars ) ) {
						$this->init_query_vars();
					}
					foreach ( $this->custom_post_query_vars as $query_vars_key => $query_vars_value ) {
						if ( isset( $permalink_query_vars[ $query_vars_value ] ) ) {
							$post_name = $permalink_query_vars[ $query_vars_value ];
							$post_type = $query_vars_key;
							break;
						}
					}
					foreach ( $this->taxonomies_query_vars as $query_vars_value ) {
						if ( isset( $permalink_query_vars[ $query_vars_value ] ) ) {
							$tax_name = $permalink_query_vars[ $query_vars_value ];
							$tax_type = $query_vars_value;
							break;
						}
					}
				}

				if ( $post_name && isset( $post_type ) ) {

					$icl_post_lang = !is_null( $filtered_icl_post_language ) ? $filtered_icl_post_language : $current_language;
					$sitepress->switch_lang( $icl_post_lang );
					$p = get_page_by_path( $post_name, OBJECT, $post_type );
					$sitepress->switch_lang( $current_language );

					if ( empty( $p ) ) { // fail safe
						if ( $post_id = url_to_postid( $home_path . '/' . $post_name ) ) {
							$p = get_post( $post_id );
						}
					}

					if ( $p ) {
						if ( $post_type == 'page' ) {
							$qvid = 'page_id';
						} else {
							$qvid = 'p';
						}
						if ( $sitepress_settings[ 'language_negotiation_type' ] == 1 && $lang ) {
							$langprefix = '/' . $lang;
						} else {
							$langprefix = '';
						}
						$perm_url = '(' . rtrim( $home_url, '/' ) . ')?' . $langprefix . '/' . str_replace( '?', '\?', $m );
						$regk     = '@href=["\'](' . $perm_url . ')["\']@i';
						if ( $anchor ) {
							$anchor = "#" . $anchor;
						} else {
							$anchor = "";
						}
						// check if this is an offsite url
						if ( $p->post_type == 'page' && $offsite_url = get_post_meta( $p->ID, '_cms_nav_offsite_url', true ) ) {
							$regv = 'href="' . $offsite_url . $anchor . '"';
						} else {
							$regv = 'href="' . '/' . ltrim( $url_parts[ 'path' ], '/' ) . '?' . $qvid . '=' . $p->ID;
							if ( $req_uri_params != '' ) {
								$regv .= '&' . $req_uri_params;
							}
							$regv .= $anchor . '"';
						}
						$def_url[ $regk ] = $regv;
					} else {
						$alp_broken_links[ $alp_matches[ 2 ][ $k ] ] = array();
						$name                                        = wpml_like_escape( $post_name );
						$p                                           = $this->_get_ids_and_post_types( $name );
						if ( $p ) {
							foreach ( $p as $post_suggestion ) {
								if ( $post_suggestion->post_type == 'page' ) {
									$qvid = 'page_id';
								} else {
									$qvid = 'p';
								}
								$alp_broken_links[ $alp_matches[ 2 ][ $k ] ][ 'suggestions' ][ ] = array(
										'absolute' => '/' . ltrim( $url_parts[ 'path' ], '/' ) . '?' . $qvid . '=' . $post_suggestion->ID,
										'perma'    => '/' . ltrim( str_replace( site_url(), '', get_permalink( $post_suggestion->ID ) ), '/' ),
								);
							}
						}
					}
				} elseif ( $category_name ) {
					if ( false !== strpos( $category_name, '/' ) ) {
						$splits             = explode( '/', $category_name );
						$category_name      = array_pop( $splits );
						$category_parent    = array_pop( $splits );
						$category_parent_id = $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM {$wpdb->terms} WHERE slug=%s", $category_parent ) );
						$c                  = $wpdb->get_row( $wpdb->prepare( "SELECT t.term_id FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON x.term_id=t.term_id AND x.taxonomy='category' AND x.parent=%d AND t.slug=%s", $category_parent_id, $category_name ) );
					} else {
						$c = $wpdb->get_row( $wpdb->prepare( "SELECT term_id FROM {$wpdb->terms} WHERE slug=%s", $category_name ) );
					}
					if ( $c ) {
						/* not used ?? */
						if ( $sitepress_settings[ 'language_negotiation_type' ] == 1 && $lang ) {
							$langprefix = '/' . $lang;
						} else {
							$langprefix = '';
						}
						/* not used ?? */
						$perm_url         = '(' . rtrim( $home_url, '/' ) . ')?' . $langprefix . '/' . $m;
						$regk             = '@href=[\'"](' . $perm_url . ')[\'"]@i';
						$url_parts        = parse_url( rtrim( get_home_url(), '/' ) . '/' );
						$regv             = 'href="' . '/' . ltrim( $url_parts[ 'path' ], '/' ) . '?cat_ID=' . $c->term_id . '"';
						$def_url[ $regk ] = $regv;
					} elseif ( isset( $name ) ) {
						$alp_broken_links[ $alp_matches[ 2 ][ $k ] ] = array();
						$c_prepared                                  = $wpdb->prepare( "SELECT term_id FROM {$wpdb->terms} WHERE slug LIKE %s", array( $name . '%' ) );
						$c                                           = $wpdb->get_results( $c_prepared );
						if ( $c ) {
							foreach ( $c as $cat_suggestion ) {
								$perma = '/' . ltrim( str_replace( get_home_url(), '', get_category_link( $cat_suggestion->term_id ) ), '/' );

								$alp_broken_links[ $alp_matches[ 2 ][ $k ] ][ 'suggestions' ][ ] = array(
										'absolute' => '?cat_ID=' . $cat_suggestion->term_id,
										'perma'    => $perma
								);
							}
						}
					}
				} elseif ( $tax_name && isset( $tax_type ) ) {

					if ( $sitepress_settings[ 'language_negotiation_type' ] == 1 && $lang ) {
						$langprefix = '/' . $lang;
					} else {
						$langprefix = '';
					}

					$perm_url = '(' . rtrim( $home_url, '/' ) . ')?' . $langprefix . '/' . $m;
					$regk     = '@href=["\'](' . $perm_url . ')["\']@i';
					if ( $anchor ) {
						$anchor = "#" . $anchor;
					} else {
						$anchor = "";
					}

					$regv             = 'href="' . '/' . ltrim( $url_parts[ 'path' ], '/' ) . '?' . $tax_type . '=' . $tax_name . $anchor . '"';
					$def_url[ $regk ] = $regv;

				}
			}

			if ( !empty( $def_url ) ) {
				$text = preg_replace( array_keys( $def_url ), array_values( $def_url ), $text );

			}

			$tx_qvs   = !empty( $this->taxonomies_query_vars ) && is_array( $this->taxonomies_query_vars ) ? '|' . join( '|', $this->taxonomies_query_vars ) : '';
			$post_qvs = !empty( $this->custom_posts_query_vars ) && is_array( $this->custom_posts_query_vars ) ? '|' . join( '|', $this->custom_posts_query_vars ) : '';
			$int      = preg_match_all( '@href=[\'"](' . rtrim( get_home_url(), '/' ) . '/?\?(p|page_id' . $tx_qvs . $post_qvs . ')=([0-9a-z-]+)(#.+)?)[\'"]@i', $text, $matches2 );
			if ( $int ) {
				$url_parts = parse_url( rtrim( get_home_url(), '/' ) . '/' );
				$text      = preg_replace( '@href=[\'"](' . rtrim( get_home_url(), '/' ) . '/?\?(p|page_id' . $tx_qvs . $post_qvs . ')=([0-9a-z-]+)(#.+)?)[\'"]@i', 'href="' . '/' . ltrim( $url_parts[ 'path' ], '/' ) . '?$2=$3$4"', $text );
			}


		}

		wp_cache_set( $cache_key, $text, $cache_group );

		return $text;
	}
	
	private function get_home_url_with_no_lang_directory( ) {
		global $sitepress, $sitepress_settings;
		$sitepress_settings = $sitepress->get_settings();
		
		$home_url = rtrim( get_home_url(), '/' );
		if ( $sitepress_settings[ 'language_negotiation_type' ] == 1 ) {
			
			// Strip lang directory from end if it's there.
			
			$exp  = explode( '/', $home_url);
			$lang = end( $exp );
			
			if ( $this->does_lang_exist( $lang ) ) {
				$home_url = substr( $home_url, 0, strlen($home_url) - strlen( $lang ) );
			}
		}
		
		return $home_url;
	}
	
	private function does_lang_exist ( $lang ) {
		global $wpdb;
		
		return $wpdb->get_var( "SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='{$lang}'" );
		
	}
	
	function _get_ids_and_post_types( $name ) {
		global $wpdb;
		static $cache = array();
		
		$name = rawurlencode( $name );
		if ( ! isset( $cache[ $name ] ) ) {
			$cache[ $name ] = $wpdb->get_results( $wpdb->prepare ("SELECT ID, post_type FROM {$wpdb->posts} WHERE post_name LIKE %s AND post_type IN('post','page')", $name . '%' ) );
		}
		
		return $cache[ $name ];
	}

	function all_rewrite_rules($rewrite) {
			global $sitepress;

			if ( !class_exists( 'WPML_Slug_Translation' ) ) {
				return $rewrite;
			}

			$active_languages = $sitepress->get_active_languages();
			$current_language = $sitepress->get_current_language();
			$default_language = $sitepress->get_default_language();

			$cache_keys = array($current_language, $default_language);
			$cache_keys[] = md5(serialize($active_languages));
			$cache_keys[] = md5(serialize($rewrite));
			$cache_key = implode(':', $cache_keys);
			$cache_group = 'all_rewrite_rules';
			$cache_found = false;

			$final_rules = wp_cache_get($cache_key, $cache_group, false, $cache_found);

			if($cache_found) return $final_rules;

			$final_rules = $rewrite;

			foreach ($active_languages as $next_language) {
				
				if ($next_language['code'] == $default_language) {
					continue;
				}
				
				$sitepress->switch_lang($next_language['code']);
				
				$translated_rules = WPML_Slug_Translation::rewrite_rules_filter($final_rules);

				if ( is_array( $translated_rules ) && is_array($final_rules) ) {
					$new_rules = array_diff_assoc( $translated_rules, $final_rules );

					$final_rules = array_merge( $new_rules, $final_rules );
				}
			}
			
			$sitepress->switch_lang($current_language);
			
			wp_cache_set($cache_key, $final_rules, $cache_group);

			return $final_rules;
			
		}

	function process_string( $st_id, $translation = true ) {
		global $wpdb;
		if ( $st_id ) {
			if ( $translation ) {
				$string_value = $wpdb->get_var( "SELECT value FROM {$wpdb->prefix}icl_string_translations WHERE id=" . $st_id );
			} else {
				$string_value = $wpdb->get_var( "SELECT value FROM {$wpdb->prefix}icl_strings WHERE id=" . $st_id );
			}
			$alp_broken_links = array();
			$string_value_up  = $this->_process_generic_text( $string_value, $alp_broken_links );
			if ( $string_value_up != $string_value ) {
				if ( $translation ) {
					$wpdb->update( $wpdb->prefix . 'icl_string_translations', array( 'value' => $string_value_up ), array( 'id' => $st_id ) );
				} else {
					$wpdb->update( $wpdb->prefix . 'icl_strings', array( 'value' => $string_value_up ), array( 'id' => $st_id ) );
				}
			}
		}
	}

	function process_post( $post_id ) {
		global $wpdb, $sitepress;

		delete_post_meta( $post_id, '_alp_broken_links' );

		$post             = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %s", $post_id )  );
		$alp_broken_links = array();

		$this_post_language = $sitepress->get_language_for_element($post_id, 'post_' . $post->post_type);
		$current_language = $sitepress->get_current_language();
		
		$sitepress->switch_lang($this_post_language);
		
		$post_content = $this->_process_generic_text( $post->post_content, $alp_broken_links );
		
		$sitepress->switch_lang($current_language);

		if ( $post_content != $post->post_content ) {
			$wpdb->update( $wpdb->posts, array( 'post_content' => $post_content ), array( 'ID' => $post_id ) );
		}

		update_post_meta( $post_id, '_alp_processed', time() );
		if ( !empty( $alp_broken_links ) ) {
			update_post_meta( $post_id, '_alp_broken_links', $alp_broken_links );
		}
	}

}
