<?php

/**
 * @package wpml-core
 * @subpackage wpml-user-language
 */
class WPML_User_Language extends WPML_SP_User {
	private $language_changes_history       = array();
	private $admin_language_changes_history = array();
	private $language_switched              = false;

	public function __construct( &$sitepress ) {
		parent::__construct( $sitepress );

		$this->language_changes_history[] = $sitepress->get_current_language();

		$this->admin_language_changes_history[] = $this->sitepress->get_admin_language();

		$this->register_hooks();
	}

	public function register_hooks() {
		add_action( 'wpml_switch_language_for_email', array( $this, 'switch_language_for_email_action' ), 10, 1 );
		add_action( 'wpml_restore_language_from_email', array( $this, 'restore_language_from_email_action' ), 10, 0 );
	}

	public function switch_language_for_email_action( $email ) {
		$this->switch_language_for_email( $email );
	}

	private function switch_language_for_email( $email ) {
		$language = apply_filters( 'wpml_user_language', null, $email );

		if ( $language ) {
			$this->language_switched                = true;
			$this->language_changes_history[]       = $language;
			$this->admin_language_changes_history[] = $language;

			$this->sitepress->switch_lang( $language, true );

			$this->sitepress->set_admin_language( $language );
		}
	}

	public function restore_language_from_email_action() {
		$this->wpml_restore_language_from_email();
	}

	private function wpml_restore_language_from_email() {
		if ( $this->language_switched ) {
			$this->language_switched = false;

			$this->sitepress->switch_lang( $this->language_changes_history[0], true );

			$this->sitepress->set_admin_language( $this->admin_language_changes_history[0] );
		}
	}
}
