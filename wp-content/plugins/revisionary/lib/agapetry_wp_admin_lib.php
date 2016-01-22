<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

// Decipher the ever-changing meta/advanced action names into a version-insensitive question:
// "Has metabox drawing been initiated?"
function rvy_metaboxes_started() {
	return did_action('edit_form_advanced') || did_action('edit_page_form');
}

// added adjustable timeout to WP function
function rvy_remote_fopen( $uri, $timeout = 10 ) {
	$parsed_url = @parse_url( $uri );

	if ( !$parsed_url || !is_array( $parsed_url ) )
		return false;

	$options = array();
	$options['timeout'] = $timeout;

	$response = wp_remote_get( $uri, $options );

	if ( is_wp_error( $response ) )
		return false;

	return $response['body'];
}
?>