<?php
/*
* Plugin Name: WP-RTL
* Plugin URI:  http://www.fadvisor.net/blog/2008/10/wp-rtl/
* Description: Adds two buttons to the TinyMCE editor to enable writing text in Left to Right (LTR) and Right to Left (RTL) directions.
* Version:     1.0
* License:     GPLv3
* Author:      Fahad Alduraibi
* Author URI:  http://www.fadvisor.net/blog/


Copyright (C) 2016 Fahad Alduraibi

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

add_action( "init", "tinymce_bidi_addbuttons" );

function tinymce_bidi_addbuttons() {
	if( !current_user_can ( 'edit_posts' ) && !current_user_can ( 'edit_pages' ) ) {
		return;
	}
	if( get_user_option ( 'rich_editing' ) == 'true' ) {
		add_filter( "mce_external_plugins", "tinymce_bidi_plugin" );
		add_filter( "mce_buttons", "tinymce_bidi_buttons" );
	}
	
	wp_register_style( 'wp-rtl-icon-fix',  plugin_dir_url( __FILE__ ) . 'wp-rtl.css' );
	
}
function tinymce_bidi_buttons($buttons) {
	wp_enqueue_style( 'wp-rtl-icon-fix' );
	array_push($buttons, "separator", "ltr", "rtl");
	return $buttons;
}

function tinymce_bidi_plugin($plugin_array) {
	if (get_bloginfo('version') < 3.9) {
		$plugin_array['directionality'] = includes_url('js/tinymce/plugins/directionality/editor_plugin.js');
	} else {
		$plugin_array['directionality'] = includes_url('js/tinymce/plugins/directionality/plugin.min.js');
	}
	return $plugin_array;
}
?>
