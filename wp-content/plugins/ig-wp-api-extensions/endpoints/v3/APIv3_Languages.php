<?php

/**
 * Retrieve the active WPML languages of a site
 */
class APIv3_Languages extends APIv3_Base_Abstract {

	private const ROUTE = 'languages';

	public function register_routes(String $namespace) {
		parent::register_route($namespace, self::ROUTE, 'get_languages');
	}

	public function get_languages() {
		$languages = [];
		foreach (apply_filters('wpml_active_languages', null, '') as $language) {
			$languages[] = $this->prepare($language);
		}
		return $languages;
	}

	private function prepare(Array $language) {
		return [
			'id' => $language['id'],
			'code' => $language['code'],
			'native_name' => $language['native_name'],
			'country_flag_url' => $language['country_flag_url'],
			'dir' => ig_text_dir($language['code']),
		];
	}

}