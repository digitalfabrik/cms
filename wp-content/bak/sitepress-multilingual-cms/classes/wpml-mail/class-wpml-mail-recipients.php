<?php

/**
 * @package wpml-mail
 */
class WPML_Mail_Recipients {
	private $WPML_Mail_Languages_Helper;

	/**
	 * @param WPML_Mail_Languages_Helper $WPML_Mail_Languages_Helper
	 */
	public function __construct( &$WPML_Mail_Languages_Helper ) {
		$this->WPML_Mail_Languages_Helper = &$WPML_Mail_Languages_Helper;
		$this->register_hooks();
	}

	public function register_hooks() {
		add_filter( 'wpml_email_language', array( $this, 'wpml_email_language_filter' ), 10, 2 );
	}

	public function wpml_email_language_filter( $language, $email ) {
		return $this->wpml_email_language( $language, $email );
	}

	private function wpml_email_language( $language, $email ) {
		$language_in_db = $this->get_recipient_language( $email );
		if ( $language_in_db ) {
			$language = $language_in_db;
		}

		return $this->WPML_Mail_Languages_Helper->sanitize_language_code( $language );
	}

	private function get_recipient_language( $email ) {
		$language = apply_filters( 'wpml_mail_recipient_language', null, $email );

		if ( ! $language && is_email( $email ) ) {
			$language = $this->get_language_from_globals();
			if ( ! $language ) {
				$language = $this->get_language_from_tables( $email );
				if ( ! $language ) {
					$language = $this->get_language_from_fallbacks();
				}
			}
		}

		return $this->WPML_Mail_Languages_Helper->sanitize_language_code( $language );
	}

	private function get_language_from_globals() {
		$lang = filter_input( INPUT_POST, 'wpml_mail_recipient_language', FILTER_SANITIZE_SPECIAL_CHARS );
		if ( ! $lang ) {
			$lang = filter_input( INPUT_GET, 'wpml_mail_recipient_language', FILTER_SANITIZE_SPECIAL_CHARS );
			if ( ! $lang && isset( $GLOBALS['wpml_mail_recipient_language'] ) ) {
				$lang = $GLOBALS['wpml_mail_recipient_language'];
			}
		}

		return $this->WPML_Mail_Languages_Helper->sanitize_language_code( $lang );
	}

	private function get_language_from_tables( $email ) {
		$lang = $this->get_language_from_usermeta( $email );

		return $this->WPML_Mail_Languages_Helper->sanitize_language_code( $lang );
	}

	private function get_language_from_usermeta( $email ) {
		return $this->WPML_Mail_Languages_Helper->get_language_from_usermeta( $email );
	}

	private function get_language_from_fallbacks() {

		$lang = get_option( 'wpml_mail_recipient_language' );
		if ( ! $lang ) {

			if ( $this->WPML_Mail_Languages_Helper->is_front_end_request() ) {
				$lang = apply_filters( 'wpml_current_language', null );
			} else {
				$lang = apply_filters( 'wpml_default_language', null );
			}

			if ( ! $lang ) {
				$lang = get_locale(); // will return xx_XX but will be normalized in return line
				if ( ! $lang ) {
					$lang = 'en';
				}
			}
		}

		return $this->WPML_Mail_Languages_Helper->sanitize_language_code( $lang );
	}
}
