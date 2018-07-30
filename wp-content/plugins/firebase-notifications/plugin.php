<?php
/**
 * Plugin Name: FCM Notifications
 * Description: Sending FCM Messages to Smartphones with WPML support
 * Version: 2.0
 * Author: Sven Seeberg
 * Author URI: https://github.com/sven15
 * License: MIT
 * Text Domain: firebase-notifications
 */

require_once __DIR__ . '/functions/notifications.php';
require_once __DIR__ . '/functions/settings.php';

require_once __DIR__ . '/classes/service.php';
require_once __DIR__ . '/classes/database.php';

/**
 * Add menu entries to single blog
 */
function fb_pn_menu() {
	load_plugin_textdomain( 'firebase-notifications', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	add_menu_page( 'Push Notifications', 'Push Notifications', 'publish_pages', 'fb-pn', 'write_firebase_notification', 'dashicons-email-alt', $position = 99 );
	add_submenu_page( 'fb-pn', 'Firebase Notifications Settings', 'Settings', 'manage_options', 'fb-pn-settings', 'firebase_notification_settings' );
}
add_action( 'admin_menu', 'fb_pn_menu' );

/**
 * Add menu entries to network admin
 */
function fb_pn_network_menu() {
	load_plugin_textdomain( 'firebase-notifications', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	add_menu_page( "Firebase Notifications Network Settings", "FCM Push Notifications", 'manage_network_options', 'fb-pn-network-settings', 'firebase_notification_network_settings');
}
add_action( 'network_admin_menu', 'fb_pn_network_menu' );

/**
 * Default settings for single blog
 */
function firebase_notifications_settings() {
	$settings['fbn_auth_key'] = '0';
	$settings['fbn_api_url'] = 'https://fcm.googleapis.com/fcm/send';
	$settings['fbn_use_network_settings'] = '1'; //0 use blog settings; 1 use network settings
	$settings['fbn_groups'] = 'news';
	$settings['fbn_debug'] = '0';
	$settings['fbn_title_prefix'] = '';
	return $settings;
}

/**
 * Default settings for network
 */
function firebase_notifications_network_settings() {
	$settings['fbn_auth_key'] = '0';
	$settings['fbn_api_url'] = 'https://fcm.googleapis.com/fcm/send';
	$settings['fbn_force_network_settings'] = '0'; // 0: allow settings for each blog; 1: blogs CAN use network settings; 2: blogs MUST use network settings
	$settings['fbn_per_blog_topic'] = '1'; //add blog id and wpml language to topic, 1 = add, 0 = only group
	$settings['fbn_groups'] = 'news';
	$settings['fbn_debug'] = '0';
	return $settings;
}

/**
 * Function for registering the plugin after activation. This installs
 * or updates the database and also sets the default configuration
 * values.
 */
function firebase_notifications_registration() {
	$default_network_settings = firebase_notifications_network_settings();
	foreach( $default_network_settings as $key => $value ) {
		add_site_option( $key, $value );
	}

	$default_blog_settings = firebase_notifications_settings();
	$all_blogs = get_sites();
	foreach ( $all_blogs as $blog ) {
		foreach ( $default_blog_settings as $key => $value ) {
			add_blog_option( $blog->blog_id, $key, $value );
		}
	}
	$fcmdb = New FirebaseNotificationsDatabase();
	$fcmdb->install_database();
}
register_activation_hook( __FILE__, 'firebase_notifications_registration' );

/**
 * Creates new database table for newly created blogs.
 */
add_action( 'wpmu_new_blog', function( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	$fcmdb = New FirebaseNotificationsDatabase();
	$fcmdb->new_blog( $blog_id );
}, 10, 6 );

?>
