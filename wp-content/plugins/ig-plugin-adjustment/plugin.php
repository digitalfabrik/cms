<?php

/**
 * Plugin Name: Plugin Adjustment
 * Description: Automatically adjust plugin content (e.g. resources) after an upgrade
 * Version: 0.1
 * Author: Integreat
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

add_action('upgrader_process_complete', function ($upgrader_object, $options) {
	if ($options['type'] === 'plugin') {
		if (in_array('sitepress-multilingual-cms', $options['plugins'])) {
			PluginAdjustment::apply_wpml_adjustment();
		}

		if (in_array('cms-tree-page-view', $options['plugins'])) {
			PluginAdjustment::apply_revisionary_adjustments();
		}

		if (in_array('broken-link-checker', $options['plugins'])) {
			PluginAdjustment::broken_link_checker();
		}

	}
}, 10, 2);

abstract class PluginAdjustment {

	static function replace_in_file($file_path, $search, $replace) {
		if (file_exists($file_path) && is_writeable($file_path)) {
			$file_content = file_get_contents($file_path);
			$file_content = str_replace($search, $replace, $file_content);
			file_put_contents($file_path, $file_content);
		} else {
			echo '<div class="notice notice-error"><p><strong>The file "' . $file_path . '" is not writable, please implement all modifications described in ig-plugin-adjustment manually.</strong></p></div>';
		}
	}

	static function apply_wpml_adjustment() {
		$file_path = get_home_path() . 'wp-content/plugins/sitepress-multilingual-cms/res/js/post-edit-languages.js';
		$search = 'urlData = {
					post_type: type,
					lang:      language_code
				};

				if (statuses && statuses.length) {
					urlData.post_status = statuses.join(\',\');
				}';
		$replace = 'urlData = {
					post_type: type,
					lang:      language_code
				};

				if (type === \'event\' || type === \'event-recurring\') {
					var urlParams = new URLSearchParams(window.location.search);
					urlData.scope = urlParams.get(\'scope\');
				}

				if (statuses && statuses.length) {
					urlData.post_status = statuses.join(\',\');
				}';
		self::replace_in_file($file_path, $search, $replace);

	}

	public function apply_revisionary_adjustments() {
		$tree_view_file = plugin_dir_path(__FILE__) . '../cms-tree-page-view/functions.php';
		$search = '"post_status": "<?php echo $onePage->post_status ?>",';
		$replace = '"post_status": "<?php echo ig_tree_view_labels ($onePage->ID, $onePage->post_status ) ?>",';
		self::replace_in_file($tree_view_file, $search, $replace);

	}

	public function broken_link_checker(){
		$broken_link_file = plugin_dir_path(__FILE__) . '../broken-link-checker/core/core.php';
		$search = "			'edit_others_posts',";
		$replace = "			'create_users',";
		self::replace_in_file($broken_link_file, $search, $replace);
	}

}
