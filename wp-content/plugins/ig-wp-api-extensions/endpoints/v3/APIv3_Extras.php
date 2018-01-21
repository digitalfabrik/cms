<?php

class APIv3_Extras extends APIv3_Base_Abstract {

	private const ROUTE = 'extras';

	public function register_routes(String $namespace) {
		parent::register_route($namespace, self::ROUTE, 'get_extras');
	}

	public function get_extras() {
		global $wpdb;
		$extras = [];
		foreach ($wpdb->get_results("SELECT * FROM $wpdb->options WHERE option_name LIKE 'ige-%'", OBJECT) as $extra) {
			$extras[] = $this->prepare($extra);
		}
		return $extras;
	}

	private function prepare(StdClass $extra) {
		$extra_value = json_decode($extra->option_value, true);
		if (!is_array($extra_value)) {
			$extra_value = [
				'enabled' => 0
			];
		}
		return array_merge([
			'alias' => $extra->option_name
		], $extra_value);
	}

}
