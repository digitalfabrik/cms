<?php
/**
 * Save settings for single blog
 */
function firebase_notification_settings () {
	if ( wp_verify_nonce( $_POST['_wpnonce'], 'ig-fb-settings-nonce' ) && current_user_can('manage_options') ) {
		$blog_id = get_current_blog_id();
		update_blog_option( $blog_id, 'fbn_auth_key', $_POST['fbn_auth_key'] );
		update_blog_option( $blog_id, 'fbn_api_url', $_POST['fbn_api_url'] );
		update_blog_option( $blog_id, 'fbn_use_network_settings', $_POST['fbn_use_network_settings'] );
		update_blog_option( $blog_id, 'fbn_groups', $_POST['fbn_groups'] );
		update_blog_option( $blog_id, 'fbn_debug', $_POST['fbn_debug'] );
		update_blog_option( $blog_id, 'fbn_title_prefix', $_POST['fbn_title_prefix'] );
		echo "<div class='notice notice-success'><p>".__('Settings saved.', 'firebase-notifications')." </p></div>";
	}
	firebase_notification_settings_form();
}

/**
 * Generate settings page for single blog
 */
function firebase_notification_settings_form() {
	$blog_id = get_current_blog_id();
	$settings['auth_key'] = get_blog_option( $blog_id, 'fbn_auth_key' );
	$settings['api_url'] = get_blog_option( $blog_id, 'fbn_api_url' );
	$settings['use_network_settings'] = get_blog_option( $blog_id, 'fbn_use_network_settings' );
	$settings['force_network_settings'] = get_site_option( 'fbn_force_network_settings' );
	$settings['groups'] = get_blog_option( $blog_id, 'fbn_groups' );
	$settings['debug'] = get_blog_option( $blog_id, 'fbn_debug' );
	$settings['fbn_title_prefix'] = get_blog_option( $blog_id, 'fbn_title_prefix' );
	if ( $settings['force_network_settings'] == '0' )
		$network_settings = __('This blog must manage it\'s own Firebase Cloud Messaging settings.', 'firebase-notifications');
	elseif ( $settings['force_network_settings'] == '1' )
		$network_settings = __('This blog is allowed to use the network wide Firebase Cloud Messaging settings.', 'firebase-notifications');
	elseif ( $settings['force_network_settings'] == '2' )
		$network_settings = __('This blog must use the network wide Firebase Cloud Messaging settings.', 'firebase-notifications');
	require_once( __DIR__ . '/../templates/settings.php');
}

/**
 * Save settings for network
 */
function firebase_notification_network_settings () {
	if ( wp_verify_nonce( $_POST['_wpnonce'], 'ig-fb-networksettings-nonce' ) && current_user_can('manage_network_options') ) {
		update_site_option( 'fbn_auth_key', $_POST['fbn_auth_key'] );
		update_site_option( 'fbn_api_url', $_POST['fbn_api_url'] );
		update_site_option( 'fbn_force_network_settings', $_POST['fbn_force_network_settings'] );
		update_site_option( 'fbn_per_blog_topic', $_POST['fbn_per_blog_topic'] );
		update_site_option( 'fbn_groups', $_POST['fbn_groups'] );
		update_site_option( 'fbn_debug', $_POST['fbn_debug'] );
	}
	firebase_notification_network_settings_form();
}

/**
 * Generate settings page for network
 */
function firebase_notification_network_settings_form() {
	$settings['auth_key'] = get_site_option( 'fbn_auth_key' );
	$settings['api_url'] = get_site_option( 'fbn_api_url' );
	$settings['force_network_settings'] = get_site_option( 'fbn_force_network_settings' );
	$settings['per_blog_topic'] = get_site_option( 'fbn_per_blog_topic' );
	$settings['groups'] = get_site_option( 'fbn_groups' );
	$settings['debug'] = get_site_option( 'fbn_debug' );
	require_once( __DIR__ . '/../templates/network_settings.php');
}

?>
