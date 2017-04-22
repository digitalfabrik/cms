<?php
/**
 * Plugin Name: Integreat Firebase Notifications
 * Description: Sending Multilingual Messages to Smartphones
 * Version: 1.0
 * Author: Sven Seeberg
 * Author URI: https://github.com/sven15
 * License: MIT
 * Text Domain: ig-firebase-notification
 */

require_once __DIR__ . '/service.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/settings.php';

add_action( 'admin_menu', 'ig_fb_menu' );

function ig_fb_menu() {
	add_menu_page( 'Push Notifications', 'Push Notifications', 'publish_post', 'ig-fb-pn', 'igWritePushNotification', $position = 6 );
	add_submenu_page( 'ig-fb-pn', 'Push Notifications Settings', 'Settings', 'manage_options', 'ig-fb-pn-settings', 'igPushNotificationSettings' ); 
}

?>
