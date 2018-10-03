<?php
/**
 * Plugin Name: Simplify UI for Integreat
 * Description: 
 * Version: 1.0
 * Author: Sven Seeberg
 * Author URI: https://github.com/sven15
 * License: MIT
 */
class IGSimplifyUI {
	public function enqueue_styles_and_scripts() {
		foreach (['ig-event-ui.css','ig-postmeta.css'] as $styleName) {
			wp_enqueue_style($styleName, plugin_dir_url(__FILE__) . 'css/' . $styleName);
		}
	}
}
$igsimplifyui = new IGSimplifyUI();
add_action('admin_enqueue_scripts', [$igsimplifyui, 'enqueue_styles_and_scripts']);

add_action('admin_menu', function () {
	remove_menu_page('edit.php');
});