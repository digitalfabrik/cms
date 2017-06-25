<?php

/**
 * Plugin Name: Mobile Preview
 * Description: Display a preview of the content on a mobile device in the page editor
 * Version: 0.1
 * Author: Martin Schrimpf
 * Author URI: https://github.com/Meash
 * License: MIT
 */
class MobilePreviewPlugin {
	public function add_meta_box() {
		add_meta_box('mobile_preview_meta_box',
			'Mobile Preview',
			[$this, 'display_meta_box'],
			'page', 'normal', 'high'
		);
	}

	public function display_meta_box() {
		?>
		<div id="mobile-preview">
			<div id="mobile-preview-title"></div>
			<div id="mobile-preview-content-container">
				<div id="mobile-preview-content"></div>
			</div>
		</div>
		<?php
	}

	public function enqueue_styles_and_scripts() {
		global $typenow;
		if ($typenow != 'page') {
			return;
		}
		// styles
		foreach (['mobile-preview.css', 'table-reorder.css'] as $styleName) {
			wp_enqueue_style($styleName, plugin_dir_url(__FILE__) . 'css/' . $styleName);
		}
		// scripts
		$jqueryCdnUrl = "http" . ($_SERVER['SERVER_PORT'] == 443 ? "s" : "") . "://code.jquery.com";
		$this->load_from_cdn('jquery', $jqueryCdnUrl, "jquery-2.1.4.min.js");
		foreach (['table-reorder.js', 'copy-content.js'] as $scriptName) {
			wp_enqueue_script($scriptName, plugin_dir_url(__FILE__) . 'js/' . $scriptName, ['jquery']);
		}
	}

	public function add_editor_hooks($init) {
		$init['setup'] = "function(ed) {
							ed.on('keyup', function(evt) {
								mobilePreviewCopyTinymceContent(ed, evt);
								// reorderTables(); // TODO: awaiting realization in App
							});
						  }";
		return $init;
	}

	/**
	 * @param string $scriptName
	 * @param string $cdnUrl
	 * @param string $scriptFileName
	 */
	private function load_from_cdn($scriptName, $cdnUrl, $scriptFileName) {
		$source = $cdnUrl . "/" . $scriptFileName;
		$transient_cache_name = 'cached_' . $scriptName . '_cdn_is_up';
		$cachedCdnIsUp = get_transient($transient_cache_name);

		if (!$cachedCdnIsUp) {
			$cdn_response = wp_remote_get($source);
			if (!is_wp_error($cdn_response) && wp_remote_retrieve_response_code($cdn_response) == '200') {
				set_transient($transient_cache_name, true, MINUTE_IN_SECONDS * 20); // cache cdn status for some time
			} else {
				$source = plugin_dir_url(__FILE__) . 'js/' . $scriptFileName;
			}
		}
		wp_enqueue_script($scriptName, $source);
	}
}

$mobilePreviewPlugin = new MobilePreviewPlugin();
add_action('admin_init', [$mobilePreviewPlugin, 'add_meta_box']);
add_action('admin_enqueue_scripts', [$mobilePreviewPlugin, 'enqueue_styles_and_scripts']);
add_filter('tiny_mce_before_init', [$mobilePreviewPlugin, 'add_editor_hooks']);

function mobile_preview_wpautop () {
	$text = $_POST['mpvwpautop'];
	if( $text != "" ) {
		echo wpautop($text);
		exit;
	}
	else{}

}
add_action( 'init', 'mobile_preview_wpautop' );
