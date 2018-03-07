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
	private $db_version = '1.0';
	public static $admin_notices = [];

	public function activate($network_wide) {
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
		add_option('ig_extras_db_version', $this->db_version);
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

	public function admin_menu() {
		$this->run(false);
	}

	public function network_admin_menu() {
		$this->run(true);
	}

	public function run($network_wide) {
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

}