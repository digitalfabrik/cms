<?php

class APIv3_Extras extends APIv3_Base_Abstract {

	const ROUTE = 'extras';

	public function register_routes($namespace) {
		parent::register_route($namespace, self::ROUTE, 'get_extras');
	}

	public function get_extras() {
		global $wpdb;
		$extras = [];
		if (class_exists('IntegreatSettingsPlugin')) {
			$query =
				"SELECT *
					FROM {$wpdb->base_prefix}ig_extras
						AS extras
					JOIN {$wpdb->prefix}ig_extras_config
						AS config
						ON extras.id = config.extra_id";
			foreach ($wpdb->get_results($query) as $extra) {
				$extras[] = $this->prepare($extra);
			}
		} else {
			// fallback if IntegreatSettingsPlugin is not activated
			foreach ($wpdb->get_results("SELECT * FROM $wpdb->options WHERE option_name LIKE 'ige-%'") as $extra) {
				$extras[] = $this->prepare($extra);
			}
		}
		return $extras;
	}

	private function prepare($extra) {
		if (class_exists('IntegreatSettingsPlugin')) {
			global $wpdb;
			$location = strtolower($wpdb->get_row(
				"SELECT value
					FROM {$wpdb->base_prefix}ig_settings
						AS settings
					JOIN {$wpdb->prefix}ig_settings_config
						AS config
						ON settings.id = config.setting_id
					WHERE settings.alias = 'name_without_prefix'")->value);
			$plz = $wpdb->get_row(
				"SELECT value
					FROM {$wpdb->base_prefix}ig_settings
						AS settings
					JOIN {$wpdb->prefix}ig_settings_config
						AS config
						ON settings.id = config.setting_id
					WHERE settings.alias = 'plz'")->value;
			return [
				'name' => $extra->name,
				'alias' => $extra->alias,
				'url' => str_replace(['{location}', '{plz}'], [$location, $plz], $extra->url),
				'post' => json_decode(str_replace(['{location}', '{plz}'], [$location, $plz], $extra->post)),
				'thumbnail' => $extra->thumbnail,
				'enabled' => (bool) $extra->enabled
			];
		} else {
			// fallback if IntegreatSettingsPlugin is not activated
			$extra_value = json_decode($extra->option_value, true);
			if (!is_array($extra_value)) {
				if ($extra_value === '1') {
					$extra_value = [
						'enabled' => true
					];
				} else {
					$extra_value = [
						'enabled' => false
					];
				}
			} else {
				if (isset($extra_value['enabled'])) {
					$extra_value['enabled'] = (bool) $extra_value['enabled'];
				} else {
					$extra_value['enabled'] = false;
				}
			}
			return array_merge([
				'alias' => $extra->option_name
			], $extra_value);
		}
	}

}
