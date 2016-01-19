<?php

/**
 * Class WPML_TP_Polling_Pickup
 */
class WPML_TP_Polling_Pickup {

	/** @var WPML_Pro_Translation $pro_translation */
	private $pro_translation;

	/** @var WPML_TM_CMS_ID $cms_id_helper */
	private $cms_id_helper;

	/**
	 * WPML_TP_Polling_Pickup constructor.
	 *
	 * @param WPML_Pro_Translation $pro_translation
	 * @param WPML_TM_CMS_ID       $cms_id_helper
	 */
	public function __construct( &$pro_translation, &$cms_id_helper ) {
		$this->pro_translation = &$pro_translation;
		$this->cms_id_helper   = &$cms_id_helper;
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	public function poll_job( $data ) {
		$job     = ! empty( $data['job_polled'] ) ? $data['job_polled'] : false;
		$results = array(
			'completed' => empty( $data['completed_jobs'] ) ? 0 : $data['completed_jobs'],
			'cancelled' => empty( $data['cancelled_jobs'] ) ? 0 : $data['cancelled_jobs'],
			'errors'    => empty( $data['error_jobs'] ) ? array() : $data['error_jobs']
		);
		if ( $job && in_array( $job['job_state'], array(
				'cancelled',
				'translation_ready',
				'delivered',
				'waiting_translation'
			) )
		) {
			$is_missing_in_db = ( ! empty( $job['cms_id'] )
			                      && ! $this->cms_id_helper->get_translation_id( $job['cms_id'] ) )
			                    || apply_filters( 'wpml_st_job_state_pending', false, $job );
			$job_state        = in_array( $job['job_state'], array(
				'delivered',
				'waiting_translation'
			),
				true ) && $is_missing_in_db
				? 'translation_ready' : $job['job_state'];
			if ( $job_state === 'translation_ready' || ( ! $is_missing_in_db && $job_state === 'cancelled' ) ) {
				$res = $this->pro_translation->xmlrpc_updated_job_status_with_log( array(
					$job['id'],
					$job['cms_id'],
					$job_state,
				), true );
			} else {
				$res = 0;
			}
			if ( $res === 1 ) {
				if ( $job['job_state'] === 'translation_ready' ) {
					$results['completed'] ++;
				} elseif ( $job['job_state'] === 'cancelled' ) {
					$results['cancelled'] ++;
				}
			} else {
				$results['errors']   = (array) $results['errors'];
				$results['errors'][] = $res;
			}
		}
		$errors           = "";
		$status_cancelled = "";

		if ( ! empty( $results['errors'] ) ) {
			$status    = __( 'Error', 'sitepress' );
			$errors    = join( '<br />', array_filter( (array) $results['errors'] ) );
			$job_error = true;
		} else {
			$status    = __( 'OK', 'sitepress' );
			$job_error = false;
		}
		if ( $results['completed'] == 1 ) {
			$status_completed = __( '1 translation has been fetched from the translation service.', 'sitepress' );
		} elseif ( $results['completed'] > 1 ) {
			$status_completed = sprintf( __( '%d translations have been fetched from the translation service.', 'sitepress' ), $results['completed'] );
		} else {
			$status_completed = '';
		}
		if ( $results['cancelled'] ) {
			$status_cancelled = sprintf( __( '%d translations have been marked as cancelled.', 'sitepress' ), $results['cancelled'] );
		}

		return array(
			'job_error' => $job_error,
			'status'    => $status,
			'errors'    => $errors,
			'completed' => $status_completed,
			'cancelled' => $status_cancelled,
		);
	}
}