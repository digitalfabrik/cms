<?php
/**
 * Plugin Name: WP API Extensions
 * Description: Collection of extensions to the Wordpress REST API
 * Version: 0.1
 * Author: Martin Schrimpf
 * Author URI: https://github.com/Meash
 * License: MIT
 */

// v0
require_once __DIR__ . '/endpoints/v0/RestApi_Multisites.php';
require_once __DIR__ . '/endpoints/v0/RestApi_Multisites.php';
require_once __DIR__ . '/endpoints/v0/RestApi_WpmlLanguages.php';
require_once __DIR__ . '/endpoints/v0/RestApi_ModifiedPages.php';
require_once __DIR__ . '/endpoints/v0/RestApi_ModifiedEvents.php';
require_once __DIR__ . '/endpoints/v0/RestApi_ModifiedDisclaimer.php';
// v1
require_once __DIR__ . '/endpoints/RestApi_Multisites.php';

const API_NAMESPACE = 'extensions';
const CURRENT_VERSION = 1;

const ENDPOINT_MULTISITES = 'multisites';
const ENDPOINT_LANGUAGES = 'languages';
const ENDPOINT_PAGES = 'pages';
const ENDPOINT_EVENTS = 'events';
const ENDPOINT_DISCLAIMER = 'disclaimer';

add_action('rest_api_init', function () {
	/**
	 * @var int -> [[string, RestApi_ExtensionBaseV0]] $versioned_endpoints
	 * API version -> [key -> endpoint]
	 */
	$versioned_endpoints = [
		0 => [
			ENDPOINT_MULTISITES => new RestApi_MultisitesV0(),
			ENDPOINT_LANGUAGES => new RestApi_WpmlLanguagesV0(),
			ENDPOINT_PAGES => new RestApi_ModifiedPagesV0(),
			ENDPOINT_EVENTS => new RestApi_ModifiedEventsV0(),
			ENDPOINT_DISCLAIMER => new RestApi_ModifiedDisclaimerV0(),
		],
		CURRENT_VERSION => [
			ENDPOINT_MULTISITES => new RestApi_Multisites(),
		]
	];

	// register versioned endpoints
	foreach ($versioned_endpoints as $version => $endpoints) {
		$versioned_namespace = API_NAMESPACE . '/v' . $version;
		foreach ($endpoints as $endpoint) {
			$endpoint->register_routes($versioned_namespace);
		}
	}
	// register most recent versions without version number
	$most_recent_endpoints = [];
	foreach ($versioned_endpoints as $key_endpoints) {
		$most_recent_endpoints = array_merge($most_recent_endpoints, $key_endpoints);
	}
	foreach ($most_recent_endpoints as $endpoint) {
		$endpoint->register_routes(API_NAMESPACE);
	}
});
