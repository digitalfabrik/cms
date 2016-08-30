<?php

/**
 * @author OnTheGo Systems
 */
class WPML_ICL_Client {
	private $display_errors_backup;
	private $error_reporting_backup;
	private $error;
	private $gzipped = false;
	private $post_files;
	private $sitepress;
	private $method  = 'GET';
	private $post_data;

	/**
	 * WPML_ICL_Client constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress              = $sitepress;
		$this->display_errors_backup  = ini_get( 'display_errors' );
		$this->error_reporting_backup = ini_get( 'error_reporting' );
	}

	function request( $request_url ) {

		$results = false;

		$request_url = $this->get_adjusted_request_url( $request_url );

		$this->adjust_post_data();

		$this->disable_error_reporting();

		$icanSnoopy = new IcanSnoopy();
		if ( ! is_readable( $icanSnoopy->curl_path ) || ! is_executable( $icanSnoopy->curl_path ) ) {
			$icanSnoopy->curl_path = '/usr/bin/curl';
		}

		$this->reset_error_reporting();

		$icanSnoopy->_fp_timeout  = 3;
		$icanSnoopy->read_timeout = 5;
		if ( 'GET' === $this->method ) {
			$icanSnoopy->fetch( $request_url );
		} else {
			$icanSnoopy->set_submit_multipart();
			$icanSnoopy->submit( $request_url, $this->post_data, $this->post_files );
		}

		if ( $icanSnoopy->error || $icanSnoopy->timed_out ) {
			$this->error = $icanSnoopy->error;
		} else {

			if ( $this->gzipped ) {
				$icanSnoopy->results = $this->gzdecode( $icanSnoopy->results );
			}
			$results = icl_xml2array( $icanSnoopy->results, 1 );

			if ( array_key_exists( 'info', $results ) && '-1' === $results['info']['status']['attr']['err_code'] ) {
				$this->error = $results['info']['status']['value'];

				$results = false;
			}
		}

		return $results;
	}

	public function get_error() {
		return $this->error;
	}

	private function gzdecode( $data ) {

		return icl_gzdecode( $data );
	}

	/**
	 * @return array
	 */
	private function get_debug_data() {
		$debug_vars = array(
			'debug_cms'    => 'WordPress',
			'debug_module' => 'WPML ' . ICL_SITEPRESS_VERSION,
			'debug_url'    => get_bloginfo( 'url' ),
		);

		return $debug_vars;
	}

	/**
	 * @param $request_url
	 *
	 * @return mixed|string
	 */
	private function get_adjusted_request_url( $request_url ) {
		$request_url = str_replace( ' ', '%20', $request_url );

		if ( 'GET' === $this->method ) {
			$request_url .= '&' . http_build_query( $this->get_debug_data() );
		}

		$troubleshooting_options = $this->sitepress->get_setting( 'troubleshooting_options' );
		$http_communication      = array_key_exists( 'http_communication', $troubleshooting_options ) ? $troubleshooting_options['http_communication'] : false;
		if ( $http_communication ) {
			$request_url = str_replace( 'https://', 'http://', $request_url );
		}

		return $request_url;
	}

	private function adjust_post_data() {
		if ( 'GET' !== $this->method ) {
			$this->post_data = array_merge( $this->post_data, $this->get_debug_data() );
		}
	}

	private function reset_error_reporting() {
		ini_set( 'display_errors', $this->display_errors_backup );
		ini_set( 'error_reporting', $this->error_reporting_backup );
	}

	private function disable_error_reporting() {
		ini_set( 'display_errors', '0' );
		ini_set( 'error_reporting', 0 );
	}

	/**
	 * @param bool $value
	 */
	public function set_gzipped( $value ) {
		$this->gzipped = $value;
	}

	/**
	 * @param $method
	 */
	public function set_method( $method ) {
		$this->method = $method;
	}

	public function set_post_data( $post_data ) {
		$this->post_data = $post_data;
	}

	public function set_post_files( $post_files ) {
		$this->post_files = $post_files;
	}
}
