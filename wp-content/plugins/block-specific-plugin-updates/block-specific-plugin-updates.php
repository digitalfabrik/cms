<?php
/* 
Plugin Name: Block Specific Plugin Updates
Plugin URI: http://dineshkarki.com.np/block-specific-plugin-updates
Description: This plugin blocks the updates for specific plugins. You can select the plugins from plugin setting page.
Author: Dinesh Karki
Version: 2.2
Author URI: http://www.dineshkarki.com.np
*/

/*  Copyright 2012  Dinesh Karki  (email : dnesskarki@gmail.com)

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

add_filter( 'http_request_args', 'bpu_prevent_update_check',10, 2 );
function bpu_prevent_update_check( $r, $url ) {
	if ( 0 === strpos( $url, 'https://api.wordpress.org/plugins/update-check/1.1/' ) ) {
		$bpu_update_blocked_plugins 		= get_option('bpu_update_blocked_plugins');
		$bpu_update_blocked_plugins_array	= @explode('###',$bpu_update_blocked_plugins);		
		if (!empty($bpu_update_blocked_plugins_array)){
			foreach ($bpu_update_blocked_plugins_array as $my_plugin){
				$plugins = json_decode($r['body']['plugins'], true);
				
				if (array_key_exists($my_plugin, $plugins['plugins'])){
					unset($plugins['plugins'][$my_plugin]);
				}
				
				$r['body']['plugins'] = json_encode( $plugins );
			}
		}
	}
	return $r;
}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'bpu_plugin_action_links' );
function bpu_plugin_action_links( $links ) {
   $links[] = '<a href="'. esc_url( get_admin_url(null, 'options-general.php?page=block-specific-plugin-updates/plugin_interface.php') ) .'">Settings</a>';
   return $links;
}

include('plugin_interface.php');
?>