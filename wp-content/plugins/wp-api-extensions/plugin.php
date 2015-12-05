<?php
/**
 * Plugin Name: WP API Extensions
 * Description: Collection of extensions to the Wordpress REST API
 * Version: 0.1
 * Author: Martin Schrimpf
 * Author URI: https://github.com/Meash
 * License: MIT
 */

// version: current
require_once __DIR__ . '/endpoints/RestApi_Multisites.php';
require_once __DIR__ . '/endpoints/RestApi_WpmlLanguages.php';
require_once __DIR__ . '/endpoints/RestApi_ModifiedPages.php';
require_once __DIR__ . '/endpoints/RestApi_ModifiedEvents.php';
require_once __DIR__ . '/endpoints/RestApi_ModifiedDisclaimer.php';
// v0
require_once __DIR__ . '/endpoints/v0/RestApi_Multisites.php';

const API_NAMESPACE = 'extensions';
const CURRENT_VERSION = 1;

const ENDPOINT_MULTISITES = 'multisites';
const ENDPOINT_LANGUAGES = 'languages';
const ENDPOINT_PAGES = 'pages';
const ENDPOINT_EVENTS = 'events';
const ENDPOINT_DISCLAIMER = 'disclaimer';

add_action('rest_api_init', function () {
	/** @var RestApi_ExtensionBase[] $default_endpoints key -> endpoint */
	$default_endpoints = [
		ENDPOINT_MULTISITES => new RestApi_Multisites(),
		ENDPOINT_LANGUAGES => new RestApi_WpmlLanguages(),
		ENDPOINT_PAGES => new RestApi_ModifiedPages(),
		ENDPOINT_EVENTS => new RestApi_ModifiedEvents(),
		ENDPOINT_DISCLAIMER => new RestApi_ModifiedDisclaimer(),
	];
	/** API version -> [key -> endpoint] */
	$versioned_endpoints = [
		0 => [
			ENDPOINT_MULTISITES => new RestApi_MultisitesV0(),
		],
		CURRENT_VERSION => []
	];

	// register versioned endpoints
	foreach ($versioned_endpoints as $version => $version_endpoints) {
		$versioned_namespace = API_NAMESPACE . '/v' . $version;
		/** @var RestApi_ExtensionBase[] $endpoints */
		$endpoints = array_merge($default_endpoints, $version_endpoints);
		foreach ($endpoints as $endpoint) {
			$endpoint->register_routes($versioned_namespace);
		}
	}
	// register current version endpoints without version number
	foreach ($default_endpoints as $endpoint) {
		$endpoint->register_routes(API_NAMESPACE);
	}
});
