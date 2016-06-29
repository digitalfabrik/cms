<?php
/**
 * @global WPML_Term_Translation $wpml_term_translations
 */

/**
 * Class WPML_URL_Converter
 *
 * @abstract
 * @package    wpml-core
 * @subpackage url-handling
 *
 */

abstract class WPML_URL_Converter {

	protected $default_language;
	protected $active_languages;
	protected $absolute_home;
	/** @var  string[] $cache */
	protected $cache;
	protected $resolving_url = false;

	/**
	 * @param string $default_language
	 * @param array  $active_languages
	 */
	public function __construct( $default_language, $active_languages ) {
		add_filter( 'term_link', array( $this, 'tax_permalink_filter' ), 1, 3 );
		$this->default_language = $default_language;
		$this->active_languages = $active_languages;
	}

	/**
	 * Checks if a $url points to a WP Admin screen.
	 *
	 * @param string $url
	 * @return bool True if the input $url points to an admin screen.
	 */
	public function is_url_admin($url){
		$url_query_parts = wpml_parse_url( strpos( $url, 'http' ) === false ? 'http://' . $url : $url );

		return isset( $url_query_parts[ 'path' ] )
		       && strpos ( wpml_strip_subdir_from_url($url_query_parts[ 'path' ]), '/wp-admin' ) === 0;
	}

	/**
	 *
	 * @param string $url
	 * @param bool   $only_admin If set to true only language parameters on Admin Screen URLs will be recognized. The
	 *                           function will return null for non-Admin Screens.
	 *
	 * @return null|String Language code
	 */
	protected function lang_by_param( $url, $only_admin = true ) {

		if(isset($this->cache[$url])){
			return $this->cache[$url];
		}
		$url = wpml_strip_subdir_from_url($url);
		$url_query_parts = wpml_parse_url ( $url );
		$url_query       = ($only_admin === false
		                       || isset( $url_query_parts[ 'path' ] )
		                          && strpos ( $url_query_parts[ 'path' ], '/wp-admin' ) === 0)
		                   && isset( $url_query_parts[ 'query' ] )
			? untrailingslashit($url_query_parts[ 'query' ]) : null;
		if ( isset( $url_query ) ) {
			parse_str ( $url_query, $vars );
			if ( isset( $vars[ 'lang' ] )
			     && ($only_admin === true && $vars['lang'] === 'all' || in_array ( $vars[ 'lang' ], $this->active_languages, true ) )) {
				$lang = $vars[ 'lang' ];
			}
		}

		$lang = isset( $lang ) ? $lang : null;
		$this->cache[ $url ] = $lang;

		return $lang;
	}

