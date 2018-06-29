<?php

/**
 * Plugin Name: Integreat Settings
 * Description: Provides better UI for App Content Settings
 * Version: 1.0
 * Author: Timo Ludwig
 * Author URI: https://github.com/timoludwig
 * License: MIT
 */

// include all classes which are necessary for the plugin
require_once __DIR__ . '/classes/IntegreatExtra.php';
require_once __DIR__ . '/classes/IntegreatExtraConfig.php';
require_once __DIR__ . '/classes/IntegreatSetting.php';
require_once __DIR__ . '/classes/IntegreatSettingConfig.php';
require_once __DIR__ . '/classes/IntegreatSettingsPlugin.php';

// execute $IntegreatSettingsPlugin->activate() on activating the plugin
register_activation_hook(
	__FILE__,
	[
		'IntegreatSettingsPlugin',
		'activate'
	]
);

register_deactivation_hook(
	__FILE__,
	[
		'IntegreatSettingsPlugin',
		'deactivate'
	]
);

// add the plugin to the admin menu and execute $IntegreatSettingsPlugin->admin_menu() on opening the integreat settings page
add_action(
	'admin_menu',
	function () {
		add_menu_page(
			'Integreat Settings',
			'Integreat Settings',
			'manage_network', // only run plugin if current user is super admin
			IntegreatSettingsPlugin::MENU_SLUG,
			[
				'IntegreatSettingsPlugin',
				'admin_menu'
			],
			plugins_url('ig-settings/icon.png')
		);
	}
);

// add the plugin to the network admin menu and execute $IntegreatSettingsPlugin->network_admin_menu() on opening the integreat settings page
add_action(
	'network_admin_menu',
	function () {
		add_menu_page(
			'Integreat Settings',
			'Integreat Settings',
			'manage_network', // only run plugin if current user is super admin
			IntegreatSettingsPlugin::MENU_SLUG,
			[
				'IntegreatSettingsPlugin',
				'network_admin_menu'
			],
			plugins_url('ig-settings/icon.png')
		);
	}
);

IntegreatSettingsPlugin::add_filters();