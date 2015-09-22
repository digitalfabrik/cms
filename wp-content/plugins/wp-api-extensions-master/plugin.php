<?php
/**
 * Plugin Name: WP API Extensions
 * Description: Collection of extensions to the Wordpress REST API (WP API)
 * Version: 0.1
 * Author: Martin Schrimpf
 * Author URI: https://github.com/Meash
 * License: MIT
 */

require_once __DIR__ . '/endpoints/RestApi_ModifiedContent.php';
require_once __DIR__ . '/endpoints/RestApi_WpmlLanguages.php';
require_once __DIR__ . '/endpoints/RestApi_Multisites.php';

const API_VERSION = 0;
const ROOT_URL = 'extensions';

add_action('rest_api_init', function () {
	$base_url = ROOT_URL . '/v' . API_VERSION;
	$endpoints = [
		new RestApi_ModifiedContent($base_url),
		new RestApi_WpmlLanguages($base_url),
		new RestApi_Multisites($base_url)
	];
	foreach ($endpoints as $endpoint) {
		$endpoint->register_routes();
	}
});
