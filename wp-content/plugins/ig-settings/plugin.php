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

// instantiate plugin-class
$IntegreatSettingsPlugin = new IntegreatSettingsPlugin();

// execute $IntegreatSettingsPlugin->activate() on activating the plugin
register_activation_hook(
	__FILE__,
	[
		$IntegreatSettingsPlugin,
		'activate'
	]
);

// add the plugin to the admin menu and execute $IntegreatSettingsPlugin->run() on opening the integreat settings page
add_action(
	'admin_menu',
	function () use ($IntegreatSettingsPlugin) {
		add_options_page(
			'Integreat Settings',
			'Integreat Settings',
			'manage_network', // only run plugin if current user is super admin
			$IntegreatSettingsPlugin::MENU_SLUG,
			[
				$IntegreatSettingsPlugin,
				'run'
			]
		);
	}
);
