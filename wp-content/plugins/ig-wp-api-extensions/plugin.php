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

function wp_api_extension_before_delete_post($postid) {
	$post = get_post( $postid );
	//if( $_GET['action'] == "delete" ) { //can be used instead of following line, depends on GET
	if ( 'page' == $post->post_type && !is_super_admin() ) { //we can delete everything but the initial page, independent from loaded page
		wp_redirect(admin_url('edit.php?post_type=page'));
		exit();
	}
}
add_action('before_delete_post', 'wp_api_extension_before_delete_post', 1);

function wp_api_extension_hide_delete_css()
{
	if( is_super_admin() ){
		//superadmins are allowed to delete posts. This feature is "dangerous".
		return;
	}
	if( isset( $_REQUEST['post_status'] ) && 'trash' == $_REQUEST['post_status'] ){
		echo "<style>
			.alignleft.actions:first-child, #delete_all {
				display: none;
			}
			</style>";
	}
}
add_action( 'admin_head-edit.php', 'wp_api_extension_hide_delete_css' );

function wp_api_extension_hide_row_action( $actions, $post ) 
{
	if( is_super_admin() ) {
		return $actions;
	}
	if( isset( $_REQUEST['post_status'] ) && 'trash' == $_REQUEST['post_status'] )
		unset( $actions['delete'] );
	return $actions; 
}
add_filter( 'post_row_actions', 'wp_api_extension_hide_row_action', 10, 2 );
add_filter( 'page_row_actions', 'wp_api_extension_hide_row_action', 10, 2 );
