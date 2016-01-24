<?php

/**
 * Class WPML_Language_Resolution
 *
 * @package    wpml-core
 * @subpackage wpml-requests
 */
class WPML_Language_Resolution {

	private $active_language_codes = array();
	private $current_request_lang  = null;
	private $default_lang          = null;
	private $hidden_lang_codes     = null;

	public function __construct( $active_language_codes, $default_lang ) {
		add_filter( 'icl_set_current_language', array( $this, 'current_lang_filter' ), 10, 2 );
		add_filter( 'icl_current_language', array( $this, 'current_lang_filter' ), 10, 2 );
		add_action( 'wpml_cache_clear', array( $this, 'reload' ), 11, 0 );
		$this->active_language_codes = array_fill_keys( $active_language_codes, 1 );
		$this->default_lang          = $default_lang;
		$this->hidden_lang_codes     = array_fill_keys( wpml_get_setting_filter( array(), 'hidden_languages' ), 1 );
	}

	/**
	 * Returns the language_code of the http referrer's location from which a request originated.
	 * Used to correctly determine the language code on ajax link lists for the post edit screen or
	 * the flat taxonomy auto-suggest.
	 *
	 * @param bool $return_default
	 *
	 * @return string|null
	 */
	public function get_referrer_language_code( $return_default = false ) {
		if ( ! empty( $_SERVER[ 'HTTP_REFERER' ] ) ) {
			$query_string = parse_url( $_SERVER[ 'HTTP_REFERER' ], PHP_URL_QUERY );
			$query        = array();
			parse_str( strval( $query_string ), $query );
			$language_code = isset( $query[ 'lang' ] ) ? $query[ 'lang' ] : null;
		}

		return isset( $language_code ) ? $language_code : ( $return_default ? $this->default_lang : null );
	}

	public function reload() {
		$this->active_language_codes = null;
		$this->hidden_lang_codes     = null;
		$this->default_lang          = null;
		$this->maybe_reload();
	}

	public function current_lang_filter( $lang ) {

		if ( $this->current_request_lang !== $lang ) {
			if ( ( $preview_lang = $this->filter_preview_language_code() ) ) {
				$lang = $preview_lang;
			} elseif ( $this->use_referrer_language() === true ) {
				$lang = $this->get_referrer_language_code();
			}
		}
		$this->current_request_lang = $this->filter_for_legal_langs( $lang );

		return $this->current_request_lang;
	}

	public function get_active_language_codes() {
		$this->maybe_reload();

		return array_keys( $this->active_language_codes );
	}

	public function is_language_hidden( $lang_code ) {
		$this->maybe_reload();

		return isset( $this->hidden_lang_codes[ $lang_code ] );
	}

	public function is_language_active( $lang_code, $is_all_active = false ) {
		global $wpml_request_handler;
		$this->maybe_reload();

		return ( $is_all_active === true && $lang_code === 'all' )
			   || isset( $this->active_language_codes[ $lang_code ] )
			   || ( $wpml_request_handler->show_hidden() && $this->is_language_hidden( $lang_code ) );
	}

	private function maybe_reload() {
		$this->default_lang          = $this->default_lang
			? $this->default_lang : wpml_get_setting_filter( false, 'default_language' );
		$this->active_language_codes = (bool) $this->active_language_codes === true
			? $this->active_language_codes : array_fill_keys( wpml_reload_active_languages_setting( true ), 1 );
	}

	/**
	 *
	 * Sets the language of frontend requests to false, if they are not for
	 * a hidden or active language code. The handling of permissions in case of
	 * hidden languages is done in \SitePress::init.
	 *
	 * @param string $lang
	 *
	 * @return bool|string
	 */
	private function filter_for_legal_langs( $lang ) {

		$this->maybe_reload();

		if ( $lang === 'all' && is_admin() ) {
			return 'all';
		}

		if ( ! isset( $this->hidden_lang_codes[ $lang ] ) && ! isset( $this->active_language_codes[ $lang ] ) ) {
			$lang = $this->default_lang ? $this->default_lang : icl_get_setting( 'default_language' );
		}

		return $lang;
	}

	private function use_referrer_language() {
		$get_action  = filter_input( INPUT_GET, 'action' );
		$post_action = filter_input( INPUT_POST, 'action' );

		return $get_action === 'ajax-tag-search'
			   || $post_action === 'get-tagcloud'
			   || $post_action === 'wp-link-ajax';
	}

	/**
	 * Adjusts the output of the filtering for the current language in case
	 * the request is for a preview page.
	 *
	 * @return null|string
	 */
	private function filter_preview_language_code() {
		$preview_id   = filter_var(
			isset( $_GET['preview_id'] ) ? $_GET['preview_id'] : '',
			FILTER_SANITIZE_NUMBER_INT );
		$preview_flag = filter_input( INPUT_GET, 'preview' ) || $preview_id;
		$preview_id   = $preview_id ? $preview_id : filter_input( INPUT_GET, 'p' );
		$preview_id   = $preview_id ? $preview_id : filter_input( INPUT_GET, 'page_id' );
		$lang         = null;

		if ( $preview_flag && $preview_id ) {
			global $wpml_post_translations;
			$lang = $wpml_post_translations->get_element_lang_code( $preview_id );
		}

		return $lang;
	}
}