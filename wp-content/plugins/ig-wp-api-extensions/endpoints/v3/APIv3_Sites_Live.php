<?php

/**
 * Retrieve all live multisites
 */
class APIv3_Sites_Live extends APIv3_Sites_Abstract {

	const ROUTE = parent::ROUTE.'/live';
	const LIVE = true;

}