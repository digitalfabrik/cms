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

	private $db_version = '1.0';
	private $actions = [
		'settings' => 'Settings',
		'create_setting' => 'Create Setting',
		'edit_setting' => 'Edit Setting',
		'toggle_extras' => 'Toggle Extras',
		'create_extra' => 'Create Extra',
		'edit_extra' => 'Edit Extra'
	];
	const MENU_SLUG = 'integreat-settings';

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

		if (isset($_SESSION['ig-admin-notices'])) {
			foreach ($_SESSION['ig-admin-notices'] as $admin_notice) {
				trigger_error($admin_notice['message']);
			}
		}

		*/
	}

	public function run() {
		if (is_file(__DIR__ . '/../css/styles.css')) {
			echo '<style>' . file_get_contents(__DIR__ . '/../css/styles.css') . '</style>';
		}
		if (isset($_GET['action']) && array_key_exists($_GET['action'], $this->actions)) {
			$action = $_GET['action'];
		} else {
			$action = 'settings';
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
		if (isset($_SESSION['ig-admin-notices'])) {
			foreach ($_SESSION['ig-admin-notices'] as $admin_notice) {
				echo '<div class="notice notice-' . $admin_notice['type'] . ' is-dismissible"><p><strong>' . $admin_notice['message'] . '</strong></p></div>';
			}
		}
		echo '<h1>Integreat Settings</h1>
		<h2 class="nav-tab-wrapper">';
		foreach ($this->actions as $alias => $name) {
			echo '<a href="' . parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) . '?page=' . self::MENU_SLUG . '&action=' . $alias . '" class="nav-tab' . ($action === $alias ? ' nav-tab-active' : '') . '">' . $name . '</a>';
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
				if (isset($_SESSION['ig-current-setting'])) {
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
				if (isset($_SESSION['ig-current-extra'])) {
					echo IntegreatExtra::form('extra');
				}
				break;
		}
	}

}