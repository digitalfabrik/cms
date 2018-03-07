<?php

/**
 * Retrieve all hidden multisites
 */
class APIv3_Sites_Hidden extends APIv3_Sites {

	const ROUTE = 'sites/hidden';

	public function __construct() {
		parent::__construct();
		$this->callback = 'get_hidden_sites';
	}

	public function get_hidden_sites() {
		return array_map([$this, 'prepare'], array_filter(get_sites(), function ($site) {
			return !$this->is_disabled($site) && (!$site->public || $site->spam || $site->deleted || $site->archived || $site->mature);
		}));
	}

}
