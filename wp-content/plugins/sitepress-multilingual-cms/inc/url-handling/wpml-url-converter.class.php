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
	protected $current_lang;
	protected $absolute_home;
	/** @var  string[] $cache */
	protected $cache;
	protected $hidden_languages;

	/**
	 * @param string   $default_language
	 * @param string[] $hidden_languages
	 */
	public function __construct($default_language, $hidden_languages){
		global $wpml_language_resolution;
		add_filter( 'term_link', array( $this, 'tax_permalink_filter' ), 1, 3 );
		$this->absolute_home = $this->get_abs_home();
		$this->default_language = $default_language;
		$this->hidden_languages = (array)$hidden_languages;
		$this->active_languages = $wpml_language_resolution->get_active_language_codes();
	}

	/**
	 * Checks if a $url points to a WP Admin screen.
	 *
	 * @param string $url
	 * @return bool True if the input $url points to an admin screen.
	 */
	public function is_url_admin($url){
		$url_query_parts = parse_url ( $url );

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

					: $wpdb->get_var( "	SELECT option_value
									FROM {$wpdb->options}
									WHERE option_name = 'home'
									LIMIT 1" ) )
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

		$cache_found = false;
		$new_url     = wp_cache_get( $cache_key, $cache_group, false, $cache_found );

		if ( ! $cache_found ) {
			$new_url = $this->get_language_from_url( $url ) === $lang_code
				? $url
				: $this->convert_url_string( $url,
											 $lang_code );
			$new_url = $this->fix_trailing_slash( $new_url, $url );
			wp_cache_set( $cache_key, $new_url, $cache_group );
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
											$language_code );

		if ( is_string( $translated_slug ) ) {
			$link_new = trailingslashit(
				preg_replace( "/" . preg_quote( $slug, "/" ) . "/", $translated_slug, $link, 1 )
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

	protected abstract function convert_url_string( $url, $lang );

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

		if ( $wpml_url_filters->frontend_uses_root () ) {
			$htaccess_string = str_replace (
				'/' . $this->default_language . '/index.php',
				'/index.php',
				$htaccess_string
			);
			$htaccess_string = str_replace (
				'RewriteBase /' . $this->default_language . '/',
				'RewriteBase /',
				$htaccess_string
			);
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
		$tag = is_object($tag) ? $tag : get_term($tag, $taxonomy);
		$tag_id   = $tag ? $tag->term_taxonomy_id : 0;
		$cached_permalink_key =  $tag_id . '.' . $taxonomy;
		$found  = false;
		$cached_permalink = wp_cache_get($cached_permalink_key, 'icl_tax_permalink_filter', $found);
		if($found === true) {
			return $cached_permalink;
		}
		$term_language = $tag_id ? $wpml_term_translations->get_element_lang_code($tag_id) : false;
		$permalink = (bool) $term_language === true  ? $this->convert_url( $permalink, $term_language ) : $permalink;

		wp_cache_set($cached_permalink_key, $permalink, 'icl_tax_permalink_filter');

		return $permalink;
	}

	private function fix_trailing_slash( $url, $reference_url ) {

		return trailingslashit( $reference_url ) === $reference_url && strpos( $url, '?lang=' ) === false
			   && strpos( $url, '&lang=' ) === false
			? trailingslashit( $url ) : untrailingslashit( $url );
	}
}