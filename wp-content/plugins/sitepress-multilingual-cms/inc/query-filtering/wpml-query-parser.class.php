<?php

/**
 * Class WPML_Query_Parser
 *
 * @since 3.2.3
 */
class WPML_Query_Parser extends WPML_Full_Translation_API {

	/** @var WPML_Query_Filter $query_filter */
	private $query_filter;

	/**
	 * @param SitePress             $sitepress
	 * @param wpdb                  $wpdb
	 * @param WPML_Post_Translation $post_translation
	 * @param WPML_Term_Translation $term_translation
	 * @param WPML_Query_Filter     $query_filter
	 */
	public function __construct( &$sitepress, &$wpdb, &$post_translation, &$term_translation, &$query_filter ) {
		parent::__construct( $sitepress, $wpdb, $post_translation, $term_translation );
		$this->query_filter = &$query_filter;
	}

	/**
	 * @param WP_Query $q
	 *
	 * @return WP_Query
	 */
	function parse_query( $q ) {
		if ( is_admin() ) {
			return $q;
		}

		list( $q, $redir_pid ) = $this->maybe_adjust_name_var( $q );
		/** @var WP_Query $q */
		if ( $q->is_main_query() && (bool) $redir_pid === true ) {
			if ( (bool) ( $redir_target = $this->is_redirected( $redir_pid ) ) ) {
				$this->sitepress->get_wp_api()->wp_safe_redirect( $redir_target );
			}
		}

		$current_language = $this->sitepress->get_current_language();
		if ( $current_language !== $this->sitepress->get_default_language() ) {
			$cat_array = ! empty( $q->query_vars['cat'] ) ? array_map( 'intval',
			                                                           array_map( 'trim',
			                                                                      explode( ',',
			                                                                               $q->query_vars['cat'] ) ) ) : array();
			if ( ! empty( $q->query_vars['category_name'] ) ) {
				$categories = array_filter( array_map( 'trim', explode( ",", $q->query_vars['category_name'] ) ) );
				$cat_array  = array();
				foreach ( $categories as $category ) {
					$cat = get_term_by( 'slug', preg_replace( '#((.*)/)#', '', $category ), 'category' );
					$cat = $cat ? $cat : get_term_by( 'name', $category, 'category' );
					if ( is_object( $cat ) && $cat->term_id ) {
						$cat_array[] = $cat->term_id;
					}
				}
				if ( empty( $cat_array ) ) {
					$q->query_vars['p'] = - 1;
				}
			}
			if ( ! empty( $q->query_vars['category__and'] ) ) {
				$cat_array = $q->query_vars['category__and'];
			}
			if ( ! empty( $q->query_vars['category__in'] ) ) {
				$cat_array = array_unique( array_merge( $cat_array,
				                                        array_map( 'intval', $q->query_vars['category__in'] ) ) );
			}
			if ( ! empty( $q->query_vars['category__not_in'] ) ) {
				$__cats = array();
				foreach ( $q->query_vars['category__not_in'] as $key => $val ) {
					$__cats[ $key ] = - 1 * intval( $val );
				}
				$cat_array = array_unique( array_merge( $cat_array, $__cats ) );
			}
			if ( ! empty( $cat_array ) ) {
				$translated_ids = array();
				foreach ( $cat_array as $c ) {
					$sign             = intval( $c ) < 0 ? - 1 : 1;
					$translated_ids[] = $sign * intval( $this->term_translations->term_id_in( abs( $c ),
					                                                                          $current_language,
					                                                                          true ) );
				}
				if ( ! empty( $q->query_vars['cat'] ) ) {
					$q->query_vars['cat'] = join( ',', $translated_ids );
				}
				if ( ! empty( $q->query_vars['category_name'] ) ) {
					$_ctmp                          = get_term_by( 'id', $translated_ids[0], 'category' );
					$q->query_vars['category_name'] = $_ctmp->slug;
				}
				if ( ! empty( $q->query_vars['category__and'] ) ) {
					$q->query_vars['category__and'] = $translated_ids;
				}
				if ( ! empty( $q->query_vars['category__in'] ) ) {
					$__translated_in = array();
					foreach ( $translated_ids as $key => $t_id ) {
						if ( $t_id > 0 ) {
							$__translated_in[ $key ] = $t_id;
						}
					}
					$q->query_vars['category__in'] = $__translated_in;
				}
				if ( ! empty( $q->query_vars['category__not_in'] ) ) {
					$__translated_not_in = array();
					foreach ( $translated_ids as $key => $t_id ) {
						if ( $t_id < 0 ) {
							$__translated_not_in[ $key ] = $t_id;
						}
					}
					$q->query_vars['category__not_in'] = $__translated_not_in;
				}
			}
			$tag_array = array();
			$tag_glue  = '';
			if ( ! empty( $q->query_vars['tag'] ) ) {
				$tag_glue = false !== strpos( $q->query_vars['tag'], ' ' ) ? '+' : ',';
				$exp      = explode( ' ', $q->query_vars['tag'] );
				foreach ( $exp as $e ) {
					$tag_array[] = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT x.term_id FROM {$this->wpdb->terms} t
						JOIN {$this->wpdb->term_taxonomy} x ON t.term_id=x.term_id WHERE x.taxonomy='post_tag' AND t.slug=%s LIMIT 1",
					                                                           $e ) );
				}
				$_tmp = array_unique( $tag_array );
				if ( count( $_tmp ) == 1 && empty( $_tmp[0] ) ) {
					$tag_array = array();
				}
			}
			if ( ! empty( $q->query_vars['tag_id'] ) ) {
				$tag_array = array_map( 'trim', explode( ',', $q->query_vars['tag_id'] ) );
			}

