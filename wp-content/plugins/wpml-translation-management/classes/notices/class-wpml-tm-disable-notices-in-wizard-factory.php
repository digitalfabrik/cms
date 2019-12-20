<?php

class WPML_TM_Disable_Notices_In_Wizard_Factory implements IWPML_Backend_Action_Loader, IWPML_Deferred_Action_Loader {

	const AFTER_INSTALLER_READY_ACTION = 'otgs_installer_initialized';

	/**
	 * Creates the instance.
	 *
	 * @return \IWPML_Action|\IWPML_Action[]|\WPML_TM_Disable_Notices_In_Wizard|null
	 */
	public function create() {
		global $sitepress;

		return new WPML_TM_Disable_Notices_In_Wizard( $sitepress->get_wp_api(), wpml_translation_management() );
	}

	/**
	 * It returns the action hook to use to defer the loading of the class.
	 *
	 * @return string
	 */
	public function get_load_action() {
		return self::AFTER_INSTALLER_READY_ACTION;
	}
}
