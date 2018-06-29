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
	protected $current_request;

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

	/**
	 * Get all page ids of the given page and all its children in the correct order
	 *
	 * @param $id int|string
	 * @return array
	 */
	protected function get_post_ids_recursive($id) {
		$direct_children = (new WP_Query([
			'post_type' => $this->current_request->post_type,
			'post_status' => 'publish',
			'post_parent' => $id,
			'orderby' => 'menu_order post_title',
			'order' => 'ASC',
			'posts_per_page' => -1,
			'fields' => 'ids',
		]))->posts;
		if (empty($direct_children)) {
			return [$id];
		} else {
			return array_reduce(array_map([$this, 'get_post_ids_recursive'], $direct_children), function ($all_children, $grand_children) {
				return array_merge($all_children, $grand_children);
			}, [$id]);
		}
	}

}
