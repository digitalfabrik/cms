<?php

abstract class APIv3_Base_Abstract {

	public function register_route($namespace, $route, $callback, $method = WP_REST_Server::READABLE) {
		register_rest_route($namespace, $route, [
			'methods' => $method,
			'callback' => [
				$this,
				$callback
			]
		]);
	}
}
