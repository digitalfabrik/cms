<?php

/**
 * Class WPML_Request
 *
 * @package    wpml-core
 * @subpackage wpml-requests
 *
 * @abstract
 */

abstract class WPML_Request extends WPML_URL_Converter_User {

	protected $active_languages;
	protected $default_language;
	protected $qs_lang_cache;
	private   $cookie;

	/**
	 * @param WPML_URL_Converter $url_converter
	 * @param array              $active_languages
	 * @param string             $default_language
	 * @param WPML_Cookie        $cookie
	 */
	public function __construct( &$url_converter, $active_languages, $default_language, $cookie ) {
		parent::__construct( $url_converter );
		$this->active_languages = $active_languages;
		$this->default_language = $default_language;
		$this->cookie           = $cookie;
		add_filter( 'WPML_get_language_cookie', array( $this, 'get_cookie_lang' ), 10, 0 );
		add_filter( 'wmpl_get_language_cookie', array( $this, 'get_cookie_lang' ), 10, 0 );
	}

	protected abstract function get_cookie_name();

	/**
	 * Determines the language of the current request.
	 *
	 * @return string|false language code of the current request, determined from the requested url and the user's
	 *                      cookie.
	 */
	public abstract function get_requested_lang();

	/**
	 * Returns the current REQUEST_URI optionally filtered
	 *
	 * @param null|int $filter filter to apply to the REQUEST_URI, takes the same arguments
	 *                         as filter_var for the filter type.
	 *
	 * @return string
	 */
	public function get_request_uri( $filter = null ) {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/';
		if ( $filter !== null ) {
			$request_uri = filter_var( $request_uri, $filter );
		}

		return $request_uri;
	}

	/**
	 * @global $wpml_url_converter
	 *
	 * @return string|false language code that can be determined from the currently requested URI.
	 */
	public function get_request_uri_lang() {
		$req_url = isset($_SERVER[ 'HTTP_HOST' ])
			? untrailingslashit($_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] ) : "";

		return $this->url_converter->get_language_from_url ( $req_url );
	}

	/**
	 * @return string language code stored in the user's _icl_current_language cookie
	 */
	public function get_cookie_lang() {
		global $wpml_language_resolution;
		$cookie_name  = $this->get_cookie_name();
		$cookie_value = $this->cookie->get_cookie( $cookie_name );
		$lang         = $cookie_value ? substr( $cookie_value, 0, 10 ) : "";
		$lang         = $wpml_language_resolution->is_language_active( $lang ) ? $lang : $this->default_language;

		return $lang;
	}

	/**
	 * Checks whether hidden languages are to be displayed at the moment.
	 * They are displayed in the frontend if the users has the respective option icl_show_hidden_languages set in his
	 * user_meta. The are displayed in the backend for all admins with manage_option capabilities.
	 *
	 * @return bool true if hidden languages are to be shown
	 */
	public function show_hidden() {

		return !did_action( 'init' )
		       || ( get_user_meta( get_current_user_id(), 'icl_show_hidden_languages', true )
		            || ( is_admin() && current_user_can( 'manage_options' ) ) );
	}

	/**
	 * Sets the language code of the current screen in the User's _icl_current_language cookie
	 *
	 * @param string $lang_code
	 */
	public function set_language_cookie( $lang_code ) {
		$cookie_name = $this->get_cookie_name();
		if ( ! $this->cookie->headers_sent() ) {
			if ( preg_match( '@\.(css|js|png|jpg|gif|jpeg|bmp)@i',
					basename( preg_replace( '@\?.*$@', '', $_SERVER['REQUEST_URI'] ) ) )
			     || isset( $_POST['icl_ajx_action'] ) || isset( $_POST['_ajax_nonce'] ) || defined( 'DOING_AJAX' )
			) {
				return;
			}

			$cookie_domain = $this->get_cookie_domain();
			$cookie_path   = defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
			$this->cookie->set_cookie( $cookie_name, $lang_code, time() + 86400, $cookie_path, $cookie_domain );
		}
		$_COOKIE[ $cookie_name ] = $lang_code;
	}

	/**
	 * @return bool|string
	 */
	public function get_cookie_domain() {

		return defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : $this->get_server_host_name();
	}

	/**
	 * Returns SERVER_NAME, or HTTP_HOST if the first is not available
	 *
	 * @return string
	 */
	public function get_server_host_name() {
		$host = isset( $_SERVER[ 'HTTP_HOST' ] ) ? $_SERVER[ 'HTTP_HOST' ] : null;
		$host = $host !== null
			? $host
			: ( isset( $_SERVER[ 'SERVER_NAME' ] )
				? $_SERVER[ 'SERVER_NAME' ]
				  . ( isset( $_SERVER[ 'SERVER_PORT' ] ) && ! in_array( $_SERVER[ 'SERVER_PORT' ], array( 80, 443 ) )
					? $_SERVER[ 'SERVER_PORT' ] : '' )
				: '' );

		//Removes standard ports 443 (80 should be already omitted in all cases)
		$result = preg_replace( "@:[443]+([/]?)@", '$1', $host );

		return $result;
	}
}