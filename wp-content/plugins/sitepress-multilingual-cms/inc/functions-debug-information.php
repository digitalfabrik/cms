<?php

function get_debug_info() {
	global $sitepress, $wpdb;
	$debug_info = new WPML_Debug_Information( $wpdb, $sitepress );
	$debug_info->run();

	return $debug_info;
}