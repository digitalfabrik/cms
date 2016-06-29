<?php

class WPML_Language_Domain_Validation {

	/** @var WPML_WP_API $wp_api */
	private $wp_api;
	/** @var WP_Http $http */
	private $http;
	/** @var  string $url */
	private $url;
	/** @var string $language_code */
	private $language_code;

	/**
	 * WPML_Language_Domain_Validation constructor.
	 *
	 * @param WPML_WP_API $wp_api
	 * @param WP_Http     $http
	 * @param string      $url
	 * @param string      $language_code
	 */
	public function __construct( &$wp_api, &$http, $url, $language_code ) {
		$this->wp_api = &$wp_api;
		$this->http   = &$http;
		$this->url    = $url;
		if ( $this->validate_url_string() === false ) {
			throw new InvalidArgumentException( 'Invalid URL :' . $this->url );
		}
		$this->language_code = $language_code;
	}

	/**
	 * Makes a http request to the url this points at and checks if the requests
	 * returns the correct validation result.
	 *
	 * @return bool
	 */
	public function is_valid() {
		$url_glue = false === strpos( $this->url, '?' ) ? '?' : '&';
		$url      = trailingslashit( $this->url )
		            . (
		            $this->language_code ? '/' . $this->language_code . '/' : ''
		            ) . $url_glue . '____icl_validate_domain=1';
		$response = $this->http->request( $url, 'timeout=15' );

		return ! is_wp_error( $response )
		       && ( $response['response']['code'] == '200' )
		       && ( ( $response['body'] === '<!--' . untrailingslashit( $this->wp_api->get_home_url() ) . '-->' )
		            || $response['body'] === '<!--' . untrailingslashit( $this->wp_api->get_site_url() ) . '-->' );
	}

	/**
	 * Checks that the input url is valid in so far that it contains schema
	 * and host at least.
	 *
	 * @return bool
	 */
	private function validate_url_string() {
		$url_parts = wpml_parse_url( $this->url );

		return isset( $url_parts['scheme'] ) && isset( $url_parts['host'] );
	}
}
