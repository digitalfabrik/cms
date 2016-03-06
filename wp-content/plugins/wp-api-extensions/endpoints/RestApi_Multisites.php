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
		$this->load_included_site_ids();

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
			'live' => in_array($id, $this->included_site_ids)
		];
		restore_current_blog();
		return $result;
	}

	private function get_live_instances() {
		$filepath = __DIR__ . '/' . self::LIVEINSTANCES_FILENAME;
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

	/**
	 * If the included site ids are not already loaded,
	 * retrieves them and stores them in #included_site_ids.
	 */
	private function load_included_site_ids() {
		if ($this->included_site_ids !== false) {
			// already loaded
			return;
		}
		$this->included_site_ids = $this->get_live_instances();
	}
}

/* change header image size */
add_action('after_setup_theme', function () {
	add_theme_support('custom-header', apply_filters('custom_header_args', [
		'width' => 600,
		'height' => 450,
	]));
});
