<?php

class APIv3_Posts_Pages extends APIv3_Posts_Abstract {

	protected const ROUTE = parent::ROUTE.'/pages';
	protected const POST_TYPE = 'page';

}
