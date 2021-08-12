<?php

abstract class APIv3_Base_Abstract {

	protected $method;
	protected $callback;
	protected $args;

	public function __construct() {
		$this->method = WP_REST_Server::READABLE;
		$this->callback = 'get_'.static::ROUTE;
		$this->args = [];
	}

	public function register_routes($namespace) {
		register_rest_route($namespace, static::ROUTE, [
			'methods' => $this->method,
			'callback' => [
				$this,
				$this->callback
			],
				'args' => $this->args
		]);
	}

	/*
	 * Test whether a given id has a corresponding published post.
	 */
	protected function is_valid($id) {
		$post = get_post($id);
		return $post !== null && $post->post_status == 'publish';
	}

}
