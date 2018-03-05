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
			$disabled = $wpdb->get_row(
				"SELECT value
					FROM {$wpdb->base_prefix}ig_settings
						AS settings
					LEFT JOIN {$wpdb->prefix}ig_settings_config
						AS config
						ON settings.id = config.setting_id
					WHERE settings.alias = 'disabled'");
			if (!(isset($disabled->value) && $disabled->value) && (static::LIVE XOR (!$site->public OR $site->spam OR $site->deleted OR $site->archived OR $site->mature))) {
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
		$query =
			"SELECT *
				FROM {$wpdb->base_prefix}ig_settings
					AS settings
				LEFT JOIN {$wpdb->prefix}ig_settings_config
					AS config
					ON settings.id = config.setting_id";
		foreach ($wpdb->get_results($query) as $setting) {
			if (!in_array($setting->alias, ['disabled', 'hidden'])) {
				$result[$setting->alias] = ($setting->type === 'bool' ? (bool) $setting->value : (ctype_digit($setting->value) ? (int) $setting->value : $setting->value));
			}
		}
		return $result;
	}

}
