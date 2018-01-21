<?php

/**
 * Retrieve all live multisites
 */
class APIv3_Sites_Live extends APIv3_Sites_Abstract {

	protected const ROUTE = parent::ROUTE.'/live';
	protected const LIVE = true;

}