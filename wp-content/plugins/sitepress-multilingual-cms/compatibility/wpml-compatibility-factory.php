<?php

class WPML_Compatibility_Factory implements IWPML_Frontend_Action_Loader, IWPML_Backend_Action_Loader {

	public function create() {
		$hooks = array();

		$hooks['gutenberg'] = new WPML_Compatibility_Gutenberg( new WPML_WP_API() );

		return $hooks;
	}
}
