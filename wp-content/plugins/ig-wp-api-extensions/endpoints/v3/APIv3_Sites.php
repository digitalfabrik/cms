<?php

/**
 * Retrieve the multisites defined in this network
 */
class APIv3_Sites extends APIv3_Base_Abstract {

	const ROUTE = 'sites';

	public function get_sites() {
		return array_map([$this, 'prepare'], array_filter(get_sites(), function ($site) {
			return !$this->is_disabled($site);
		}));
	}

	public function prepare(WP_Site $site) {
		switch_to_blog($site->blog_id);
		$result = [
			'id' => (int) $site->blog_id,
			'name' => get_blog_details($site->blog_id)->blogname,
			'icon' => get_site_icon_url(),
			'cover_image' => get_header_image(),
			'color' => '#FFA000',
			'path' => $site->path,
			'description' => get_bloginfo('description'),
		];
		if (static::ROUTE === 'sites') {
			$result['live'] = !$this->is_hidden($site);
		}
		if (class_exists('IntegreatSettingsPlugin')) {
			$result = array_merge($result, apply_filters('ig-settings-api', null));
		}
		restore_current_blog();
		return $result;
	}

	protected function is_disabled(WP_Site $site) {
		if (class_exists('IntegreatSettingsPlugin')) {
			return apply_filters('ig-site-disabled', $site);
		} else {
			return false;
		}
	}

	protected function is_hidden(WP_Site $site) {
		return (!$site->public || $site->spam || $site->deleted || $site->archived || $site->mature);
	}

}
