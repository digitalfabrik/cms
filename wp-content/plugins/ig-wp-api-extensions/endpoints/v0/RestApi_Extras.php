<?php

require_once __DIR__ . '/RestApi_ExtensionBase.php';

class RestApi_ExtrasV0 extends RestApi_ExtensionBaseV0 {

	const URL = 'extras';

	public function __construct() {
	}

	public function register_routes($namespace) {
		parent::register_route($namespace, self::URL, '/', [
			'callback' => [
				$this,
				'get_extras'
			]
		]);
	}

	public function get_extras() {
		global $wpdb;
		$query_str = "SELECT * FROM $wpdb->options WHERE option_name LIKE 'ige-%'";
		$query_results = $wpdb->get_results($query_str, OBJECT);
		$result = [];
		foreach ($query_results as $extra) {
			$result[] = $this->prepare_item($extra);
		}
		return $result;
	}

	protected function prepare_item($extra){
		$extra_prepared = [
			'alias' => $extra->option_name
		];
		$extra_value = json_decode($extra->option_value, true);
		if (is_array($extra_value)) {
			$extra_prepared = array_merge($extra_prepared, $extra_value);
		} else {
			$extra_prepared['enabled'] = 0;
		}
		return $extra_prepared;
	}

}
