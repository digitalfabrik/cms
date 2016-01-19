<?php

require_once __DIR__ . '/callback.php';

/**
 * Plugin Name: Automatic Translation Button
 * Description: Translate posts to another language on button click, based on WPML and the Automatic Translation Plugin
 * Version: 0.1
 * Author: Martin Schrimpf
 * Author URI: https://github.com/Meash
 * License: MIT
 */
class AutomaticTranslationButtonPlugin {
	public function enqueue_styles_and_scripts() {
		$post = get_post(null, OBJECT);
		if (!$post || $post->post_status == 'auto-draft') {
			// page has not been saved yet
			return;
		}

		global $typenow;
		if (!in_array($typenow, ['page', 'event'])) {
			return;
		}
		// styles
		foreach (['automatic-translation-button.css'] as $styleName) {
			wp_enqueue_style($styleName, plugin_dir_url(__FILE__) . 'css/' . $styleName);
		}
		// scripts
		$jqueryCdnUrl = "http" . ($_SERVER['SERVER_PORT'] == 443 ? "s" : "") . "://code.jquery.com";
		$this->load_from_cdn('jquery', $jqueryCdnUrl, "jquery-2.1.4.min.js");
		foreach (['automatic-translation-button.js'] as $scriptName) {
			wp_enqueue_script($scriptName, plugin_dir_url(__FILE__) . 'js/' . $scriptName, ['jquery']);
		}
		$callback_url = wp_nonce_url(admin_url('admin-ajax.php'), $this->get_nonce_action($post->ID));
		wp_localize_script('automatic-translation-button.js', 'automatic_translation_button_vars', ['post' => $post->ID, 'ajaxurl' => $callback_url]);
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

	public function get_nonce_action($post_id) {
		return 'automatic_translation_button_translate-' . $post_id;
	}
}

$automaticTranslationButtonPlugin = new AutomaticTranslationButtonPlugin();
add_action('admin_enqueue_scripts', [$automaticTranslationButtonPlugin, 'enqueue_styles_and_scripts']);
