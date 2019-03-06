<?php

class APIv3_PC_Page extends APIv3_Base_Abstract {

	const ROUTE = 'pushpage';

	public function __construct() {
		parent::__construct();
		$this->callback = 'put_pushpage';
		$this->method = 'POST';
	}

	public function put_pushpage( WP_REST_Request $request ) {
		if (function_exists('ig_pc_save_page')) {
			return ig_pc_save_page( $request );
		} else {
			// throw error if IntegreatSettingsPlugin is not activated
			return new WP_Error('The Push Content plugin is not activated.');
		}
	}

}
