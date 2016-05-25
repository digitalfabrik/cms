<?php

function rvy_mu_site_menu() {	
	if ( ! current_user_can( 'manage_options' ) )
		return;

	$path = RVY_ABSPATH;
	
	$name = ( awp_ver( '3.1' ) ) ? 'sites' : 'ms-admin';
	
	// WP MU site options
	if ( awp_is_mu() ) {
		// RS Site Options
		add_submenu_page("{$name}.php", __('Revisionary Options', 'revisionary'), __('Revisionary Options', 'revisionary'), 'read', 'rvy-site_options');

		$func = "include_once( '$path' . '/admin/options.php');rvy_options( true );";
		add_action("{$name}_page_rvy-site_options", create_function( '', $func ) );	
		
		global $rvy_default_options, $rvy_options_sitewide;
		
		// omit Option Defaults menu item if all options are controlled sitewide
		if ( empty($rvy_default_options) )
			rvy_refresh_default_options();
		
		if ( count($rvy_options_sitewide) != count($rvy_default_options) ) {
			// RS Default Options (for per-site settings)
			add_submenu_page("{$name}.php", __('Revisionary Option Defaults', 'revisionary'), __('Revisionary Defaults', 'revisionary'), 'read', 'rvy-default_options');
			
			$func = "include_once( '$path' . '/admin/options.php');rvy_options( false, true );";
			add_action("{$name}_page_rvy-default_options", create_function( '', $func ) );	
		}
	}
}

?>