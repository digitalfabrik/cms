<?php

class WPML_TM_Words_Count_AJAX extends WPML_TM_AJAX_Factory {
	/**
	 * @var WPML_TM_Words_Count
	 */
	private $wpml_tm_words_count;

	/**
	 * @param WPML_TM_Words_Count            $wpml_tm_words_count
	 * @param WPML_TM_Words_Count_Summary_UI $wpml_tm_words_count_summary
	 * @param WPML_WP_API                    $wpml_wp_api
	 */
	public function __construct( &$wpml_tm_words_count, &$wpml_tm_words_count_summary, &$wpml_wp_api ) {
		parent::__construct( $wpml_wp_api );

		$this->wpml_tm_words_count          = &$wpml_tm_words_count;
		$this->wpml_tm_words_count_summary = &$wpml_tm_words_count_summary;
		if ( $this->wpml_wp_api->is_ajax() ) {
			$this->add_ajax_action( 'wp_ajax_wpml_set_words_count_panel_default_status', array( $this, 'set_words_count_panel_default_status' ) );
			$this->add_ajax_action( 'wp_ajax_wpml_words_count_summary', array( $this, 'get_summary' ) );
			$this->init();
		}
	}

	public function set_words_count_panel_default_status() {
		$open        = filter_input( INPUT_GET, 'open', FILTER_VALIDATE_BOOLEAN );
		$valid_nonce = check_ajax_referer( 'wpml_set_words_count_panel_default_status', 'wpml_words_count_panel_nonce', false );

		if ( $valid_nonce ) {
			$this->wpml_tm_words_count->set_words_count_panel_default_status( $open );
		}

		$result = 'Ok!';

		return $this->wpml_wp_api->wp_send_json_success( $result );
	}

	public function get_summary() {
		$result = false;
		$source_language = filter_input( INPUT_GET, 'source_language', FILTER_DEFAULT );
		$valid_nonce     = check_ajax_referer( 'wpml_words_count_summary', 'nonce', false );

		if ( $valid_nonce ) {

			$rows = array();
			if ( $source_language ) {
				$rows = $this->wpml_tm_words_count->get_summary( $source_language );
			}

			if ( count( $rows ) ) {
				$this->wpml_tm_words_count_summary->rows = $rows;
				$result                                  = $this->wpml_tm_words_count_summary->get_view();
			}
		}

		if ( $result ) {
			return $this->wpml_wp_api->wp_send_json_success( $result );
		} else {
			return $this->wpml_wp_api->wp_send_json_error( 'Error!' );
		}
	}

	public function enqueue_resources( $hook_suffix ) {
		return;
	}
}