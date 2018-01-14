<?php

/**
 * Class RestApi_ExtensionBase
 *
 * This abstract class provides basic functionality for all endpoints
 */
abstract class RestApi_ExtensionBase {
	private $default_route_options = [
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
	 * @see self::default_route_options
	 */
	public function register_route($namespace, $baseRoute, $subPath, $options) {
		$routeOptions = array_merge($this->default_route_options, $options);
		register_rest_route($namespace, $baseRoute . $subPath, $routeOptions);
	}
}
