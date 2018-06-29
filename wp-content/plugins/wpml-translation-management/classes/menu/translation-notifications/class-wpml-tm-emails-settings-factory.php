<?php

class WPML_TM_Emails_Settings_Factory implements IWPML_Backend_Action_Loader {

	/**
	 * @return WPML_TM_Emails_Settings
	 */
	public function create() {
		global $iclTranslationManagement;

		$hooks = null;

		if ( isset( $_GET['sm'] ) && 'notifications' === filter_var( $_GET['sm'], FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) {
			$template_service = new WPML_Twig_Template_Loader( array( WPML_TM_PATH . '/templates/settings' ) );
			$hooks = new WPML_TM_Emails_Settings( $template_service->get_template(), $iclTranslationManagement );
		}

		return $hooks;
	}
}