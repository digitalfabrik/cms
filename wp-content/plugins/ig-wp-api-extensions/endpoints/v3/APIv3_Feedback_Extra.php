<?php

class APIv3_Feedback_Extra extends APIv3_Feedback_Abstract {

	const ROUTE = 'feedback/extra';
	const TYPE = 'extra';
	const META = 'alias';

	public function __construct() {
		parent::__construct();
		$this->args['alias'] = [
			'required' => true,
			'validate_callback' => function($alias) {
				if (class_exists('IntegreatSettingsPlugin')) {
					return (bool) IntegreatExtra::get_extra_by_alias($alias);
				}
				return false;
			}
		];
	}

}
