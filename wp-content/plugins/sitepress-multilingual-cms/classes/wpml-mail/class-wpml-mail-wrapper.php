<?php

/**
 * @package wpml-mail
 */
class WPML_Mail_Wrapper {
	static $language_changes_history = array();
	static $language_switched        = false;

	public function __construct() {
		self::$language_changes_history[] = apply_filters( 'wpml_current_language', null );
		$this->register_hooks();
	}

	public function register_hooks() {
		add_action( 'wpml_switch_language_for_mailing', array( $this, 'wpml_switch_language_for_mailing_action' ), 10, 1 );
		add_action( 'wpml_reset_language_after_mailing', array( $this, 'wpml_reset_language_after_mailing_action' ), 10, 0 );
	}

	public function wpml_switch_language_for_mailing_action( $email ) {
		$this->wpml_switch_language_for_mailing( $email );
	}

	private function wpml_switch_language_for_mailing( $email ) {
		$language = apply_filters( 'wpml_email_language', null, $email );

		if ( $language ) {
			self::$language_switched          = true;
			self::$language_changes_history[] = $language;

			do_action( 'wpml_switch_language', $language );
		}
	}

	public function wpml_reset_language_after_mailing_action() {
		$this->wpml_reset_language_after_mailing();
	}

	private function wpml_reset_language_after_mailing() {
		if ( self::$language_switched ) {
			self::$language_switched = false;

			do_action( 'wpml_switch_language', self::$language_changes_history[0] );
		}
	}
}
