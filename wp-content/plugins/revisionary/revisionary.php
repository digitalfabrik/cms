<?php
/*
Plugin Name: Revisionary
Plugin URI: http://agapetry.net/
Description: Enables qualified users to submit changes to currently published posts or pages.  These changes, if approved by an Editor, can be published immediately or scheduled for future publication.
Version: 1.1.13
Author: Kevin Behrens
Author URI: http://agapetry.net/
Min WP Version: 3.0
License: GPL version 2 - http://www.opensource.org/licenses/gpl-license.php
*/

/*
Copyright (c) 2009-2015, Kevin Behrens.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
version 2 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

if ( strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/index-extra.php' ) || strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/update.php' ) )
	return;

if ( defined( 'RVY_VERSION' ) ) {
	// don't allow two copies to run simultaneously
	if ( is_admin() && strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/plugins.php' ) && ! strpos( urldecode($_SERVER['REQUEST_URI']), 'deactivate' ) ) {
		if ( defined( 'RVY_FOLDER' ) )
			$message = sprintf( __( 'Another copy of Revisionary is already activated (version %1$s in "%2$s")', 'rvy' ), RVY_VERSION, RVY_FOLDER );
		else
			$message = sprintf( __( 'Another copy of Revisionary is already activated (version %1$s)', 'rvy' ), RVY_VERSION );
		
		die($message);
	}
	return;
}

define ('RVY_VERSION', '1.1.13');

define ('COLS_ALL_RVY', 0);
define ('COL_ID_RVY', 1);

if ( defined('RS_DEBUG') ) {
	include_once( dirname(__FILE__).'/lib/debug.php');
	add_action( 'admin_footer', 'rvy_echo_usage_message' );
} else
	include_once( dirname(__FILE__).'/lib/debug_shell.php');

//if ( version_compare( phpversion(), '5.2', '<' ) )	// some servers (Ubuntu) return irregular version string format
if ( ! function_exists("array_fill_keys") )
	require_once( dirname(__FILE__).'/lib/php4support_rs.php');

// === awp_is_mu() function definition and usage: must be executed in this order, and before any checks of IS_MU_RVY constant ===
require_once( dirname(__FILE__).'/lib/agapetry_wp_core_lib.php');
define( 'IS_MU_RVY', awp_is_mu() );
// -------------------------------------------

require_once( dirname(__FILE__).'/content-roles_rvy.php');

if ( is_admin() || defined('XMLRPC_REQUEST') ) {
	require_once( dirname(__FILE__).'/lib/agapetry_wp_admin_lib.php');
		
	// skip WP version check and init operations when a WP plugin auto-update is in progress
	if ( false !== strpos($_SERVER['SCRIPT_NAME'], 'update.php') )
		return;
}

require_once( dirname(__FILE__).'/rvy_init.php');	// Contains activate, deactivate, init functions. Adds mod_rewrite_rules.

// register these functions before any early exits so normal activation/deactivation can still run with RS_DEBUG
register_activation_hook(__FILE__, 'rvy_activate');

// avoid lockout in case of editing plugin via wp-admin
if ( defined('RS_DEBUG') && is_admin() && ( strpos( urldecode($_SERVER['REQUEST_URI']), 'p-admin/plugin-editor.php' ) || strpos( urldecode($_SERVER['REQUEST_URI']), 'p-admin/plugins.php' ) ) && false === strpos( $_SERVER['REQUEST_URI'], 'activate' ) )
	return;

// define URL
define ('RVY_BASENAME', plugin_basename(__FILE__) );
define ('RVY_FOLDER', dirname( plugin_basename(__FILE__) ) );

if ( ! defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', site_url( 'wp-content', $scheme ) );

if ( ! defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', str_replace('\\', '/', ABSPATH) . 'wp-content' );

define ('RVY_ABSPATH', WP_CONTENT_DIR . '/plugins/' . RVY_FOLDER);

$bail = 0;

if ( ! awp_ver('3.0') ) {
	rvy_notice('Sorry, Revisionary requires WordPress 3.0 or higher.  Please upgrade Wordpress or use Revisionary 1.0.x');
	$bail = 1;
} else {	
	global $wpdb;

	if ( ! $wpdb->has_cap( 'subqueries' ) ) {
		rvy_notice('Sorry, Revisionary requires a database server that supports subqueries (such as MySQL 4.1+).  Please upgrade your server or deactivate Revisionary.');
		$bail = 1;
	}
}

if ( ! $bail ) {
	require_once( dirname(__FILE__).'/defaults_rvy.php');

	rvy_refresh_options_sitewide();
	
	// since sequence of set_current_user and init actions seems unreliable, make sure our current_user is loaded first
	add_action('init', 'rvy_init', 1);
	add_action('init', 'rvy_add_revisor_custom_caps', 99);
}

?>