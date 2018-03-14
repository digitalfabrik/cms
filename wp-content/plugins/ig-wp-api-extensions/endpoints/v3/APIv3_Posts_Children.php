<?php

class APIv3_Posts_Children extends APIv3_Posts_Relatives_Abstract {

	const ROUTE = 'children';

	public function get_children(WP_REST_Request $request) {
		$post = $this->get_post($request);
		if (is_wp_error($post)) {
			return $post;
		}
		return 'id: '.$post->id;
		$query = new WP_Query([
			'post_type' => static::POST_TYPE,
			'post_status' => 'publish',
			'post_parent' => $post->id,
			'orderby' => 'menu_order post_title',
			'order'   => 'ASC',
			'posts_per_page' => -1,
		]);
		return array_map([$this, 'prepare'], $query->posts);
	}

}
