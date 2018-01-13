<?php

require_once __DIR__ . '/RestApi_ExtensionBase.php';

/**
 * Retrieve the multisites defined in this network
 */
class RestApi_MultisitesV2 extends RestApi_ExtensionBaseV2 {
	const URL = 'multisites';
	const LIVEINSTANCES_FILENAME = 'liveinstances.txt';

	private $GLOBAL_SITE_IDS = [5];

	public function register_routes($namespace) {
		parent::register_route($namespace,
			self::URL, '/', [
				'callback' => [$this, 'get_multisites']
			]);
	}

	public function get_multisites() {
		$multisites = wp_get_sites();
		$included_site_ids = $this->get_live_instances();

		$result = [];
		foreach ($multisites as $blog) {
			if (!in_array($blog['blog_id'], $included_site_ids)) {
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
			'cover_image' => get_header_image(),
			'color' => '#FFA000',
			'path' => $blog['path'],
			'description' => get_bloginfo($blog),
			'global' => in_array($id, $this->GLOBAL_SITE_IDS)
		];
		restore_current_blog();
		return $result;
	}

	private function get_live_instances() {
		$filepath = __DIR__ . '/../' . self::LIVEINSTANCES_FILENAME;
		$handle = fopen($filepath, "r");
		if (!$handle) {
			throw new RuntimeException("Could not open live instances file '" . $filepath . "'");
		}
		try {
			$ids = [];
			while (($line = fgets($handle)) !== false) {
				$id_length = strpos($line, " ");
				if ($id_length > 0) {
					$id = substr($line, 0, $id_length);
				} else {
					$id = $line;
				}
				$ids[] = $id;
			}
		} finally {
			fclose($handle);
		}
		return $ids;
	}
}

/* change header image size */
add_action('after_setup_theme', function () {
	add_theme_support('custom-header', apply_filters('custom_header_args', [
		'width' => 600,
		'height' => 450,
	]));
});
