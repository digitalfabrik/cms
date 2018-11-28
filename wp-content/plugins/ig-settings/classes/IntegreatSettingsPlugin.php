<?php

/*
 * The main class for this plugin. It contains functions for activation and execution of the plugin.
 * It also handles the different actions provided by this plugin:
 *
 *     * settings: configure the different settings for each instance
 *     * create_setting: create a new setting field which is stored once and can be configured by each instance
 *     * edit_setting: edit a central setting object
 *     * toggle_extras: enable and disable the available extras for each instance
 *     * create_extra: create a new extra wich is stored once and can be en- or disabled by each instance
 *     * edit_extra: edit a central extra object
 *
 */

// abort if this file is called directly
if (!defined('WPINC')) {
	die;
}

class IntegreatSettingsPlugin {

	const MENU_SLUG = 'integreat-settings';
	const DB_VERSION = '1';
	const DELETE_DB_ON_DEACTIVATION = false;
	public static $admin_notices = [];

	public static function activate($network_wide) {
		$current_db_version = get_network_option(null, 'ig-settings-db-version');
		if (!$current_db_version) {
			add_network_option(null, 'ig-settings-db-version', self::DB_VERSION);
			$current_db_version = self::DB_VERSION;
		}
		global $wpdb;
		// global tables for extras and settings
		IntegreatSetting::create_table();
		IntegreatExtra::create_table();
		if ($network_wide) {
			// Get all blogs in the network and activate plugin on each one
			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);
				// local tables for configuration of extras and settings
				IntegreatSettingConfig::create_table();
				IntegreatExtraConfig::create_table();
				restore_current_blog();
			}
		} else {
			// local tables for configuration of extras and settings
			IntegreatSettingConfig::create_table();
			IntegreatExtraConfig::create_table();
		}
		if ($current_db_version < self::DB_VERSION) {
			// update database here if there are any changes in the future
			update_network_option(null, 'ig-settings-db-version', self::DB_VERSION);
		}
		/*
		 * Log admin notices if there are any messages in the session variable.
		 * Only uncomment for debugging purposes.

		if (isset(IntegreatSettingsPlugin::$admin_notices[])) {
			foreach (IntegreatSettingsPlugin::$admin_notices[] as $admin_notice) {
				trigger_error($admin_notice['message']);
			}
		}

		*/
	}

	public static function admin_menu() {
		self::run(false);
	}

	public static function network_admin_menu() {
		self::run(true);
	}

	public static function run($network_wide) {
		if (is_file(__DIR__ . '/../css/styles.css')) {
			echo '<style>' . file_get_contents(__DIR__ . '/../css/styles.css') . '</style>';
		}
		if ($network_wide) {
			$allowed_actions =  [
				'create_setting' => 'Create Setting',
				'edit_setting' => 'Edit Setting',
				'create_extra' => 'Create Extra',
				'edit_extra' => 'Edit Extra',
			];
			$default_action = 'create_setting';
		} else {
			$allowed_actions =  [
				'settings' => 'Settings',
				'toggle_extras' => 'Extras',
			];
			$default_action = 'settings';
		}
		if (isset($_GET['action']) && array_key_exists($_GET['action'], $allowed_actions)) {
			$action = $_GET['action'];
		} else {
			$action = $default_action;
		}
		if (isset($_GET['current_blog_id']) && get_blog_details((int) $_GET['current_blog_id']) !== false) {
			$current_blog_id = (int) $_GET['current_blog_id'];
		} else {
			$current_blog_id = get_current_blog_id();
		}
		switch ($action) {
			case 'settings':
				IntegreatSettingConfig::handle_request();
				break;
			case 'create_setting':
			case 'edit_setting':
				IntegreatSetting::handle_request();
				break;
			case 'toggle_extras':
				IntegreatExtraConfig::handle_request();
				break;
			case 'create_extra':
			case 'edit_extra':
				IntegreatExtra::handle_request();
				break;
		}
		// show admin notices if there are any messages in the session variable
		foreach (self::$admin_notices as $admin_notice) {
			echo '<div class="notice notice-' . $admin_notice['type'] . ' is-dismissible"><p><strong>' . $admin_notice['message'] . '</strong></p></div>';
		}
		echo '<h1>Integreat Settings</h1>
			<h2 class="nav-tab-wrapper">
				<a href="' . get_admin_url($current_blog_id, 'admin.php?page=' . self::MENU_SLUG) . '" class="nav-tab' . (!$network_wide ? ' nav-tab-active' : '') . '">Settings for ' . get_blog_details($current_blog_id)->blogname . '</a>
				<a href="' . network_admin_url('admin.php?page=' . self::MENU_SLUG) . '&current_blog_id='. $current_blog_id . '" class="nav-tab' . ($network_wide ? ' nav-tab-active' : '') . '">Settings for all Locations</a>
			</h2><br>
			<h2 class="nav-tab-wrapper">';
		foreach ($allowed_actions as $alias => $name) {
			echo '<a href="' . parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) . '?page=' . self::MENU_SLUG . '&action=' . $alias . '&current_blog_id='. $current_blog_id . '" class="nav-tab' . ($action === $alias ? ' nav-tab-active' : '') . '">' . $name . '</a>';
		}
		echo '</h2><br><br>';

		switch ($action) {
			case 'settings':
				echo IntegreatSettingConfig::form();
				break;
			case 'create_setting':
				echo IntegreatSetting::form('setting');
				break;
			case 'edit_setting':
				echo IntegreatSetting::form('select');
				if (IntegreatSetting::$current_setting) {
					echo IntegreatSetting::form('setting');
				}
				break;
			case 'toggle_extras':
				echo IntegreatExtraConfig::form();
				break;
			case 'create_extra':
				echo IntegreatExtra::form('extra');
				break;
			case 'edit_extra':
				echo IntegreatExtra::form('select');
				if (IntegreatExtra::$current_extra) {
					echo IntegreatExtra::form('extra');
				}
				break;
		}
	}

	public static function deactivate() {
		if (self::DELETE_DB_ON_DEACTIVATION) {
			global $wpdb;
			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);
				// local tables for configuration of extras and settings
				$wpdb->query('DROP TABLE IF EXISTS ' . IntegreatSettingConfig::get_table_name());
				$wpdb->query('DROP TABLE IF EXISTS ' . IntegreatExtraConfig::get_table_name());
				restore_current_blog();
			}
			// global tables for extras and settings
			$wpdb->query('DROP TABLE IF EXISTS ' . IntegreatSetting::get_table_name());
			$wpdb->query('DROP TABLE IF EXISTS ' . IntegreatExtra::get_table_name());
		}
	}

	public static function add_filters() {
		global $wpdb;
		/*
		 * Return all extras joined with the extra configurations
		 */
		add_filter('ig-extras', function ($only_enabled = false) use ($wpdb) {
			// fetch settings to enable the replacement of location-dependent content
			$settings = apply_filters('ig-settings', null);
			if (!$location = $settings['location_override']) {
				// replace umlaute to prevent wrong urls
				$location = str_replace([' ', 'ä', 'ö', 'ü'], ['-', 'ae', 'oe', 'ue'], strtolower(apply_filters('ig-settings-legacy', $settings, 'name_without_prefix')));
			}
			$plz = apply_filters('ig-settings-legacy', $settings, 'plz');
			// get all extras (or only the enabled ones if $only_enabled is true)
			$extras = $wpdb->get_results(
				"SELECT alias, name, url, post, thumbnail" . ($only_enabled ? "" : ", enabled") . "
					FROM {$wpdb->base_prefix}ig_extras
						AS extras
					LEFT JOIN {$wpdb->prefix}ig_extras_config
						AS config
						ON extras.id = extra_id"
				. ($only_enabled ? " WHERE enabled" : ""), OBJECT_K);
			// fields for Wohnraumbörse
			$wb_name = isset($settings['wb_name']) ? $settings['wb_name'] : 'Wohnraumbörse';
			$wb_url = isset($settings['wb_url']) ? $settings['wb_url'] : '';
			$wb_api = isset($settings['wb_api']) ? $settings['wb_api'] : '';
			$wb_thumb = isset($settings['wb_thumb']) ? $settings['wb_thumb'] : '';
			// filter all location-dependent content by replacing the placeholders
			return array_map(function ($extra) use ($location, $plz, $wb_name, $wb_url, $wb_api, $wb_thumb) {
				$extra->name = str_replace('Wohnraumbörse', $wb_name, $extra->name);
				$extra->url = str_replace(['{location}', '{plz}', '{wb_url}'], [$location, $plz, $wb_url], $extra->url);
				$extra->post = json_decode(str_replace(['{location}', '{plz}', '{wb_api}'], [$location, $plz, $wb_api], $extra->post));
				$extra->thumbnail = str_replace('{wb_thumb}', $wb_thumb, $extra->thumbnail);
				return $extra;
				}, $extras
			);
		}, 10, 1);
		/*
		 * Return all settings joined with the setting configurations
		 */
		add_filter('ig-settings', function () use ($wpdb) {
			// get all settings
			return array_map(function ($setting) {
				// cast setting value by its desired type
				return ($setting->type === 'bool' ? (bool) $setting->value : ($setting->value === '' ? null : $setting->value));
			}, $wpdb->get_results(
				"SELECT alias, type, value
					FROM {$wpdb->base_prefix}ig_settings
						AS settings
					LEFT JOIN {$wpdb->prefix}ig_settings_config
						AS config
						ON settings.id = setting_id", OBJECT_K));
		}, 10, 0);
		/*
		 * Return all settings relevant for APIv3 (joined with the setting configurations)
		 */
		add_filter('ig-settings-api', function () use ($wpdb) {
			// get all settings and union it with an additional extra setting which is true if at least one extra is enabled
			return array_map(function ($setting) {
				// cast setting value by its desired type
				if ($setting->type === 'bool') {
					return (bool) $setting->value;
				} elseif ($setting->type === 'json') {
					return json_decode($setting->value);
				} else {
					return ($setting->value === '' ? null : $setting->value);
				}
			}, $wpdb->get_results(
				"SELECT alias, type, value
					FROM {$wpdb->base_prefix}ig_settings
						AS settings
					LEFT JOIN {$wpdb->prefix}ig_settings_config
						AS config
						ON settings.id = setting_id
					WHERE
						alias = 'prefix' OR
						alias = 'name_without_prefix' OR
						alias = 'plz' OR
						alias = 'events' OR
						alias = 'push-notifications' OR
						alias = 'city-aliases' OR
						alias = 'longitude' OR
						alias = 'latitude'
				UNION SELECT 'extras', 'bool', (SELECT enabled FROM {$wpdb->prefix}ig_extras_config WHERE enabled LIMIT 1)", OBJECT_K));
		}, 10, 0);
		/*
		 * Take an array of settings and a setting alias and return the settings value as string
		 */
		add_filter('ig-settings-legacy', function ($settings, $setting) {
			if (isset($settings[$setting]) && $settings[$setting]) {
				return (string) $settings[$setting];
			} else {
				return '0';
			}
		}, 10, 2);
		/*
		 * Take an array of extras and an extra alias and return the json-object if $url == true and otherwise the extra state
		 */
		add_filter('ig-extras-legacy', function ($extras, $extra, $url = false) {
			if (isset($extras[$extra]) && $extras[$extra]->enabled) {
				if ($url && isset($extras[$extra]->url)) {
					return json_encode([
						'enabled' => 1,
						'url' => $extras[$extra]->url
					], JSON_UNESCAPED_SLASHES);
				} else {
					return '1';
				}
			} else {
				return '0';
			}
		}, 10, 3);
		/*
		 * Return whether a site is completely disabled in API
		 */
		add_filter('ig-site-disabled', function ($site) use ($wpdb) {
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
			return (bool) $disabled;
		}, 10, 1);
	}

	public static function encode_quotes_deep($object) {
		return self::code_quotes_deep($object, true);
	}

	public static function decode_quotes_deep($object) {
		return self::code_quotes_deep($object, false);
	}

	private static function code_quotes_deep($object, $encode) {
		if ($object === []) {
			return [];
		} else {
			return (object) array_map(function ($attribute) use ($encode) {
				if (is_string($attribute)) {
					if ($encode) {
						return str_replace('"', '&quot;', $attribute);
					} else {
						return str_replace('&quot;', '"', $attribute);
					}
				} else {
					return $attribute;
				}
			}, (array)$object);
		}
	}

}
