<?php

class WPML_XDomain_Data_Parser {

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$nopriv = is_user_logged_in() ? '' : 'nopriv_';
			add_action( 'wp_ajax_' . $nopriv . 'switching_language', array( $this, 'xdomain_language_data_setup' ) );
		}
		add_action('wp_enqueue_scripts', array($this, 'register_scripts_action'), 100);
	}

	public function register_scripts_action() {
		if ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) {
			wp_enqueue_script( 'wpml-xdomain-data', ICL_PLUGIN_URL . '/res/js/xdomain-data.js', array( 'jquery', 'sitepress' ), ICL_SITEPRESS_VERSION );
		}
	}

	public function xdomain_language_data_setup() {
		$ret = array();

		$data = apply_filters( 'WPML_cross_domain_language_data', array() );
		$data = apply_filters( 'wpml_cross_domain_language_data', $data );
		if ( ! empty( $data ) ) {
			$ret[ 'xdomain_data' ] = base64_encode( json_encode( $data ) );
		}

		echo json_encode( $ret );
		exit;
	}
}
