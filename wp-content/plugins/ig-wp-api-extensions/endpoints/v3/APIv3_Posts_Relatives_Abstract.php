<?php

abstract class APIv3_Posts_Relatives_Abstract extends APIv3_Posts_Abstract {

	const POST_TYPE = 'any';

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->args = [
			'id' => [
				'required' => false,
				'validate_callback' => function($id) {
					return $this->is_valid($id);
				}
			],
			'url' => [
				'required' => false,
				'validate_callback' => function($url) {
					return $this->is_valid(url_to_postid($url));
				}
			]
		];
	}

	public function get_post(WP_REST_Request $request) {
		$id = $request->get_param('id');
		$url = $request->get_param('url');
		if ($id !== null || $url !== null) {
			if ($id === null) {
				$id = url_to_postid($url);
			}
			$post = get_post($id);
		} else {
			$post = (object) [
				'ID' => 0,
				'post_status' => 'publish',
				'post_parent' => 0
			];
		}
		return $post;
	}

}
