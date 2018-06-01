<?php

class APIv3_Feedback_Extra extends APIv3_Feedback_Abstract {

	const ROUTE = 'feedback/extra';

	public function __construct() {
		parent::__construct();
		$this->args['url'] = [
			'required' => true,
			'validate_callback' => function($url) {
				return filter_var($url, FILTER_VALIDATE_URL);
			}
		];
		$this->args['extra'] = [
			'required' => true,
			'validate_callback' => function($extra) {
				return is_string($extra);
			}
		];
	}

	public function put_feedback(WP_REST_Request $request) {
		$url = $request->get_param('url');
		$extra = $request->get_param('extra');
		$comment_id = wp_new_comment([
			'comment_content' => $request->get_param('comment'),
			'comment_author_IP' => ''
		], true);
		if (is_wp_error($comment_id)) {
			return $comment_id;
		}
		add_comment_meta($comment_id, 'rating', $request->get_param('rating'));
		add_comment_meta($comment_id, 'extra', $extra);
		add_comment_meta($comment_id, 'url', $url);
		return new WP_Error('rest_comment_created', 'Feedback successfully submitted', ['status' => 201]);
	}

}
