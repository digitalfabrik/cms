<?php

namespace WPML\TM\REST;

use IWPML_Deferred_Action_Loader;
use IWPML_REST_Action_Loader;
use function WPML\Container\make;

class FactoryLoader implements IWPML_REST_Action_Loader, IWPML_Deferred_Action_Loader {

	const REST_API_INIT_ACTION = 'rest_api_init';

	/**
	 * @return string
	 */
	public function get_load_action() {
		return self::REST_API_INIT_ACTION;
	}

	public function create() {
		return [
			\WPML\TM\ATE\REST\Sync::class     => make( \WPML\TM\ATE\REST\Sync::class ),
			\WPML\TM\ATE\REST\Download::class => make( \WPML\TM\ATE\REST\Download::class ),
		];
	}
}
