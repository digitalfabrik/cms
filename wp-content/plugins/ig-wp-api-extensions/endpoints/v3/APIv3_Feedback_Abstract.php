<?php

abstract class APIv3_Feedback_Abstract extends APIv3_Base_Abstract {

	public function __construct() {
		parent::__construct();
		$this->method = 'POST';
		$this->callback = 'put_feedback';
		$this->args = [
			'comment' => [
				'required' => true,
				'validate_callback' => function($comment) {
					return is_string($comment);
				}
			],
			'rating' => [
				'validate_callback' => function($rating) {
					return in_array($rating, [0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5]);
				}
			],
		];
	}

}
