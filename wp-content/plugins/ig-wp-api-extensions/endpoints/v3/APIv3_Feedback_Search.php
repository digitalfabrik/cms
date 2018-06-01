<?php

class APIv3_Feedback_Search extends APIv3_Feedback_Abstract {

	const ROUTE = 'feedback/search';

	public function __construct() {
		parent::__construct();
		$this->args['url'] = [
			'required' => true,
			'validate_callback' => function($url) {
				return filter_var($url, FILTER_VALIDATE_URL);
			}
		];
		$this->args['query'] = [
			'required' => true,
			'validate_callback' => function($query) {
				return is_string($query);
			}
		];
	}

	public function put_feedback(WP_REST_Request $request) {
		$url = $request->get_param('url');
		$query = $request->get_param('query');
		$comment_id = wp_new_comment([
			'comment_content' => $request->get_param('comment'),
			'comment_author_IP' => ''
		], true);
		if (is_wp_error($comment_id)) {
			return $comment_id;
		}
		add_comment_meta($comment_id, 'rating', $request->get_param('rating'));
		add_comment_meta($comment_id, 'query', $query);
		add_comment_meta($comment_id, 'url', $url);
		return new WP_Error('rest_comment_created', 'Feedback successfully submitted', ['status' => 201]);
	}

}
