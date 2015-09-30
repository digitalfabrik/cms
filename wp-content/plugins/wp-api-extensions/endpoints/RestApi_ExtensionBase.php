<?php

abstract class RestApi_ExtensionBase {
	private $namespace;
	private $baseRoute;
	private $DEFAULT_ROUTE_OPTIONS = [
		'methods' => WP_REST_Server::READABLE,
	];

	public function __construct($namespace, $baseRoute) {
		$this->namespace = $namespace;
		$this->baseRoute = $baseRoute;
	}

	/**
	 * @param string $subPath the path after the base route
	 * @param array $options options for the route (need at least callback)
	 * @see register_rest_route
	 * @see self::DEFAULT_ROUTE_OPTIONS
	 */
	public function register_route($subPath, $options) {
		$routeOptions = array_merge($this->DEFAULT_ROUTE_OPTIONS, $options);
		register_rest_route($this->namespace, $this->baseRoute . $subPath, $routeOptions);
	}
}
