<?php

class APIv3_Extras extends APIv3_Base_Abstract {

	const ROUTE = 'extras';

	public function get_extras() {
		if (class_exists('IntegreatSettingsPlugin')) {
			return array_values(apply_filters('ig-extras', true));
		} else {
			// throw error if IntegreatSettingsPlugin is not activated
			return new WP_Error('settings_plugin_not_activated', 'The Plugin "Integreat Settings" is not activated for this location', ['status' => 501]);
		}
	}

}