	/**
	 * Returns the unfiltered home_url by directly retrieving it from wp_options.
	 *
	 * @return string
	 *
	 * @global wpdb $wpdb
	 *
	 */
	public function get_abs_home() {
		global $wpdb;

		$this->absolute_home = $this->absolute_home
			? $this->absolute_home
			: ( ! is_multisite() && defined( 'WP_HOME' )
				? WP_HOME
				: ( is_multisite() && ! is_main_site()
					? ( preg_match( '/^(https)/', get_option( 'home' ) ) === 1 ? 'https://'
						: 'http://' ) . $wpdb->get_var( "	SELECT CONCAT(b.domain, b.path)
									FROM {$wpdb->blogs} b
									WHERE blog_id = {$wpdb->blogid}
									LIMIT 1" )
					: $this->get_unfiltered_home_option() )
			);

		return apply_filters( 'wpml_url_converter_get_abs_home', $this->absolute_home );
	}

	/**
	 * Scope of this function:
	 * 1. Convert the home URL in the specified language depending on language negotiation:
	 *    1. Add a language directory
	 *    2. Change the domain
	 *    3. Add a language parameter
	 * 2. If the requested URL is equal to the current URL, the URI will be adapted
	 * with potential slug translations for:
	 *    - single post slugs
	 *    - taxonomy term slug
	 *
	 * WARNING: The URI slugs won't be translated for arbitrary URL (not the current one)
	 *
	 * @param $url
	 * @param bool $lang_code
	 *
	 * @return bool|mixed|string
	 */
	public function convert_url( $url, $lang_code = false ) {
		if ( ! $url ) {
			return $url;
		}

		global $sitepress;

		$lang_code = $lang_code ? $lang_code : $sitepress->get_current_language();
		$negotiation_type = $sitepress->get_setting( 'language_negotiation_type' );

		$cache_key_args = array( $url, $lang_code, $negotiation_type );
		$cache_key      = md5( wp_json_encode( $cache_key_args ) );
		$cache_group    = 'convert_url';
		$cache_found    = false;
		$cache          = new WPML_WP_Cache( $cache_group );
		
		$new_url        = $cache->get( $cache_key, $cache_found );

		if ( ! $cache_found ) {
			$language_from_url  = $this->get_language_from_url( $url );
			if ( $language_from_url === $lang_code ) {
				$new_url = $url;
			} else {
				$server_name = isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : "";
				$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : "";
				$server_name = strpos( $request_uri, '/' ) === 0
					? untrailingslashit( $server_name ) : trailingslashit( $server_name );
				$request_url = stripos( get_option( 'siteurl' ), 'https://' ) === 0
					? 'https://' . $server_name . $request_uri : 'http://' . $server_name . $request_uri;

				$is_request_url     = trailingslashit( $request_url ) === trailingslashit( $url );
				$is_home_url        = trailingslashit( $this->get_abs_home() ) === trailingslashit( $url );
				$is_home_url_filter = current_filter() === 'home_url';

				if( $is_request_url && ! $is_home_url && ! $is_home_url_filter && ! $this->resolving_url ) {
					$new_url = $this->resolve_object_url( $url, $lang_code );
				}

				if ( $new_url === false ) {
					$new_url = $this->convert_url_string( $url, $lang_code );
				}
			}
			$new_url = $this->fix_trailing_slash( $new_url, $url );
			$cache->set( $cache_key, $new_url );
		}

		return $new_url;
	}

	/**
	 * Takes a URL and returns the language of the document it points at
	 *
	 * @param string $url
	 * @return string
	 */
	public function get_language_from_url( $url ) {
		if(isset($this->cache[$url])){
			return $this->cache[$url];
		}

		if ( !( $language = $this->lang_by_param ( $url ) ) ) {
			$language = $this->get_lang_from_url_string($url);
		}

		$lang                = $this->validate_language( $language, $url );
		$this->cache[ $url ] = $lang;

		return $lang;
	}

	/**
	 * Adjusts the CPT archive slug for possible slug translations from ST.
	 *
	 * @param string $link
	 * @param string $post_type
	 * @param null|string $language_code
	 *
	 * @return string
	 */
	function adjust_cpt_in_url( $link, $post_type, $language_code = null ) {

		$post_type_object = get_post_type_object( $post_type );

		if ( isset( $post_type_object->rewrite ) ) {
			$slug = trim( $post_type_object->rewrite['slug'], '/' );
		} else {
			$slug = $post_type_object->name;
		}

		$translated_slug = apply_filters( 'wpml_get_translated_slug',
										    $slug,
											$post_type,
											$language_code );

		if ( is_string( $translated_slug ) ) {
			$link_parts = explode( '?', $link, 2 );
			$link_new = trailingslashit(
				preg_replace( "#\/" . preg_quote( $slug, "#" ) . "\/#", '/' . $translated_slug . '/', trailingslashit( $link_parts[0] ), 1 )
			);
			$link = $this->fix_trailing_slash( $link_new, $link_parts[0] );
			$link = isset( $link_parts[1] ) ? $link . '?' . $link_parts[1] : $link;
		}

		return $link;
	}

	protected function validate_language( $language, $url ) {
		return in_array ( $language, $this->active_languages, true )
		       || $language === 'all' && $this->is_url_admin ( $url ) ? $language : $this->default_language ();
	}

	private function default_language(){
		$this->default_language = $this->default_language ? $this->default_language : icl_get_setting ( 'default_language' );

		return $this->default_language;
	}

	protected abstract function get_lang_from_url_string($url);

	protected abstract function convert_url_string( $source_url, $lang );

	/**
	 * Filters the string content of the .htaccess file that WP writes when saving permalinks.
	 * This is only used when using languages in directories and a root page and prevents the default language slug to
	 * be used a rewrite base leading to error #500 as it is not actually part of the rewrite base, but only a result
	 * of WPML's filtering on the home_url.
	 *
	 * @param string $htaccess_string Content of the .htaccess file
	 * @return string .htaccess file contents with adjusted RewriteBase
	 */
	public function rewrite_rules_filter( $htaccess_string ) {
		global $wpml_url_filters;

		if ( $wpml_url_filters->frontend_uses_root() ) {
			foreach ( $this->active_languages as $lang_code ) {
				foreach ( array( '', 'index.php' ) as $base ) {
					$htaccess_string = str_replace(
						'/' . $lang_code . '/' . $base,
						'/' . $base,
						$htaccess_string
					);
				}
			}
		}

		return $htaccess_string;
	}

	/**
	 * Filters the permalink pointing at a taxonomy archive to correctly reflect the language of its underlying term
	 *
	 * @param string     $permalink url pointing at a term's archive
	 * @param Object|int $tag       term object or term_id of the term
	 * @param string     $taxonomy  the term's taxonomy
	 *
	 * @return string
	 */
	public function tax_permalink_filter( $permalink, $tag, $taxonomy ) {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations;
		
		$tag                  = is_object( $tag ) ? $tag : get_term( $tag, $taxonomy );
		$tag_id               = $tag ? $tag->term_taxonomy_id : 0;
		$cached_permalink_key =  $tag_id . '.' . $taxonomy;
		$cache_group          = 'icl_tax_permalink_filter';
		$found                = false;
		$cache                = new WPML_WP_Cache( $cache_group );
		$cached_permalink     = $cache->get( $cached_permalink_key, $found );
		if( $found === true ) {
			return $cached_permalink;
		}
		$term_language = $tag_id ? $wpml_term_translations->get_element_lang_code( $tag_id ) : false;
		$permalink = (bool) $term_language === true  ? $this->convert_url( $permalink, $term_language ) : $permalink;

		$cache->set( $cached_permalink_key, $permalink );

		return $permalink;
	}

	private function fix_trailing_slash( $url, $reference_url ) {

		return trailingslashit( $reference_url ) === $reference_url && strpos( $url, '?lang=' ) === false
			   && strpos( $url, '&lang=' ) === false
			? trailingslashit( $url ) : untrailingslashit( $url );
	}

	/**
	 * Returns the unfiltered home option from the database.
	 *
	 * @uses \WPML_Include_Url::get_unfiltered_home in case the $wpml_include_url_filter global is loaded
	 *
	 * @return string
	 */
	private function get_unfiltered_home_option() {
		global $wpml_include_url_filter, $wpdb;

		return ( $wpml_include_url_filter ? $wpml_include_url_filter->get_unfiltered_home()
			: $wpdb->get_var( "	SELECT option_value
									FROM {$wpdb->options}
									WHERE option_name = 'home'
									LIMIT 1" ) );
	}

	/**
	 * Try to parse the URL to find a related post or term
	 *
	 * @param string $url
	 * @param string $lang_code
	 *
	 * @return string|bool
	 */
	private function resolve_object_url( $url, $lang_code ) {
		global $sitepress, $wp_query, $wpml_term_translations, $wpml_post_translations;// todo: pass as a dependencies

		$this->resolving_url = true;
		$new_url        = false;
		$cache_key      = md5( $url );
		$cache_group    = 'resolve_object_url';
		$cache_found    = false;
		$cache          = new WPML_WP_Cache( $cache_group );
		$translations   = $cache->get( $cache_key, $cache_found );

		if ( ! $cache_found && is_object( $wp_query ) ) {
			$sitepress->set_wp_query(); // Make sure $sitepress->wp_query is set
			$_wp_query_back = clone $wp_query;
			unset( $wp_query );
			global $wp_query; // make it global again after unset
			$tmp_wp_query = $sitepress->get_wp_query();
			$wp_query = is_object( $tmp_wp_query ) ? clone $tmp_wp_query : clone $_wp_query_back;
			unset( $tmp_wp_query );

			$languages_helper = new WPML_Languages( $wpml_term_translations, $sitepress, $wpml_post_translations );
			list( $translations, $wp_query ) = $languages_helper->get_ls_translations( $wp_query,
																					   $_wp_query_back,
				                                                                       $sitepress->get_wp_query() );

			// restore current $wp_query
			unset( $wp_query );
			global $wp_query; // make it global again after unset
			$wp_query = clone $_wp_query_back;
			unset( $_wp_query_back );

			$cache->set( $cache_key, $translations );
		}

		if ( $translations && isset( $translations[ $lang_code ]->element_type ) ) {

			$current_lang = $sitepress->get_current_language();
			$sitepress->switch_lang( $lang_code );
			$element = explode( '_', $translations[ $lang_code ]->element_type );
			$type = array_shift( $element );
			$subtype = implode( '_', $element );
			switch( $type ) {
				case 'post':
					$new_url = get_permalink( $translations[ $lang_code ]->element_id );
					break;
				case 'tax':
					$term = get_term( $translations[ $lang_code ]->element_id, $subtype );
					$new_url = get_term_link( $term );
					break;
			}
			$sitepress->switch_lang( $current_lang );
		}

		$this->resolving_url = false;
		return $new_url;
	}
}