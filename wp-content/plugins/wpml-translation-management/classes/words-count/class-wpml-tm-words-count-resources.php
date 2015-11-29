<?php

class WPML_TM_Words_Count_Resources extends WPML_TM_Resources_Factory {
	private $script_handle = 'wpml_tm_words_count';

	/**
	 * @param WPML_WP_API $wpml_wp_api
	 */
	public function __construct( &$wpml_wp_api ) {
		parent::__construct( $wpml_wp_api );
	}

	public function enqueue_resources( $hook_suffix ) {
		if ( $this->wpml_wp_api->is_dashboard_tab() ) {
			$data = array(
				'box_status' => get_option( 'wpml_words_count_panel_default_status', 'open' ),
			);
			wp_localize_script( $this->script_handle, $this->script_handle . '_data', $data );
			wp_enqueue_script( $this->script_handle );
			wp_enqueue_style( $this->script_handle );
		}
	}

	public function register_resources( $hook_suffix ) {
		if ( $this->wpml_wp_api->is_dashboard_tab() ) {
			wp_register_script( $this->script_handle, WPML_TM_URL . '/res/js/words-count.js', array( 'jquery', 'jquery-ui-accordion', 'jquery-ui-dialog' ) );
			wp_register_style( $this->script_handle, WPML_TM_URL . '/res/css/words-count.css' );
		}
	}
}