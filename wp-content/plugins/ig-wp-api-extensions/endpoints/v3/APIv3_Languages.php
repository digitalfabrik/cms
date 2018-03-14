<?php

/**
 * Retrieve the active WPML languages of a site
 */
class APIv3_Languages extends APIv3_Base_Abstract {

	const ROUTE = 'languages';

	public function get_languages() {
		return array_map([$this, 'prepare'], (apply_filters('wpml_active_languages', null, '')));
	}

	private function prepare(Array $language) {
		return [
			'id' => (int) $language['id'],
			'code' => $language['code'],
			'native_name' => $language['native_name'],
			'country_flag_url' => $language['country_flag_url'],
			'dir' => ig_text_dir($language['code']),
		];
	}

}