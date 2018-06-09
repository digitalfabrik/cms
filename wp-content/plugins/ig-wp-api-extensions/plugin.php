<?php
/**
 * Plugin Name: WP API Extensions
 * Description: Collection of extensions to the Wordpress REST API
 * Version: 0.1
 * Author: Martin Schrimpf, Timo Ludwig, Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

// wpml helper
require_once __DIR__ . '/endpoints/helper/WpmlHelper.php';
// Piwik / Matomo helper
require_once __DIR__ . '/endpoints/helper/PiwikTracker.php';
require_once __DIR__ . '/endpoints/helper/PiwikHelper.php';
// base
require_once __DIR__ . '/endpoints/RestApi_ExtensionBase.php';
// v0
require_once __DIR__ . '/endpoints/v0/RestApi_ModifiedContent.php';
require_once __DIR__ . '/endpoints/v0/RestApi_ModifiedDisclaimer.php';
require_once __DIR__ . '/endpoints/v0/RestApi_ModifiedEvents.php';
require_once __DIR__ . '/endpoints/v0/RestApi_ModifiedPages.php';
require_once __DIR__ . '/endpoints/v0/RestApi_Multisites.php';
require_once __DIR__ . '/endpoints/v0/RestApi_WpmlLanguages.php';
// v1
require_once __DIR__ . '/endpoints/v1/RestApi_Multisites.php';
// v2
require_once __DIR__ . '/endpoints/v2/RestApi_ModifiedContent.php';
require_once __DIR__ . '/endpoints/v2/RestApi_ModifiedPages.php';
require_once __DIR__ . '/endpoints/v2/RestApi_ModifiedEvents.php';
require_once __DIR__ . '/endpoints/v2/RestApi_ModifiedDisclaimer.php';
// v3
require_once __DIR__ . '/endpoints/v3/APIv3_Base_Abstract.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Extras.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Feedback_Abstract.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Feedback_Categories.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Feedback_Cities.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Feedback_Events.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Feedback_Extra.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Feedback_Extras.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Feedback_Post.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Feedback_Search.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Languages.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Sites.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Sites_Hidden.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Sites_Live.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Posts_Abstract.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Posts_Relatives_Abstract.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Posts_Children.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Posts_Disclaimer.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Posts_Events.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Posts_Pages.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Posts_Parents.php';
require_once __DIR__ . '/endpoints/v3/APIv3_Posts_Post.php';

const API_NAMESPACE = 'extensions';
const CURRENT_VERSION = 2;

add_action('rest_api_init', function () {
	/*
	 * Register no routes if current location is disabled
	 */
	global $wpdb;
	$disabled = $wpdb->get_row(
		"SELECT value
			FROM {$wpdb->base_prefix}ig_settings
				AS settings
			LEFT JOIN {$wpdb->prefix}ig_settings_config
				AS config
				ON settings.id = config.setting_id
			WHERE settings.alias = 'disabled'");
	if (isset($disabled->value) && $disabled->value) {
		return;
	}
	$versioned_endpoints = [
		0 => [
			new RestApi_ModifiedPagesV0(),
			new RestApi_ModifiedEventsV0(),
			new RestApi_ModifiedDisclaimerV0(),
			new RestApi_MultisitesV0(),
			new RestApi_WpmlLanguagesV0(),
		],
		1 => [
			new RestApi_ModifiedDisclaimerV0(), // legacy APIv0
			new RestApi_ModifiedEventsV0(), // legacy APIv0
			new RestApi_ModifiedPagesV0(), // legacy APIv0
			new RestApi_MultisitesV1(),
			new RestApi_WpmlLanguagesV0(), // legacy APIv0
		],
		2 => [
			new RestApi_ModifiedDisclaimerV2(),
			new RestApi_ModifiedEventsV2(),
			new RestApi_ModifiedPagesV2(),
			new RestApi_MultisitesV1(), // legacy APIv1
			new RestApi_WpmlLanguagesV0(), // legacy APIv0
		],
		3 => [
			new APIv3_Extras(),
			new APIv3_Feedback_Post(),
			new APIv3_Feedback_Categories(),
			new APIv3_Feedback_Cities(),
			new APIv3_Feedback_Events(),
			new APIv3_Feedback_Extra(),
			new APIv3_Feedback_Extras(),
			new APIv3_Feedback_Search(),
			new APIv3_Languages(),
			new APIv3_Posts_Children(),
			new APIv3_Posts_Disclaimer(),
			new APIv3_Posts_Events(),
			new APIv3_Posts_Pages(),
			new APIv3_Posts_Parents(),
			new APIv3_Posts_Post(),
			new APIv3_Sites(),
			new APIv3_Sites_Hidden(),
			new APIv3_Sites_Live(),
		],
	];

	// register versioned endpoints
	foreach ($versioned_endpoints as $version => $endpoints) {
		$versioned_namespace = API_NAMESPACE . '/v' . $version;
		foreach ($endpoints as $endpoint) {
			$endpoint->register_routes($versioned_namespace);
		}
	}
	// register current version without version number
	foreach ($versioned_endpoints[CURRENT_VERSION] as $endpoint) {
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
