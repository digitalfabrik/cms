<?php

class WPML_TM_Service_Activation_Resources extends WPML_TM_Resources_Factory {
	private $script_handle = 'wpml_tm_service_activation';

	/**
	 * @param WPML_WP_API $wpml_wp_api
	 */
	public function __construct( &$wpml_wp_api ) {
		parent::__construct( $wpml_wp_api );
	}

	public function register_resources( $hook_suffix ) {
		if ( $this->wpml_wp_api->is_jobs_tab() ) {
			wp_register_script( $this->script_handle, WPML_TM_URL . '/res/js/service-activation.js', array( 'jquery', 'jquery-ui-dialog' ), false, true );
		}
	}

	public function enqueue_resources( $hook_suffix ) {
		if ( $this->wpml_wp_api->is_jobs_tab() ) {
			$strings = array(
				'alertTitle'          => _x( 'Incomplete local translation jobs', 'Incomplete local jobs after TS activation: [response] 00 Title', 'wpml-translation-management' ),
				'cancelledJobs'       => _x( 'Cancelled local translation jobs:', 'Incomplete local jobs after TS activation: [response] 01 Cancelled', 'wpml-translation-management' ),
				'openJobs'            => _x( 'Open local translation jobs:', 'Incomplete local jobs after TS activation: [response] 02 Open', 'wpml-translation-management' ),
				'errorCancellingJobs' => _x( 'Unable to cancel some or all jobs', 'Incomplete local jobs after TS activation: [response] 03 Error', 'wpml-translation-management' ),
				'errorGeneric'        => _x( 'Unable to complete the action', 'Incomplete local jobs after TS activation: [response] 04 Error', 'wpml-translation-management' ),
				'keepLocalJobs'       => _x( 'Local translation jobs will be kept and the above notice hidden.', 'Incomplete local jobs after TS activation: [response] 10 Close button', 'wpml-translation-management' ),
				'closeButton'         => _x( 'Close', 'Incomplete local jobs after TS activation: [response] 20 Close button', 'wpml-translation-management' ),
				'confirm'             => _x( 'Are you sure you want to do this?', 'Incomplete local jobs after TS activation: [confirmation] 01 Message', 'wpml-translation-management' ),
				'yes'                 => _x( 'Yes', 'Incomplete local jobs after TS activation: [confirmation] 01 Yes', 'wpml-translation-management' ),
				'no'                  => _x( 'No', 'Incomplete local jobs after TS activation: [confirmation] 01 No', 'wpml-translation-management' ),
			);
			wp_localize_script( $this->script_handle, $this->script_handle . '_strings', $strings );
			wp_enqueue_script( $this->script_handle );
		}
	}
}