<?php

/**
 * Retrieve the multisites defined in this network
 */
abstract class APIv3_Sites_Abstract extends APIv3_Base_Abstract {

	public function register_routes(String $namespace) {
		parent::register_route($namespace, static::ROUTE, 'get_sites');
	}

	public function get_sites() {
		$sites = [];
		foreach (get_sites() as $site) {
			if (static::LIVE XOR (!$site->public OR $site->spam OR $site->deleted OR $site->archived OR $site->mature)) {
				$sites[] = $this->prepare($site);
			}
		}
		return $sites;
	}

	private function prepare(WP_Site $site) {
		switch_to_blog($site->blog_id);
		$result = [
			'id' => (int) $site->blog_id,
			'name' => get_blog_details($site->blog_id)->blogname,
			'icon' => get_site_icon_url(),
			'cover_image' => get_header_image(),
			'color' => '#FFA000',
			'path' => $site->path,
			'description' => get_bloginfo($site->path),
		];
		restore_current_blog();
		return $result;
	}

}
