<?php

class APIv3_Posts_Post extends APIv3_Posts_Abstract {

	const ROUTE = 'post';
	const POST_TYPE = 'any';

	public function register_routes($namespace) {
		parent::register_route($namespace, static::ROUTE, 'get_post', 'GET', [
			'id' => [
				'required' => false,
				'validate_callback' => function($id) {
					return is_numeric($id);
				}
			],
			'url' => [
				'required' => false,
				'validate_callback' => function($url) {
					return filter_var($url, FILTER_VALIDATE_URL);
				}
			]
		]);
	}

	public function get_post(WP_REST_Request $request) {
		$id = $request->get_param('id');
		$url = $request->get_param('url');
		if ($id !== null) {
			$post = get_post($id);
			if ($post === null) {
				return new WP_Error('rest_no_post', 'No post was found for this ID', ['status' => 404]);
			}
		} elseif ($url !== null) {
			$id = url_to_postid($url);
			$post = get_post($id);
			if ($post === null) {
				return new WP_Error('rest_no_post', 'No post was found for this URL', ['status' => 404]);
			}
		} else {
			return new WP_Error('rest_missing_param', 'Either the ID or the URL parameter is required', ['status' => 400]);
		}
		if ($post->post_status !== 'publish') {
			return new WP_Error('rest_not_published', 'This post has not been published', ['status' => 403]);
		}
		return $this->prepare($post);
	}

}
