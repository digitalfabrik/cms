<?php
/*
   Plugin Name: Prevent Post Deletion
   Plugin URI: http://wordpress.org/extend/plugins/prevent-post-deletion/
   Version: 0.1
   Author: Integreat
   Description: Prevent Post Deletion
   Text Domain: prevent-post-deletion
   License: GPLv3
  */

/*
    "WordPress Plugin Template" Copyright (C) 2016 Michael Simpson  (email : michael.d.simpson@gmail.com)

    This following part of this file is part of WordPress Plugin Template for WordPress.

    WordPress Plugin Template is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    WordPress Plugin Template is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Contact Form to Database Extension.
    If not, see http://www.gnu.org/licenses/gpl-3.0.html
*/

$PreventPostDeletion_minimalRequiredPhpVersion = '5.0';

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying
 * an error message on the Admin page
 */
function PreventPostDeletion_noticePhpVersionWrong() {
    global $PreventPostDeletion_minimalRequiredPhpVersion;
    echo '<div class="updated fade">' .
      __('Error: plugin "Prevent Post Deletion" requires a newer version of PHP to be running.',  'prevent-post-deletion').
            '<br/>' . __('Minimal version of PHP required: ', 'prevent-post-deletion') . '<strong>' . $PreventPostDeletion_minimalRequiredPhpVersion . '</strong>' .
            '<br/>' . __('Your server\'s PHP version: ', 'prevent-post-deletion') . '<strong>' . phpversion() . '</strong>' .
         '</div>';
}


function PreventPostDeletion_PhpVersionCheck() {
    global $PreventPostDeletion_minimalRequiredPhpVersion;
    if (version_compare(phpversion(), $PreventPostDeletion_minimalRequiredPhpVersion) < 0) {
        add_action('admin_notices', 'PreventPostDeletion_noticePhpVersionWrong');
        return false;
    }
    return true;
}


/**
 * Initialize internationalization (i18n) for this plugin.
 * References:
 *      http://codex.wordpress.org/I18n_for_WordPress_Developers
 *      http://www.wdmac.com/how-to-create-a-po-language-translation#more-631
 * @return void
 */
function PreventPostDeletion_i18n_init() {
    $pluginDir = dirname(plugin_basename(__FILE__));
    load_plugin_textdomain('prevent-post-deletion', false, $pluginDir . '/languages/');
}


//////////////////////////////////
// Run initialization
/////////////////////////////////

// Initialize i18n
add_action('plugins_loadedi','PreventPostDeletion_i18n_init');

// Run the version check.
// If it is successful, continue with initialization for this plugin
if (PreventPostDeletion_PhpVersionCheck()) {
    // Only load and run the init function if we know PHP version can parse it
    include_once('prevent-post-deletion_init.php');
    PreventPostDeletion_init(__FILE__);
}

function wpse_92155_before_delete_post() {
	wp_redirect(admin_url('edit.php'));
	exit();
} // function wpse_92155_before_delete_post
add_action('before_delete_post', 'wpse_92155_before_delete_post', 1);

add_action( 'admin_head-edit.php', 'hide_delete_css_wpse_92155' );
add_filter( 'post_row_actions', 'hide_row_action_wpse_92155', 10, 2 );
add_filter( 'page_row_actions', 'hide_row_action_wpse_92155', 10, 2 );

function hide_delete_css_wpse_92155()
{
	if( isset( $_REQUEST['post_status'] ) && 'trash' == $_REQUEST['post_status'] ) 
	{
		echo "<style>
			.alignleft.actions:first-child, #delete_all {
				display: none;
			}
			</style>";
	}
}

function hide_row_action_wpse_92155( $actions, $post ) 
{
	if( isset( $_REQUEST['post_status'] ) && 'trash' == $_REQUEST['post_status'] ) 
		unset( $actions['delete'] );

	return $actions; 
}
