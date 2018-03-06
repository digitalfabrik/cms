<?php

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}

global $wpdb;
$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
foreach ($blog_ids as $blog_id) {
	switch_to_blog($blog_id);
	// local tables for configuration of extras and settings
	IntegreatSettingConfig::delete_table();
	IntegreatExtraConfig::delete_table();
	restore_current_blog();
}
// global tables for extras and settings
IntegreatSetting::delete_table();
IntegreatExtra::delete_table();