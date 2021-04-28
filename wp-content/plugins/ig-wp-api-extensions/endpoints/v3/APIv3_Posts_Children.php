<?php

class APIv3_Posts_Children extends APIv3_Posts_Relatives_Abstract {

	const ROUTE = 'children';

	public function get_children(WP_REST_Request $request) {
		$children = $this->get_posts_recursive(
			$this->get_post($request)->ID,
			($request['depth'] ? (int)$request['depth'] : -1)
		);
		return array_map([$this, 'prepare'], $children);
	}

}
