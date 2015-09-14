<?php

/**
 * Class WPML_URL_Filters
 */
class WPML_URL_Filters extends WPML_SP_And_PT_User {

	/** @var WPML_URL_Converter $url_converter */
	private $url_converter;

	/**
	 * @param WPML_Post_Translation $post_translation
	 * @param WPML_URL_Converter    $url_converter
	 * @param SitePress             $sitepress
	 */
	public function __construct( &$post_translation, &$url_converter, &$sitepress ) {
		parent::__construct( $post_translation, $sitepress );
		$this->url_converter = $url_converter;
		if ( $this->frontend_uses_root() === true ) {
			require_once ICL_PLUGIN_PATH . '/inc/url-handling/wpml-root-page.class.php';
			add_filter( 'page_link', array( $this, 'permalink_filter_root' ), 1, 2 );
		} else {
			add_filter( 'page_link', array( $this, 'permalink_filter' ), 1, 2 );
		}
		add_filter( 'home_url', array( $this, 'home_url_filter' ), - 10, 1 );
		// posts and pages links filters
		add_filter( 'post_link', array( $this, 'permalink_filter' ), 1, 2 );
		add_filter( 'post_type_link', array( $this, 'permalink_filter' ), 1, 2 );
		add_filter( 'get_edit_post_link', array( $this, 'get_edit_post_link' ), 1, 3 );
	}

	/**
	 * Filters the link to a post's edit screen by appending the language query argument
	 *
	 * @param  string $link
	 * @param    int  $id
	 * @param string  $context
	 *
	 * @return string
	 *
	 * @hook get_edit_post_link
	 */
	public function get_edit_post_link( $link, $id, $context = 'display' ) {
		if ( $id && (bool) ( $lang = $this->post_translation->get_element_lang_code( $id ) ) === true ) {
			$link .= ( 'display' === $context ? '&amp;' : '&' ) . 'lang=' . $lang;
		}

		return $link;
	}

	/**
	 * Permalink filter that is used when the site uses a root page
	 *
	 * @param string      $link
	 * @param int|WP_Post $pid
	 *
	 * @return string
	 */
	public function permalink_filter_root( $link, $pid ) {
		$pid  = is_object( $pid ) ? $pid->ID : $pid;
		$link = $this->sitepress->get_root_page_utils()->get_root_page_id() != $pid
				? $this->permalink_filter( $link, $pid ) : $this->filter_root_permalink( $link );

		return $link;
	}

	/**
	 * Filters links to the root page, so that they are displayed properly in the front-end.
	 *
	 * @param $url
	 *
	 * @return string
	 */
	public function filter_root_permalink( $url ) {
		$root_page_utils = $this->sitepress->get_root_page_utils();
		if ( $root_page_utils->get_root_page_id() > 0 && $root_page_utils->is_url_root_page( $url ) ) {
			$url_parts = parse_url( $url );
			$query     = isset( $url_parts['query'] ) ? $url_parts['query'] : '';
			$path      = isset( $url_parts['path'] ) ? $url_parts['path'] : '';
			$slugs     = array_filter( explode( '/', $path ) );
			$last_slug = array_pop( $slugs );
			$new_url   = $this->url_converter->get_abs_home();
			$new_url   = is_numeric( $last_slug ) ? trailingslashit( trailingslashit( $new_url ) . $last_slug ) : $new_url;
			$query     = $this->unset_page_query_vars( $query );
			$new_url   = trailingslashit( $new_url );
			$url       = (bool) $query === true ? trailingslashit( $new_url ) . '?' . $query : $new_url;
		}

		return $url;
	}

	/**
	 * @param string      $link
	 * @param int|WP_Post $post_object
	 *
	 * @return bool|mixed|string
	 */
	public function permalink_filter( $link, $post_object ) {
		$post_object = is_object( $post_object ) ? $post_object->ID : $post_object;
		$post_type   = isset( $post_object->post_type )
				? $post_object->post_type : $this->sitepress->get_wp_api()->get_post_type( $post_object );
		if ( $this->sitepress->is_translated_post_type( $post_type ) ) {

			$code = $this->get_permalink_filter_lang( $post_object );
			$link = $this->url_converter->convert_url( $link, $code );
			$link = $this->sitepress->get_wp_api()->is_feed() ? str_replace( "&lang=", "&#038;lang=", $link ) : $link;
		}

		return $link;
	}

	public function home_url_filter( $url ) {
		$server_name = isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : "";
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : "";
		$server_name = strpos( $request_uri, '/' ) === 0
				? untrailingslashit( $server_name ) : trailingslashit( $server_name );
		$url_snippet = $server_name . $request_uri;

		return $this->url_converter->convert_url(
				$url,
				$this->url_converter->get_language_from_url(
						$url_snippet
				)
		);
	}

	public function frontend_uses_root() {
		$urls = $this->sitepress->get_setting( 'urls' );

		return isset( $urls['root_page'] ) && isset( $urls['show_on_root'] )
		       && ! empty( $urls['directory_for_default_language'] )
		       && ( $urls['show_on_root'] === 'page' || $urls['show_on_root'] === 'html_file' );
	}

	/**
	 * Finds the correct language a post belongs to by handling the special case of the post edit screen.
	 *
	 * @param WP_Post $post_object
	 *
	 * @return bool|mixed|null|String
	 */
	private function get_permalink_filter_lang( $post_object ) {
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'sample-permalink' ) {
			$code = filter_var( ( isset( $_GET['lang'] ) ? $_GET['lang'] : "" ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$code = $code
				? $code
				: ( ! isset( $_SERVER['HTTP_REFERER'] )
					? $this->sitepress->get_default_language()
					: $this->url_converter->get_language_from_url( $_SERVER["HTTP_REFERER"] ) );
		} else {
			$code = $this->post_translation->get_element_lang_code( $post_object );
		}

		return $code;
	}

	private function unset_page_query_vars( $query ) {
		parse_str( (string) $query, $query_parts );
		foreach ( array( 'p', 'page_id', 'page', 'pagename', 'page_name', 'attachement_id' ) as $part ) {
			if ( isset( $query_parts[ $part ] ) && ! ( $part === 'page_id' && ! empty( $query_parts['preview'] ) ) ) {
				unset( $query_parts[ $part ] );
			}
		}

		return http_build_query( $query_parts );
	}
}