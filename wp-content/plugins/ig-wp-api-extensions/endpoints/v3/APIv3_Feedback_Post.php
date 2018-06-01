<?php

class APIv3_Feedback_Post extends APIv3_Feedback_Abstract {

	const ROUTE = 'feedback';

	public function __construct() {
		parent::__construct();
		$this->args['id'] = [
			'validate_callback' => function($id) {
				return $this->is_valid($id);
			}
		];
		$this->args['url'] = [
			'validate_callback' => function($url) {
				return $this->is_valid(url_to_postid($url));
			}
		];
	}

	public function put_feedback(WP_REST_Request $request) {
		$id = $request->get_param('id');
		$url = $request->get_param('url');
		if ($id !== null || $url !== null) {
			if ($id === null) {
				$id = url_to_postid($url);
			}
			$comment_id = wp_new_comment([
				'comment_post_ID' => $id,
				'comment_content' => $request->get_param('comment'),
				'comment_author_IP' => ''
			], true);
			if (is_wp_error($comment_id)) {
				return $comment_id;
			}
			add_comment_meta($comment_id, 'rating', $request->get_param('rating'));
			return new WP_Error('rest_comment_created', 'Feedback successfully submitted', ['status' => 201]);
		} else {
			return new WP_Error('rest_missing_param', 'Either the id or the url parameter is required', ['status' => 400]);
		}
	}

}