			foreach ( array( 'tag__not_in', 'tag__in', 'tag__and' ) as $index ) {
				if ( ! empty( $q->query_vars[ $index ] ) ) {
					$tag_array = $q->query_vars[ $index ];
					break;
				}
			}
			// tag_slug__in
			if ( ! empty( $q->query_vars['tag_slug__in'] ) ) {
				foreach ( $q->query_vars['tag_slug__in'] as $t ) {
					if ( $tg = $this->wpdb->get_var( $this->wpdb->prepare( "
								SELECT x.term_id FROM {$this->wpdb->terms} t
								JOIN {$this->wpdb->term_taxonomy} x ON t.term_id=x.term_id
								WHERE x.taxonomy='post_tag' AND t.slug=%s LIMIT 1",
					                                                       $t ) )
					) {
						$tag_array[] = $tg;
					}
				}
			}
			if ( ! empty( $q->query_vars['tag_slug__and'] ) ) {
				foreach ( $q->query_vars['tag_slug__and'] as $t ) {
					$tag_array[] = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT x.term_id FROM {$this->wpdb->terms} t
						JOIN {$this->wpdb->term_taxonomy} x ON t.term_id=x.term_id WHERE x.taxonomy='post_tag' AND t.slug=%s LIMIT 1",
					                                                           $t ) );
				}
			}
			if ( ! empty( $tag_array ) ) {
				$translated_ids = array();
				foreach ( $tag_array as $c ) {
					if ( intval( $c ) < 0 ) {
						$sign = - 1;
					} else {
						$sign = 1;
					}
					$_tid             = intval( $this->term_translations->term_id_in( abs( $c ),
					                                                                  $current_language,
					                                                                  true ) );
					$translated_ids[] = $sign * $_tid;
				}
			}
			if ( ! empty( $translated_ids ) ) {
				if ( isset( $q->query_vars['tag'] ) && $q->query_vars['tag'] !== "" ) {
					$slugs                = $this->wpdb->get_col( "SELECT slug
                                                               FROM {$this->wpdb->terms}
                                                               WHERE term_id IN (" . wpml_prepare_in( $translated_ids,
					                                                                                  '%d' ) . ")" );
					$q->query_vars['tag'] = join( $tag_glue, $slugs );
				}
				foreach ( array( 'tag__in', 'tag__and', 'tag_id' ) as $index ) {
					if ( ! empty( $q->query_vars[ $index ] ) ) {
						$q->query_vars[ $index ] = join( ',', $translated_ids );
						break;
					}
				}
				if ( ! empty( $q->query_vars['tag__not_in'] ) ) {
					$q->query_vars['tag__not_in'] = array_map( 'abs', $translated_ids );
				}
				if ( ! empty( $q->query_vars['tag_slug__in'] ) ) {
					$q->query_vars['tag_slug__in'] = $this->wpdb->get_col( "SELECT slug
                                                               FROM {$this->wpdb->terms}
                                                               WHERE term_id IN (" . wpml_prepare_in( $translated_ids,
					                                                                                  '%d' ) . ")" );
				}
				if ( ! empty( $q->query_vars['tag_slug__and'] ) ) {
					$q->query_vars['tag_slug__and'] = $this->wpdb->get_col( "SELECT slug
                                                               FROM {$this->wpdb->terms}
                                                               WHERE term_id IN (" . wpml_prepare_in( $translated_ids,
					                                                                                  '%d' ) . ")" );
				}
			}

			$post_type = ! empty( $q->query_vars['post_type'] ) ? $q->query_vars['post_type'] : 'post';
			if ( ! is_array( $post_type ) ) {
				$post_type = (array) $post_type;
			}
			if ( ! empty( $q->query_vars['page_id'] ) ) {
				$q->query_vars['page_id'] = $this->post_translations->element_id_in( $q->query_vars['page_id'],
				                                                                     $current_language,
				                                                                     true );
			}
			$q = $this->adjust_query_ids( $q, 'include' );
			$q = $this->adjust_query_ids( $q, 'exclude' );
			if ( isset( $q->query_vars['p'] ) && ! empty( $q->query_vars['p'] ) ) {
				$q->query_vars['p'] = $this->post_translations->element_id_in( $q->query_vars['p'],
				                                                               $current_language,
				                                                               true );
			}
			if ( $this->sitepress->is_translated_post_type( $post_type[0] ) && ! empty( $q->query_vars['name'] ) ) {
				if ( is_post_type_hierarchical( $post_type[0] ) ) {
					$reqpage = get_page_by_path( $q->query_vars['name'], OBJECT, $post_type[0] );
					if ( $reqpage ) {
						$q->query_vars['p'] = $this->post_translations->element_id_in( $reqpage->ID,
						                                                               $current_language,
						                                                               true );
						unset( $q->query_vars['name'] );
						// We need to set this to an empty string otherwise WP will derive the pagename from this.
						$q->query_vars[ $post_type[0] ] = '';
					}
				} else {
					$pid_prepared = $this->wpdb->prepare( "SELECT ID FROM {$this->wpdb->posts} WHERE post_name=%s AND post_type=%s LIMIT 1",
					                                      array( $q->query_vars['name'], $post_type[0] ) );
					$pid          = $this->wpdb->get_var( $pid_prepared );
					if ( ! empty( $pid ) ) {
						$q->query_vars['p'] = $this->post_translations->element_id_in( $pid, $current_language, true );
						unset( $q->query_vars['name'] );
					}
				}
			}
			$q = $this->adjust_q_var_pids( $q, $post_type, 'post__in' );
			$q = $this->adjust_q_var_pids( $q, $post_type, 'post__not_in' );
			$q = $this->maybe_adjust_parent( $q, $post_type, $current_language );
			//TODO: [WPML 3.3] Discuss this. Why WP assumes it's there if query vars are altered? Look at wp-includes/query.php line #2468 search: if ( $this->query_vars_changed ) {
			$q->query_vars['meta_query'] = isset( $q->query_vars['meta_query'] ) ? $q->query_vars['meta_query'] : array();

			if ( isset( $q->query_vars['tax_query'] ) && is_array( $q->query_vars['tax_query'] ) &&
					 isset( $q->query['tax_query'] ) && is_array( $q->query['tax_query'] )) {

				foreach ( $q->query['tax_query'] as $num => $fields ) {
					if ( ! isset( $fields['terms'] ) ) {
						continue;
					}
					if ( is_array( $fields['terms'] ) ) {
						foreach ( $fields['terms'] as $term ) {
							$term_index = isset( $fields['field'] ) ? $fields['field'] : 'term_id';
							$taxonomy   = get_term_by( $term_index, $term, $fields['taxonomy'] );
							if ( is_object( $taxonomy ) ) {
								if ( $term_index === 'id' && ! isset( $taxonomy->id ) ) {
									$field = isset( $taxonomy->term_id ) ? $taxonomy->term_id : null;
								} else {
									$field = isset( $taxonomy->{$term_index} ) ? $taxonomy->{$term_index} : null;
								}
								$tmp   = $q->query['tax_query'][ $num ]['terms'];
								$tmp   = array_diff( (array) $tmp,
								                     array( $term ) ); // removes from array element with original value
								$tmp[] = $field;
								//Reindex array
								$q->query['tax_query'][ $num ]['terms'] = array_values( $tmp );
								$tmp                                    = isset( $q->tax_query->queries[ $num ]['terms'] ) ? $q->tax_query->queries[ $num ]['terms'] : array();
								$tmp                                    = array_diff( (array) $tmp,
								                                                      array( $term ) ); // see above
								$tmp[]                                  = $field;
								//Reindex array
								$q->tax_query->queries[ $num ]['terms'] = array_values( $tmp );
								$tmp                                    = $q->query_vars['tax_query'][ $num ]['terms'];
								$tmp                                    = array_diff( (array) $tmp,
								                                                      array( $term ) ); // see above
								$tmp[]                                  = $field;
								//Reindex array
								$q->query_vars['tax_query'][ $num ]['terms'] = array_values( $tmp );
								unset( $tmp );
							}
						}
					} else if ( is_string( $fields['terms'] ) ) {
						$term_index = $fields['field'];
						$taxonomy   = get_term_by( $term_index, $fields['terms'], $fields['taxonomy'] );
						if ( is_object( $taxonomy ) ) {
							$field                                       = isset( $taxonomy->{$term_index} ) ? $taxonomy->{$term_index} : null;
							$q->query['tax_query'][ $num ]['terms']      = $field;
							$q->tax_query->queries[ $num ]['terms'][0]   = $field;
							$q->query_vars['tax_query'][ $num ]['terms'] = $field;
						}
					}
				}
			}
		}

		return $q;
	}

	/**
	 * Adjust the parent post in the query in case we're dealing with a translated
	 * post type.
	 *
	 * @param WP_Query        $q
	 * @param string|string[] $post_type
	 * @param string          $current_language
	 *
	 * @return WP_Query  mixed
	 */
	private function maybe_adjust_parent( $q, $post_type, $current_language ) {
		$post_type = ! is_scalar( $post_type ) && count( $post_type ) === 1 ? end( $post_type ) : $post_type;
		if ( ! empty( $q->query_vars['post_parent'] )
		     && $q->query_vars['post_type'] !== 'attachment'
		     && $post_type
		     && is_scalar( $post_type )
		     && $this->sitepress->is_translated_post_type( $post_type )
		) {
			$q->query_vars['post_parent'] = $this->post_translations->element_id_in(
				$q->query_vars['post_parent'],
				$current_language,
				true );
		}

		return $q;
	}

	/**
	 * Tries to transform certain queries from "by name" querying to "by ID" to overcome WordPress Core functionality
	 * for resolving names not being filtered by language
	 *
	 * @param WP_Query $q
	 *
	 * @return WP_Query
	 */
	private function maybe_adjust_name_var( $q ) {
		$redirect = false;
		if ( ( (bool) ( $name_in_q = $q->get( 'name' ) ) === true
		     || (bool) ( $name_in_q = $q->get( 'pagename' ) ) === true )
			&& (bool) $q->get( 'page_id' ) === false
			|| ( (bool) ( $post_type = $q->get('post_type') ) === true
                && is_scalar($post_type)
                && (bool) ( $name_in_q = $q->get($post_type)) === true ) ) {
			list( $name_found, $type, $altered ) = $this->query_filter->get_404_util()->guess_cpt_by_name( $name_in_q,
			                                                                                               $q );
			if ( $altered === true ) {
				$name_before = $q->get( 'name' );
				$q->set( 'name', $name_found );
			}
			$type = $type ? $type : 'page';
			$type = is_scalar( $type ) ? $type : ( count( $type ) === 1 ? end( $type ) : false );
			/**
			 * @var WP_Query $q
			 * @var $pid int|false
			 */
			list( $q, $redirect ) = $type
				? $this->query_filter->get_page_name_filter( $type )->filter_page_name( $q ) : array( $q, false );
			if ( isset( $name_before ) ) {
				$q->set( 'name', $name_before );
			}
		}

		return array( $q, $redirect );
	}

	private function adjust_query_ids( $q, $index ) {
		if ( ! empty( $q->query_vars[ $index ] ) ) {
			$untranslated = is_array( $q->query_vars[ $index ] ) ? $q->query_vars[ $index ] : explode( ',',
			                                                                                           $q->query_vars[ $index ] );
			$this->post_translations->prefetch_ids( $untranslated );
			$ulanguage_code = $this->sitepress->get_current_language();
			$translated     = array();
			foreach ( $untranslated as $element_id ) {
				$translated[] = $this->post_translations->element_id_in( $element_id, $ulanguage_code );
			}
			$q->query_vars[ $index ] = is_array( $q->query_vars[ $index ] ) ? $translated : implode( ',', $translated );
		}

		return $q;
	}

	private function adjust_q_var_pids( $q, $post_types, $index ) {
		if ( ! empty( $q->query_vars[ $index ] ) && (bool) $post_types !== false ) {

			$untranslated = $q->query_vars[ $index ];
			$this->post_translations->prefetch_ids( $untranslated );
			$current_lang = $this->sitepress->get_current_language();
			$pid          = array();
			foreach ( $q->query_vars[ $index ] as $p ) {
				$pid[] = $this->post_translations->element_id_in( $p, $current_lang, true );
			}
			$q->query_vars[ $index ] = $pid;
		}

		return $q;
	}

	/**
	 * @param int $post_id
	 *
	 * @return false|string redirect target url if redirect is needed, false otherwise
	 */
	private function is_redirected( $post_id ) {
		$request_uri = $_SERVER['REQUEST_URI'];
		$post        = $this->sitepress->get_wp_api()->get_post( $post_id );
		$parent      = $post->post_parent;
		$redirect    = false;
		if ( $parent ) {
			if ( strpos( $request_uri,
					parse_url(
						$this->sitepress->get_wp_api()->get_permalink( $post_id ),
						PHP_URL_PATH ) ) === false
			) {
				$redirect = $this->sitepress->get_wp_api()->get_permalink( $post_id );
			}
		}

		return $redirect;
	}
}
