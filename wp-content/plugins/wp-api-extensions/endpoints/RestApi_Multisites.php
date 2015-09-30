<?php

require_once __DIR__ . '/RestApi_ExtensionBase.php';

/**
 * Retrieve the multisites defined in this network
 */
class RestApi_Multisites extends RestApi_ExtensionBase {
	const URL = 'multisites';

	const EXCLUDED_SITE_IDS = [1];
	const GLOBAL_SITE_IDS = [5];

	public function __construct($namespace) {
		parent::__construct($namespace, self::URL);
	}


	public function register_routes() {
		parent::register_route('/', [
			'callback' => [$this, 'get_multisites']
		]);
	}

	public function get_multisites() {
		$multisites = wp_get_sites();

		$result = [];
		foreach ($multisites as $blog) {
			if (in_array($blog['blog_id'], self::EXCLUDED_SITE_IDS)) {
				continue;
			}
			$result[] = $this->prepare_item($blog);
		}
		return $result;
	}

	private function prepare_item($blog) {
		$details = get_blog_details($blog);
		$id = $blog['blog_id'];
		return [
			'id' => $id,
			'name' => $details->blogname,
			'icon' => $this->get_icon($id),
			'path' => $blog['path'],
			'description' => get_bloginfo($blog),
			'global' => in_array($id, self::GLOBAL_SITE_IDS)
		];
	}

	private function get_icon($blog_id) {
		$posts = $blog_id > 1 ? "wp_${blog_id}_posts" : "wp_posts";
		$postmeta = $blog_id > 1 ? "wp_${blog_id}_postmeta" : "wp_postmeta";
		$query_string = "
			SELECT post_content AS icon_path
			FROM $posts
			JOIN $postmeta ON $postmeta.post_id = $posts.ID
			WHERE $postmeta.meta_key = '_wp_attachment_context'
			  AND $postmeta.meta_value = 'site-icon'";
		global $wpdb;
		$results = $wpdb->get_results($query_string, OBJECT);
		if (sizeof($results) > 1) {
			throw new LogicException("More than one icon defined for site $blog_id");
		}
		if (sizeof($results) == 0) {
			return null;
		}
		return $results[0]->icon_path;
	}
}
