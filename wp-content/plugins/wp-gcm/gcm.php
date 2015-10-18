<?php
/*
Plugin Name: WP GCM
Plugin  URI: http://wordpress.org/plugins/wp-gcm
Description: Google Cloud Messaging Plugin for WordPress.
Version: 1.2.8
Author: Deniz Celebi & Pixelart
Author URI: http://profiles.wordpress.org/pixelart-dev/
License: GPLv3
License URI: http://www.gnu.org/licenses/old-licenses/gpl-3.0.html

    Copyright 2014 Pixelart and Deniz Celebi  (email : office@pixelartdev.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

$dir = px_gcm_dir();

@include_once "$dir/options.php";
@include_once "$dir/page/settings.php";
@include_once "$dir/page/write.php";
@include_once "$dir/register.php";

 function px_gcm_activated() {
   	global $wpdb;
  	$px_table_name = $wpdb->prefix.'gcm_users';

	if($wpdb->get_var("show tables like '$px_table_name'") != $px_table_name) {
		$sql = "CREATE TABLE " . $px_table_name . " (
		`id` int(11) NOT NULL AUTO_INCREMENT,
        `gcm_regid` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
		);";
 
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
         add_option('px_gcm_do_activation_redirect', true);
}

function px_gcm_setting_redirect() {
    if (get_option('px_gcm_do_activation_redirect', false)) {
        delete_option('px_gcm_do_activation_redirect');
        if(!isset($_GET['activate-multi']))
        {
            wp_redirect(get_site_url().'/wp-admin/admin.php?page=px-gcm-settings');
        }
    }
}

function px_gcm_dir() {
  if (defined('PX_GCM_DIR') && file_exists(PX_GCM_DIR)) {
    return PX_GCM_DIR;
  } else {
    return dirname(__FILE__);
  }
}

register_activation_hook("$dir/gcm.php", 'px_gcm_activated');
add_action('admin_init', 'px_gcm_setting_redirect');
add_action('init','px_gcm_register');
?>