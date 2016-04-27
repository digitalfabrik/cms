<?php

class WPML_XDomain_Data_Parser {

	public function __construct() {
		global $sitepress_settings;

		if ( ! isset( $sitepress_settings['xdomain_data'] ) || $sitepress_settings['xdomain_data'] != WPML_XDOMAIN_DATA_OFF ) {
			add_action( 'init', array( $this, 'init' ) );
			add_filter( 'wpml_get_cross_domain_language_data', array( $this, 'get_xdomain_data' ) );
		}
	}

	public function init() {
		add_action( 'wp_ajax_switching_language', array( $this, 'xdomain_language_data_setup' ) );
		add_action( 'wp_ajax_nopriv_switching_language', array( $this, 'xdomain_language_data_setup' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts_action' ), 100 );
	}

	public function register_scripts_action() {
		if ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) {
			wp_enqueue_script( 'wpml-xdomain-data', ICL_PLUGIN_URL . '/res/js/xdomain-data.js', array( 'jquery', 'sitepress' ), ICL_SITEPRESS_VERSION );
		}
	}

	public function xdomain_language_data_setup() {
		global $sitepress_settings;

		$ret = array();

		$data = apply_filters( 'WPML_cross_domain_language_data', array() );
		$data = apply_filters( 'wpml_cross_domain_language_data', $data );

		if ( ! empty( $data ) ) {

			$encoded_data = json_encode( $data );

			if ( function_exists( 'mcrypt_encrypt' ) && function_exists( 'mcrypt_decrypt' ) ) {
				$key             = substr( NONCE_KEY, 0, 24 );
				$mcrypt_iv_size  = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
				$mcrypt_iv       = mcrypt_create_iv( $mcrypt_iv_size, MCRYPT_RAND );
				$encoded_data = mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $key, $encoded_data, MCRYPT_MODE_ECB, $mcrypt_iv );
			}

			$base64_encoded_data = base64_encode( $encoded_data );
			$ret['xdomain_data'] = urlencode( $base64_encoded_data );

			$ret['method'] = WPML_XDOMAIN_DATA_POST == $sitepress_settings['xdomain_data'] ? 'post' : 'get';

		}

		wp_send_json_success( $ret );
	}

	public function get_xdomain_data() {
		$xdomain_data = array();

		if ( isset( $_GET['xdomain_data'] ) || isset( $_POST['xdomain_data'] ) ) {
			global $sitepress_settings;

			$xdomain_data_request = false;

			if ( WPML_XDOMAIN_DATA_GET == $sitepress_settings['xdomain_data'] ) {
				$xdomain_data_request = isset( $_GET['xdomain_data'] ) ? $_GET['xdomain_data'] : false;
			} elseif ( WPML_XDOMAIN_DATA_POST == $sitepress_settings['xdomain_data'] ) {
				$xdomain_data_request = isset( $_POST['xdomain_data'] ) ? $_POST['xdomain_data'] : false;
			}

			if ( $xdomain_data_request ) {
				$data = base64_decode( $xdomain_data_request );
				if ( function_exists( 'mcrypt_encrypt' ) && function_exists( 'mcrypt_decrypt' ) ) {
					$key             = substr( NONCE_KEY, 0, 24 );
					$mcrypt_iv_size  = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
					$mcrypt_iv       = mcrypt_create_iv( $mcrypt_iv_size, MCRYPT_RAND );
					$data = mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_MODE_ECB, $mcrypt_iv );
				}
				$xdomain_data = (array) json_decode( $data, JSON_OBJECT_AS_ARRAY );
			}
		}
		return $xdomain_data;
	}
}
