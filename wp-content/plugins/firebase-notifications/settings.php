<?php

function FirebaseNotificationSettings () {
	$blog_id = get_current_blog_id();
	$settings['fbn_auth_key'] = get_blog_option( $blog_id, 'fbn_auth_key' );
	$settings['fbn_api_url'] = get_blog_option( $blog_id, 'fbn_api_url' );
	$settings['use_network_settings'] = get_blog_option( $blog_id, 'fbn_use_network_settings' );
	$settings['force_network_settings'] = get_site_option('fbn_force_network_settings');
	var_dump($settings);
}

function FirebaseNotificationNetworkSettings () {
	$settings['auth_key'] = get_site_option('fbn_auth_key');
	$settings['api_url'] = get_site_option('fbn_api_url');
	$settings['force_network_settings'] = get_site_option('fbn_force_network_settings');
	var_dump($settings);
}

?>
