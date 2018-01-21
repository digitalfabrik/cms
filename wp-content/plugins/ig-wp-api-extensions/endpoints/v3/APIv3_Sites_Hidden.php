<?php

/**
 * Retrieve all hidden multisites
 */
class APIv3_Sites_Hidden extends APIv3_Sites_Abstract {

	protected const ROUTE = parent::ROUTE.'/hidden';
	protected const LIVE = false;

}
