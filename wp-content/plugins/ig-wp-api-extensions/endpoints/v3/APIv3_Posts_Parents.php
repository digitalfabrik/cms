<?php

class APIv3_Posts_Parents extends APIv3_Posts_Relatives_Abstract {

	const ROUTE = 'parents';

	public function get_parents(WP_REST_Request $request) {
		$post = $this->get_post($request);
		$parents = [];
		while($post->post_parent !== 0) {
			$post = get_post($post->post_parent);
			$parents[] = $post;
		}
		return array_map([$this, 'prepare'], $parents);
	}

}
