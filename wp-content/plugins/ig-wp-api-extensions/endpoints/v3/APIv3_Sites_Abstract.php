<?php

/**
 * Retrieve the multisites defined in this network
 */
abstract class APIv3_Sites_Abstract extends APIv3_Base_Abstract {

	public function register_routes($namespace) {
		parent::register_route($namespace, static::ROUTE, 'get_sites');
	}

	public function get_sites() {
		global $wpdb;
		$sites = [];
		foreach (get_sites() as $site) {
			switch_to_blog($site->blog_id);
			$disabled = $wpdb->get_row("SELECT {$wpdb->prefix}ig_settings_config.value FROM {$wpdb->base_prefix}ig_settings LEFT JOIN {$wpdb->prefix}ig_settings_config ON {$wpdb->base_prefix}ig_settings.id = {$wpdb->prefix}ig_settings_config.setting_id WHERE {$wpdb->base_prefix}ig_settings.alias = 'disabled'")->value;
			$hidden = $wpdb->get_row("SELECT {$wpdb->prefix}ig_settings_config.value FROM {$wpdb->base_prefix}ig_settings LEFT JOIN {$wpdb->prefix}ig_settings_config ON {$wpdb->base_prefix}ig_settings.id = {$wpdb->prefix}ig_settings_config.setting_id WHERE {$wpdb->base_prefix}ig_settings.alias = 'hidden'")->value;
			if (!$hidden && (static::LIVE XOR $disabled)) {
				$sites[] = $this->prepare($site);
			}
			restore_current_blog();
		}
		return $sites;
	}

	private function prepare(WP_Site $site) {
		global $wpdb;
		$result = [
			'id' => (int) $site->blog_id,
			'name' => get_blog_details($site->blog_id)->blogname,
			'icon' => get_site_icon_url(),
			'cover_image' => get_header_image(),
			'color' => '#FFA000',
			'path' => $site->path,
			'description' => get_bloginfo('description'),
		];
		foreach ($wpdb->get_results("SELECT * FROM {$wpdb->base_prefix}ig_settings JOIN {$wpdb->prefix}ig_settings_config ON {$wpdb->base_prefix}ig_settings.id = {$wpdb->prefix}ig_settings_config.setting_id") as $setting) {
			if (!in_array($setting->alias, ['disabled', 'hidden'])) {
				$result[$setting->alias] = ($setting->type === 'bool' ? (bool) $setting->value : (ctype_digit($setting->value) ? (int) $setting->value : $setting->value));
			}
		}
		return $result;
	}

}
