<?php

class APIv3_Feedback_Post extends APIv3_Feedback_Abstract {

	const ROUTE = 'feedback';
	const TYPE = 'post';

	public function __construct() {
		parent::__construct();
		$this->args['id'] = [
			'validate_callback' => function($id) {
				return $this->is_valid($id);
			}
		];
		$this->args['permalink'] = [
			'validate_callback' => function($permalink) {
				return $this->is_valid(url_to_postid($this::sanitize_permalink($permalink)));
			}
		];
	}

}
