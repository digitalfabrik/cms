<?php
global $wpdb;

require WPML_TM_PATH . '/menu/basket-tab/wpml-basket-tab-ajax.class.php';

$basket_ajax = new WPML_Basket_Tab_Ajax( TranslationProxy::get_current_project(),
                                         wpml_tm_load_basket_networking(),
                                         new WPML_Translation_Basket( $wpdb ) );
add_action( 'init', array( $basket_ajax, 'init' ) );

function icl_get_jobs_table() {
	require_once WPML_TM_PATH . '/menu/wpml-translation-jobs-table.class.php';
	global $iclTranslationManagement;

	$nonce = filter_input( INPUT_POST, 'icl_get_jobs_table_data_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	if ( !wp_verify_nonce( $nonce, 'icl_get_jobs_table_data_nonce' ) ) {
		die( 'Wrong Nonce' );
	}

	$table = new WPML_Translation_Jobs_Table($iclTranslationManagement);
	$data  = $table->get_paginated_jobs();
	
	wp_send_json_success( $data );
}

function icl_get_job_original_field_content() {
    global $iclTranslationManagement;

    if ( !wpml_is_action_authenticated ( 'icl_get_job_original_field_content' ) ) {
        die( 'Wrong Nonce' );
    }

    $job_id = filter_input ( INPUT_POST, 'tm_editor_job_id', FILTER_SANITIZE_NUMBER_INT );
    $field = filter_input ( INPUT_POST, 'tm_editor_job_field' );
    $data = array();

    $job = $job_id !== null && $field !== null ? $job = $iclTranslationManagement->get_translation_job ( $job_id )
        : null;
    $elements = $job && isset( $job->elements ) ? $job->elements : array();

    foreach ( $elements as $element ) {
        $sanitized_type = sanitize_title ( $element->field_type );
        if ( $field === 'icl_all_fields' || $sanitized_type === $field ) {
            // if we find a field by that name we need to decode its contents according to its format
            $field_contents = $iclTranslationManagement->decode_field_data (
                $element->field_data,
                $element->field_format
            );
            if ( is_scalar ( $field_contents ) ) {
                $field_contents = strpos ( $field_contents, "\n" ) !== false ? wpautop ( $field_contents )
                                                                             : $field_contents;
                $data[ ] = array( 'field_type' => $sanitized_type, 'field_data' => $field_contents );
            }
        }
    }

    if ( (bool) $data !== false ) {
        wp_send_json_success ( $data );
    } else {
        wp_send_json_error ( 0 );
    }
}

function icl_populate_translations_pickup_box() {
	if ( ! wpml_is_action_authenticated( 'icl_populate_translations_pickup_box' ) ) {
		die( 'Wrong Nonce' );
	}

	global $sitepress, $wpdb;

	$last_picked_up     = $sitepress->get_setting( 'last_picked_up' );
	$translation_offset = strtotime( current_time( 'mysql' ) ) - @intval( $last_picked_up ) - 5 * 60;

	if ( WP_DEBUG == false && $translation_offset < 0 ) {
		$time_left = floor( abs( $translation_offset ) / 60 );
		if ( $time_left == 0 ) {
			$time_left = abs( $translation_offset );
			$wait_text = '<p><i>' . sprintf( __( 'You can check again in %s seconds.', 'sitepress' ), '<span id="icl_sec_tic">' . $time_left . '</span>' ) . '</i></p>';
		} else {
			$wait_text = sprintf( __( 'You can check again in %s minutes.', 'sitepress' ), '<span id="icl_sec_tic">' . $time_left . '</span>' ) . '</i></p>';
		}

		$result = array(
				'wait_text' => $wait_text,
		);
	} else {
		$project         = TranslationProxy::get_current_project();
		$job_factory     = wpml_tm_load_job_factory();
		$wpml_tm_records = new WPML_TM_Records( $wpdb );
		$cms_id_helper   = new WPML_TM_CMS_ID( $wpml_tm_records, $job_factory );
		$polling_status  = new WPML_TP_Polling_Status( $project, $sitepress, $cms_id_helper );
		$result          = $polling_status->get_status_array();
	}

	wp_send_json_success( $result );
}

function icl_pickup_translations() {
	if ( ! wpml_is_action_authenticated( 'icl_pickup_translations' ) ) {
		die( 'Wrong Nonce' );
	}
	global $ICL_Pro_Translation, $wpdb;
	$job_factory     = wpml_tm_load_job_factory();
	$wpml_tm_records = new WPML_TM_Records( $wpdb );
	$cms_id_helper   = new WPML_TM_CMS_ID( $wpml_tm_records, $job_factory );
	$pickup          = new WPML_TP_Polling_Pickup( $ICL_Pro_Translation, $cms_id_helper );
	wp_send_json_success( $pickup->poll_job( $_POST ) );
}

function icl_get_blog_users_not_translators() {
	$translator_drop_down_options = array();

	$nonce = filter_input( INPUT_POST, 'get_users_not_trans_nonce' );
	if ( !wp_verify_nonce( $nonce, 'get_users_not_trans_nonce' ) ) {
		die( 'Wrong Nonce' );
	}

	$blog_users_nt = TranslationManagement::get_blog_not_translators();

	foreach ( (array) $blog_users_nt as $u ) {
		$label                           = $u->display_name . ' (' . $u->user_login . ')';
		$value                           = esc_attr( $u->display_name );
		$translator_drop_down_options[ ] = array(
			'label' => $label,
			'value' => $value,
			'id'    => $u->ID
		);
	}

	wp_send_json_success( $translator_drop_down_options );
}

/**
 * Ajax handler for canceling translation Jobs.
 */
function icl_cancel_translation_jobs() {
	if ( !wpml_is_action_authenticated ( 'icl_cancel_translation_jobs' ) ) {
		die( 'Wrong Nonce' );
	}

	/** @var TranslationManagement $iclTranslationManagement */
	global $iclTranslationManagement;

	$job_ids = isset( $_POST[ 'job_ids' ] ) ? $_POST[ 'job_ids' ] : false;
	if ( $job_ids ) {
		foreach ( (array) $job_ids as $key => $job_id ) {
			$iclTranslationManagement->cancel_translation_request( $job_id );
		}
	}

	wp_send_json_success( $job_ids );
}
