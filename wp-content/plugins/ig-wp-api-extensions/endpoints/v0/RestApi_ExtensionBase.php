<?php

abstract class RestApi_ExtensionBaseV0 {
	private $DEFAULT_ROUTE_OPTIONS = [
			'methods' => WP_REST_Server::READABLE,
	];

	public function __construct() {
	}

	/**
	 * @param string $namespace
	 * @param string $baseRoute
	 * @param string $subPath the path after the base route
	 * @param array $options options for the route (need at least callback)
	 * @see register_rest_route
	 * @see self::DEFAULT_ROUTE_OPTIONS
	 */
	public function register_route($namespace, $baseRoute, $subPath, $options) {
		$routeOptions = array_merge($this->DEFAULT_ROUTE_OPTIONS, $options);
		register_rest_route($namespace, $baseRoute . $subPath, $routeOptions);
	}
}
