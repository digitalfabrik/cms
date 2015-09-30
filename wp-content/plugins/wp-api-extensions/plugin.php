<?php
/**
 * Plugin Name: WP API Extensions
 * Description: Collection of extensions to the Wordpress REST API
 * Version: 0.1
 * Author: Martin Schrimpf
 * Author URI: https://github.com/Meash
 * License: MIT
 */

require_once __DIR__ . '/endpoints/RestApi_ModifiedContent.php';
require_once __DIR__ . '/endpoints/RestApi_WpmlLanguages.php';
require_once __DIR__ . '/endpoints/RestApi_Multisites.php';

const PLUGIN_NAMESPACE = 'extensions';
const API_VERSION = 0;

add_action('rest_api_init', function () {
	$versioned_namespace = PLUGIN_NAMESPACE . '/v' . API_VERSION;
	$endpoints = [
		new RestApi_ModifiedContent($versioned_namespace),
		new RestApi_WpmlLanguages($versioned_namespace),
		new RestApi_Multisites($versioned_namespace)
	];
	foreach ($endpoints as $endpoint) {
		$endpoint->register_routes();
	}
});
