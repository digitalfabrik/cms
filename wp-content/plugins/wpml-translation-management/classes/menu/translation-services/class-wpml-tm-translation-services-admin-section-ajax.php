<?php

class WPML_TM_Translation_Services_Admin_Section_Ajax {

	const NONCE_ACTION = 'translation_service_toggle';

	public function add_hooks() {
		add_action( 'wp_ajax_translation_service_toggle', array( $this, 'translation_service_toggle' ) );
	}

	public function translation_service_toggle( ) {
		if ( $this->is_valid_request() ) {

			if ( ! isset( $_POST[ 'service_id' ] ) ) {
				return;
			}

			$service_id = (int) filter_var( $_POST[ 'service_id' ], FILTER_SANITIZE_NUMBER_INT );
			$enable = false;
			$response = false;

			if ( isset( $_POST[ 'enable' ] ) ) {
				$enable = filter_var( $_POST[ 'enable' ], FILTER_SANITIZE_NUMBER_INT );
			}

			if ( $enable && $service_id !== TranslationProxy::get_current_service_id() ) {
				$response = $this->activate_service( $service_id );
			}

			if ( ! $enable && $service_id === TranslationProxy::get_current_service_id() ) {
				$response = $this->deactivate_service();
			}

			wp_send_json_success( $response );
		} else {
			$response = array(
				'message' => __( 'You are not allowed to perform this action.', 'wpml-translation-management' ),
				'reload'  => 0,
			);

			wp_send_json_error( $response );
		}
	}

	/**
	 * @param int $service_id
	 *
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	private function activate_service( $service_id ) {
		$result  = TranslationProxy::select_service( $service_id );
		$message = '';
		if ( is_wp_error( $result ) ) {
			$message = $result->get_error_message();
		}

		return array(
			'message'   => $message,
			'reload'    => 1,
			'activated' => 1,
		);
	}

	private function deactivate_service() {
		TranslationProxy::deselect_active_service();

		return array(
			'message'   => '',
			'reload'    => 1,
			'activated' => 0,
		);
	}

	/**
	 * @return bool
	 */
	private function is_valid_request() {
		if ( ! isset( $_POST[ 'nonce' ] ) ) {
			return false;
		}

		return wp_verify_nonce( filter_var( $_POST[ 'nonce' ], FILTER_SANITIZE_FULL_SPECIAL_CHARS ), self::NONCE_ACTION );
	}
}