<?php

/**
 * Class WPML_Media_Edit_Hooks_Factory
 */
class WPML_Media_Edit_Hooks_Factory extends WPML_Current_Screen_Loader_Factory {

	/** @return string */
	public function get_screen_regex() {
		return '/^attachment$/';
	}

	/** @return WPML_Media_Edit_Hooks */
	public function create_hooks() {
		/** @var WPML_URL_Converter $wpml_url_converter */
		global $wpml_url_converter;
		return new WPML_Media_Edit_Hooks( $wpml_url_converter );
	}
}
