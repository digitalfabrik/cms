<?php

class APIv3_Extras extends APIv3_Base_Abstract {

	const ROUTE = 'extras';

	public function register_routes($namespace) {
		parent::register_route($namespace, self::ROUTE, 'get_extras');
	}

	public function get_extras() {
		global $wpdb;
		$extras = [];
		foreach ($wpdb->get_results("SELECT * FROM {$wpdb->base_prefix}ig_extras JOIN {$wpdb->prefix}ig_extras_config ON {$wpdb->base_prefix}ig_extras.id = {$wpdb->prefix}ig_extras_config.extra_id") as $extra) {
			$extras[] = $this->prepare($extra);
		}
		return $extras;
	}

	private function prepare($extra) {
		global $wpdb;
		$location = strtolower($wpdb->get_row("SELECT {$wpdb->prefix}ig_settings_config.value FROM {$wpdb->base_prefix}ig_settings JOIN {$wpdb->prefix}ig_settings_config ON {$wpdb->base_prefix}ig_settings.id = {$wpdb->prefix}ig_settings_config.setting_id WHERE {$wpdb->base_prefix}ig_settings.alias = 'name_without_prefix'")->value);
		$plz = $wpdb->get_row("SELECT {$wpdb->prefix}ig_settings_config.value FROM {$wpdb->base_prefix}ig_settings JOIN {$wpdb->prefix}ig_settings_config ON {$wpdb->base_prefix}ig_settings.id = {$wpdb->prefix}ig_settings_config.setting_id WHERE {$wpdb->base_prefix}ig_settings.alias = 'plz'")->value;
		return [
			'name' => $extra->name,
			'alias' => $extra->alias,
			'url' => str_replace(['{location}', '{plz}'], [$location, $plz], $extra->url),
			'post' => json_decode(str_replace(['{location}', '{plz}'], [$location, $plz], $extra->post)),
			'thumbnail' => $extra->thumbnail,
			'enabled' => (bool) $extra->enabled
		];
	}

}
