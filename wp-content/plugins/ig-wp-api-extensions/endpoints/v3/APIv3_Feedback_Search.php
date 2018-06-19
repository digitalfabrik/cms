<?php

class APIv3_Feedback_Search extends APIv3_Feedback_Abstract {

	const ROUTE = 'feedback/search';
	const TYPE = 'search';
	const META = 'query';

	public function __construct() {
		parent::__construct();
		$this->args['query'] = [
			'required' => true,
			'validate_callback' => function($query) {
				return is_string($query);
			}
		];
	}

}
