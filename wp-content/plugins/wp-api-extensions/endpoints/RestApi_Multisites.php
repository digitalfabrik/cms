<?php

require_once __DIR__ . '/RestApi_ExtensionBase.php';

/**
 * Retrieve the multisites defined in this network
 */
class RestApi_Multisites extends RestApi_ExtensionBase {
	const URL = 'multisites';
	const LIVEINSTANCES_FILENAME = 'liveinstances.txt';

	private $included_site_ids = false;

	public function register_routes($namespace) {
		parent::register_route($namespace,
			self::URL, '/', [
				'callback' => [$this, 'get_multisites']
			]);
	}

	public function get_multisites() {
		$multisites = wp_get_sites();

		$result = [];
		foreach ($multisites as $blog) {
			$result[] = $this->prepare_item($blog);
		}
		return $result;
	}

	private function prepare_item($blog) {
		$details = get_blog_details($blog);
		$id = $blog['blog_id'];
		switch_to_blog($id);
		$result = [
			'id' => $id,
			'name' => $details->blogname,
			'icon' => get_site_icon_url(),
			'cover_image' => get_header_image(),
			'color' => '#FFA000',
			'path' => $blog['path'],
			'description' => get_bloginfo($blog['path']),
			'live' => $blog['public'] and !$blog['spam'] and !$blog['deleted'] and !$blog['archived'] and !$blog['mature']
		];
		restore_current_blog();
		return $result;
	}
}

/* change header image size */
add_action('after_setup_theme', function () {
	add_theme_support('custom-header', apply_filters('custom_header_args', [
		'width' => 600,
		'height' => 450,
	]));
});
