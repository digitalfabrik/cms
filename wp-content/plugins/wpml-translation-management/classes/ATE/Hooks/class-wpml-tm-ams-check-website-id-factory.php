<?php

/**
 * @author OnTheGo Systems
 */

class WPML_TM_AMS_Check_Website_ID_Factory implements IWPML_Backend_Action_Loader {

	/**
	 * @return \WPML_TM_AMS_Check_Website_ID|null
	 * @throws \Auryn\InjectionException
	 */
	public function create() {
		$options_manager = \WPML\Container\make( '\WPML\WP\OptionManager' );
		if (
			WPML_TM_ATE_Status::is_enabled_and_activated() &&
			! wpml_is_ajax() &&
			! $options_manager->get( 'TM-has-run', 'WPML_TM_AMS_Check_Website_ID' )
		) {
			return \WPML\Container\make( '\WPML_TM_AMS_Check_Website_ID' );
		}
	}
}
