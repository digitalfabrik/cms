<?php

/**
 * Retrieve all live multisites
 */
class APIv3_Sites_Live extends APIv3_Sites {

	const ROUTE = 'sites/live';

	public function __construct() {
		parent::__construct();
		$this->callback = 'get_live_sites';
	}

	public function get_live_sites() {
		return array_map([$this, 'prepare'], array_filter(get_sites(), function ($site) {
			return !$this->is_disabled($site) && !$this->is_hidden($site);
		}));
	}

}