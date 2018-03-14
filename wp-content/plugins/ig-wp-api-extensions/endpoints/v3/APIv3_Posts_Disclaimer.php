<?php

class APIv3_Posts_Disclaimer extends APIv3_Posts_Abstract {

	const ROUTE = 'disclaimer';
	const POST_TYPE = 'disclaimer';

	public function get_disclaimer() {
		$query = new WP_Query([
			'post_type' => 'disclaimer',
			'post_status' => 'publish',
		]);
		return $query->have_posts() ? $this->prepare($query->post) : [];
	}

}
