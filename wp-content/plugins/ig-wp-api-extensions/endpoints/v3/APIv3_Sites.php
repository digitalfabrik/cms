<?php

/**
 * Retrieve the multisites defined in this network
 */
class APIv3_Sites extends APIv3_Base_Abstract {

	const ROUTE = 'sites';

	public function get_sites() {
		return array_map([$this, 'prepare'], array_filter(get_sites(), function ($site) {
			return !$this->is_disabled($site);
		}));
	}

	public function prepare(WP_Site $site) {
		global $wpdb;
		switch_to_blog($site->blog_id);
		$result = [
			'id' => (int) $site->blog_id,
			'name' => get_blog_details($site->blog_id)->blogname,
			'icon' => get_site_icon_url(),
			'cover_image' => get_header_image(),
			'color' => '#FFA000',
			'path' => $site->path,
			'description' => get_bloginfo('description'),
		];
		if (class_exists('IntegreatSettingsPlugin')) {
			$settings = $wpdb->get_results(
				"SELECT *
					FROM {$wpdb->base_prefix}ig_settings
						AS settings
					LEFT JOIN {$wpdb->prefix}ig_settings_config
						AS config
						ON settings.id = config.setting_id");
			foreach ($settings as $setting) {
				// ignore disabled setting because it is always false for the returned sites
				if ($setting->alias === 'disabled') {
					continue;
				}
				if ($setting->alias === 'hidden') {
					// only return "hidden"-setting if enpoint is "sites"
					if (static::ROUTE === 'sites') {
						$result['live'] = !$this->is_hidden($site);
					}
				} else {
					$result[$setting->alias] = ($setting->type === 'bool' ? (bool) $setting->value : (ctype_digit($setting->value) ? (int) $setting->value : ($setting->value === '' ? null : $setting->value)));
				}
			}
			$result['extras'] = (bool) $wpdb->get_var("SELECT enabled FROM {$wpdb->prefix}ig_extras_config WHERE enabled = true");
		}
		restore_current_blog();
		return $result;
	}

	protected function is_disabled(WP_Site $site) {
		if (class_exists('IntegreatSettingsPlugin')) {
			global $wpdb;
			switch_to_blog($site->blog_id);
			$disabled = $wpdb->get_var(
				"SELECT value
						FROM {$wpdb->base_prefix}ig_settings
							AS settings
						JOIN {$wpdb->prefix}ig_settings_config
							AS config
							ON settings.id = config.setting_id
						WHERE settings.alias = 'disabled'");
			restore_current_blog();
			return $disabled;
		} else {
			return false;
		}
	}

	protected function is_hidden(WP_Site $site) {
		return (!$site->public || $site->spam || $site->deleted || $site->archived || $site->mature);
	}

}
