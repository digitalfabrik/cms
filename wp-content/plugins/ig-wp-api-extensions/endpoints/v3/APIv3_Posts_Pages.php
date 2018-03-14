<?php

class APIv3_Posts_Pages extends APIv3_Posts_Abstract {

	const ROUTE = 'pages';
	const POST_TYPE = 'page';

	public function get_pages(WP_REST_Request $request) {
		return $this->get_posts($request);
	}

}
