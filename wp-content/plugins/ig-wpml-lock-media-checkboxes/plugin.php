<?php
/**
 * Plugin Name: Lock WPML Media Checkboxes
 * Description: Lock checkboxes for page editor for media attachments
 * Version: 1.0
 * Author: Sven Seeberg
 * Author URI: https://github.com/sven15
 * License: MIT
 */
class LockWPMLMediaCheckboxes {
	public function enqueue_styles_and_scripts() {
		if( !(strpos($_SERVER['REQUEST_URI'],'post.php') or strpos($_SERVER['REQUEST_URI'],'post-new.php')) )
			return;
		foreach (['lock-wpml-media-checkbox.js'] as $scriptName) {
			wp_enqueue_script($scriptName, plugin_dir_url(__FILE__) . 'js/' . $scriptName, ['jquery']);
		}
	}
}

$removeWPMLMediaCheckboxes = new LockWPMLMediaCheckboxes();
add_action('admin_enqueue_scripts', [$removeWPMLMediaCheckboxes, 'enqueue_styles_and_scripts']);
