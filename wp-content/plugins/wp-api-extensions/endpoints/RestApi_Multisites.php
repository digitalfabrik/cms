<?php

require_once __DIR__ . '/RestApi_ExtensionBase.php';

/**
 * Retrieve the multisites defined in this network
 */
class RestApi_Multisites extends RestApi_ExtensionBase {
	const URL = 'multisites';

	private $EXCLUDED_SITE_IDS = [
		1, // landing page
		6 // pre arrival
	];
	private $GLOBAL_SITE_IDS = [5];

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
			if (in_array($blog['blog_id'], $this->EXCLUDED_SITE_IDS)) {
				continue;
			}
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
			'path' => $blog['path'],
			'description' => get_bloginfo($blog),
			'global' => in_array($id, $this->GLOBAL_SITE_IDS)
		];
		restore_current_blog();
		return $result;
	}
}
