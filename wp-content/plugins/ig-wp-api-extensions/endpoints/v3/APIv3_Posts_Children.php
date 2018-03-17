<?php

class APIv3_Posts_Children extends APIv3_Posts_Relatives_Abstract {

	const ROUTE = 'children';

	public function get_children(WP_REST_Request $request) {
		$children = (new WP_Query([
			'post_type' => static::POST_TYPE,
			'post_status' => 'publish',
			'post_parent' => $this->get_post($request)->ID,
			'orderby' => 'menu_order post_title',
			'order'   => 'ASC',
			'posts_per_page' => -1,
		]))->posts;
		return array_map([$this, 'prepare'], $children);
	}

}
