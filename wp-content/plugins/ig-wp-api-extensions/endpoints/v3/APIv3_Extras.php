<?php

class APIv3_Extras extends APIv3_Base_Abstract {

	const ROUTE = 'extras';

	public function get_extras() {
		global $wpdb;
		if (class_exists('IntegreatSettingsPlugin')) {
			$extras = $wpdb->get_results(
				"SELECT *
					FROM {$wpdb->base_prefix}ig_extras
						AS extras
					JOIN {$wpdb->prefix}ig_extras_config
						AS config
						ON extras.id = config.extra_id
					WHERE config.enabled = true");
			return array_map([$this, 'prepare'], $extras);
		} else {
			// throw error if IntegreatSettingsPlugin is not activated
			return new WP_Error('settings_plugin_not_activated', 'The Plugin "Integreat Settings" is not activated for this location', ['status' => 501]);
		}
	}

	public function prepare($extra) {
		global $wpdb;
		$location = str_replace([' ', 'ä', 'ö', 'ü'], ['-', 'ae', 'oe', 'ue'], strtolower($wpdb->get_var(
			"SELECT value
				FROM {$wpdb->base_prefix}ig_settings
					AS settings
				JOIN {$wpdb->prefix}ig_settings_config
					AS config
					ON settings.id = config.setting_id
				WHERE settings.alias = 'name_without_prefix'")));
		$plz = $wpdb->get_var(
			"SELECT value
				FROM {$wpdb->base_prefix}ig_settings
					AS settings
				JOIN {$wpdb->prefix}ig_settings_config
					AS config
					ON settings.id = config.setting_id
				WHERE settings.alias = 'plz'");
		return [
			'name' => $extra->name,
			'alias' => $extra->alias,
			'url' => str_replace(['{location}', '{plz}'], [$location, $plz], $extra->url),
			'post' => json_decode(str_replace(['{location}', '{plz}'], [$location, $plz], $extra->post)),
			'thumbnail' => $extra->thumbnail
		];
	}

}
