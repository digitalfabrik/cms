<?php
// create custom plugin settings menu
add_action('admin_menu', 'bpu_create_menu');

if (!function_exists('get_plugins')){
	require_once (ABSPATH."wp-admin/includes/plugin.php");
}

function bpu_create_menu() {
	add_options_page('Block Plugin Update', 'Block Plugin Update', 'administrator', __FILE__, 'bpu_settings_page');
	add_action('admin_init', 'register_bpusettings');
}

function register_bpusettings() {
	register_setting('bpu-settings-group', 'bpu_update_blocked_plugins');
}

function bpu_settings_page() {
	global $updated;
	$bpu_update_blocked_plugins 		= get_option('bpu_update_blocked_plugins');
	$bpu_update_blocked_plugins_array	= explode('###',$bpu_update_blocked_plugins);
	$plugins = get_plugins ();
	include('includes/bspu_header.php');
	include('includes/bspu_plugin_select.php');
	include('includes/bspu_footer.php');
}