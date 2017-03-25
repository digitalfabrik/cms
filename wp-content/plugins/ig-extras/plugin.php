<?php
/**
 * Plugin Name: Integreat Extras Settings
 * Description: Provides checkboxes for App Content Settings
 * Version: 1.0
 * Author: Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */
function ig_extras_settings() {
	$ig_extras_settings['ige-srl'] = "0";
	$ig_extras_settings['ige-sbt'] = "0";
	$ig_extras_settings['ige-evts'] = "0";
	$ig_extras_settings['ige-pn'] = "0";
	$ig_extras_settings['ige-c4r'] = "0";
	return $ig_extras_settings;
}

function ig_extras_api_settings( $array, $blog_id ) {
	$ig_extras_settings = ig_extras_settings();
	foreach( $ig_extras_settings as $key => $value ) {
		$ig_extras_settings[ $key ] = get_blog_option( $blog_id, $key, $value );
	}
	return array_merge( $array, $ig_extras_settings );
}
add_filter( 'ig_wp_api_extension_settings', 'ig_extras_api_settings', 10, 2 );

function ig_extras_blogs() {
	global $wp_query;
	global $wpdb;
	$query = "SELECT blog_id FROM wp_blogs where blog_id > 1";
	$all_blogs = $wpdb->get_results($query);
	$n = 0;
	foreach( $all_blogs as $blog ){
		$blog_ids[$n] = $blog->blog_id;
		$n++;
	}
	return $blog_ids;
}

function ig_extras_activate() {
	foreach(ig_extras_blogs() as $blog_id) {
		foreach(ig_extras_settings() as $key => $value) {
			add_blog_option($blog_id, $key, $value);
		}
	}
}
register_activation_hook( __FILE__, 'ig_extras_activate' );
