<?php

/**
 * @package wpml-mail
 */
class WPML_Mail_Languages_Helper {
	function sanitize_language_code( $code ) {
		if ( $code ) {
			$code = trim( $code );
			if ( strlen( $code ) < 2 ) {
				return false;
			}
			if ( strlen( $code ) > 2 ) {
				$code = substr( $code, 0, 2 );
			}
			$code = strtolower( $code );
		}

		return $code;
	}

	function is_front_end_request() {
		return $this->request_is_frontend_ajax() || ! is_admin();
	}

	function request_is_frontend_ajax() {
		// http://snippets.khromov.se/determine-if-wordpress-ajax-request-is-a-backend-of-frontend-request/
		$script_filename = isset( $_SERVER['SCRIPT_FILENAME'] ) ? $_SERVER['SCRIPT_FILENAME'] : '';

		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$ref = '';
			if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
				$ref = wp_unslash( $_REQUEST['_wp_http_referer'] );
			} elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
				$ref = wp_unslash( $_SERVER['HTTP_REFERER'] );
			}

			if ( ( ( strpos( $ref, admin_url() ) === false ) && ( basename( $script_filename ) === 'admin-ajax.php' ) ) ) {
				return true;
			}
		}

		return false;
	}

	function get_language_from_usermeta( $email ) {
		$language = false;
		$user     = get_user_by( 'email', $email );
		if ( $user && isset( $user->ID ) ) {
			$language = get_user_meta( $user->ID, 'icl_admin_language', true );
		}

		return $this->sanitize_language_code( $language );
	}
}
