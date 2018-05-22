<?php

/**
 * @author OnTheGo Systems
 */
class WPML_TM_AJAX {
	/**
	 * @return bool
	 */
	protected function is_valid_request() {
		if ( ! array_key_exists( 'nonce', $_POST ) || ! array_key_exists( 'action', $_POST )
		     || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), $_POST['action'] ) ) {

			wp_send_json_error( __( 'You have attempted to submit data in a not legit way.',
			                        'wpml-translation-management' ) );

			return false;
		}

		return true;
	}

}