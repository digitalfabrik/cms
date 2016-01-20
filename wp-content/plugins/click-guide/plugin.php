<?php
/*
Plugin Name: Click Guide
Description: Configurable click-guide
Author:      Sascha Beele
*/

define( 'CLICKGUIDE_BASEPATH', __DIR__ );
define( 'CLICKGUIDE_BASEURL', plugin_dir_url( __FILE__ ) );

$options_instance_table = $wpdb->prefix . 'options';
define( 'CLICKGUIDE_INSTANCE_OPTIONS_TABLE', $options_instance_table );
define( 'CLICKGUIDE_INSTANCE_OPTION_NAME', 'clickguide_chosen_tours' );

define( 'CLICKGUIDE_NETWORK_OPTIONS_TABLE', 'wp_options' );

$table_name = 'wp_clickguide_fields';
define( 'CLICKGUIDE_TABLE', $table_name);

if( is_admin() ) {

	require_once __DIR__ .  '/ClickGuide.php';
	$clickGuide = new ClickGuide();

	// Create database table on plugin activation
	register_activation_hook( __FILE__, 'clickguide_activation' );
	function clickguide_activation() {
		$clickGuide = new ClickGuide();
		$clickGuide->createDatabaseTable();
	}

}

?>