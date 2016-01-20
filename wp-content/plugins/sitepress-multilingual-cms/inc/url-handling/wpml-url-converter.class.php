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
	protected $hidden_languages;

	/**
	 * @param string   $default_language
	 * @param array $hidden_languages
	 * @param WPML_WP_API $wpml_wp_api
	 */
	public function __construct( $default_language, $hidden_languages, &$wpml_wp_api ) {
		global $wpml_language_resolution;
		add_filter( 'admin_url', array( $this, 'admin_url_filter' ), 1, 2 );
		add_filter( 'term_link', array( $this, 'tax_permalink_filter' ), 1, 3 );
		$this->wpml_wp_api      = &$wpml_wp_api;
		$this->default_language = $default_language;
		$this->hidden_languages = (array)$hidden_languages;
		$this->active_languages = $wpml_language_resolution->get_active_language_codes();
	}

	public function admin_url_filter( $url, $path ) {
		if ( 'admin-ajax.php' === $path ) {
			$url = $this->get_admin_ajax_url( $url );
		}
		return $url;
	}

	public function get_admin_ajax_url( $url ) {
		global $sitepress;

		//todo: this should actually change the url with `add_query_arg( array( 'lang' => $sitepress->get_current_language() ), $url );` but it may cause conflicts with other plugins which does not properly change this URL. Let's put this on hold for the moment.
		return $url;
	}

	/**
	 * Checks if a $url points to a WP Admin screen.
	 *
	 * @param string $url
	 * @return bool True if the input $url points to an admin screen.
	 */
	public function is_url_admin($url){
		$url_query_parts = parse_url( strpos( $url, 'http' ) === false ? 'http://' . $url : $url );

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
		$url_query_parts = parse_url ( $url );
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

		return $this->absolute_home;
	}

	public function convert_url( $url, $lang_code = false ) {
		global $sitepress;

		$lang_code = $lang_code ? $lang_code : $sitepress->get_current_language();

		if ( ! $url ) {
			return $url;
		}

		$cache_key_args = array( $url, $lang_code );
		$cache_key      = md5( wp_json_encode( $cache_key_args ) );
		$cache_group    = 'convert_url';
		$cache_found    = false;
		$cache          = new WPML_WP_Cache( $cache_group );
		
		$new_url        = $cache->get( $cache_key, $cache_found );

		if ( ! $cache_found ) {
			$language_from_url = $this->get_language_from_url( $url );
			if ( $language_from_url === $lang_code ) {
				$new_url = $url;
			} else {
				$new_url = $this->convert_url_string( $url, $lang_code );
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

		$lang                = $this->validate_language ( $language, $url );
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
			$link_new = trailingslashit(
				preg_replace( "#\/" . preg_quote( $slug, "/" ) . "#", '/' . $translated_slug, $link, 1 )
			);
			$link = $this->fix_trailing_slash($link_new, $link);
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
}