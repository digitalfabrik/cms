<?php
/**
 * Plugin Name: Separate Disclaimer
 * Description: Gesondertes Impressum außerhalb der normalen Beiträge/Seiten (eigener post_type)
 * Version: 0.1
 * Author: Martin Schrimpf
 * Author URI: https://github.com/Meash
 * License: MIT
 */

add_action('init', function () {
	register_post_type('disclaimer',
		[
			'labels' => [
				'name' => __('Impressum'),
				'singular_name' => __('Impressum')
			],
			'public' => true,
			'has_archive' => true,
			'menu_icon' => 'dashicons-info',
			'capabilities' => [
				'publish_posts' => 'manage_disclaimer',
				'edit_posts' => 'manage_disclaimer',
				'edit_others_posts' => 'manage_disclaimer',
				'delete_posts' => 'manage_disclaimer',
				'delete_others_posts' => 'manage_disclaimer',
				'read_private_posts' => 'manage_disclaimer',
				'edit_post' => 'manage_disclaimer',
				'delete_post' => 'manage_disclaimer',
				'read_post' => 'manage_disclaimer'
			]
		]
	);
	global $iclTranslationManagement;
//	print_r($iclTranslationManagement->settings['custom-types_readonly_config']);
//	exit;
});

register_activation_hook(__FILE__, function () {
	$mu_blogs = wp_get_sites();
	foreach ($mu_blogs as $mu_blog) {
		switch_to_blog($mu_blog['blog_id']);

		WPML_Config::load_config_run();
	}

	restore_current_blog();
});
