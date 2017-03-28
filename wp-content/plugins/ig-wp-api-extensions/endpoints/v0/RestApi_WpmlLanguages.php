<?php

require_once __DIR__ . '/RestApi_ExtensionBase.php';
require_once __DIR__ . '/helper/WpmlHelper.php';

/**
 * Retrieve the active WPML languages of a site
 */
class RestApi_WpmlLanguagesV0 extends RestApi_ExtensionBaseV0 {
	const URL = 'languages';

	public function __construct() {
		parent::__construct();
		$this->wpml_helper = new WpmlHelper();
	}


	public function register_routes($namespace) {
		parent::register_route($namespace,
			self::URL, '/wpml', [
				'callback' => [$this, 'get_wpml_languages']
			]);
	}

	public function get_wpml_languages() {
		$languages = $this->wpml_helper->get_languages();

		$result = [];
		foreach ($languages as $item) {
			$result[] = $this->prepare_item($item);
		}
		return $result;
	}

	private function prepare_item($language) {
		return [
			'id' => $language['id'],
			'code' => $language['code'],
			'native_name' => $language['native_name'],
			'country_flag_url' => $language['country_flag_url'],
			'dir' => ig_text_dir($language['code']),
		];
	}
}
