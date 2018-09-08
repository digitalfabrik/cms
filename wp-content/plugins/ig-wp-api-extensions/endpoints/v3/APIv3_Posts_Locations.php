<?php

class APIv3_Posts_Locations extends APIv3_Posts_Abstract {

	const ROUTE = 'locations';
	const POST_TYPE = 'location';

	public function get_locations(WP_REST_Request $request) {
		/*
		 * Add filters to the SQL query to make it work with events.
		 */
		add_filter('posts_fields', [ $this, 'select_locations' ]);
		add_filter('posts_join', [ $this, 'join_locations' ]);
		/*
		 * Get all events which are not recurring with the same query as for posts:
		 */
		$events_query = new WP_Query([
			'post_type' => 'location',
			'post_status' => 'publish',
			'orderby' => 'id',
			'order'   => 'ASC',
			'posts_per_page' => -1,
		]);
		$locations = [];
		foreach ($events_query->posts as $event) {
			$locations[] = $this->prepare($event);
		}
		/*
		 * Remove all filters so they don't affect other queries
		 */
		remove_filter('posts_fields', [ $this, 'select_locations' ]);
		remove_filter('posts_join', [ $this, 'join_locations' ]);
		return parent::get_changed_posts($request, $locations);
	}

	/*
	 * Define all fields we want to select to ensure that the where-conditions and joins work properly.
	 */
	public function select_locations($sql) {
		$post_fields = [
			'ID',
			'post_title',
			'post_type',
			'post_status',
			'post_name',
			'post_modified_gmt',
			'post_excerpt',
			'post_content',
			'post_parent',
			'menu_order',
		];
		$location_fields = [
			'location_id',
			'location_name',
			'location_address',
			'location_town',
			'location_state',
			'location_postcode',
			'location_region',
			'location_country',
			'location_latitude',
			'location_longitude',
		];
		$post_fields = array_map(function ($field) { return $GLOBALS['wpdb']->prefix.'posts.'.$field; }, $post_fields);
		$location_fields = array_map(function ($field) { return 'em_locations.'.$field; }, $location_fields);
		return implode(', ', array_merge($post_fields, $location_fields));
	}

	public function join_locations($sql) {
		global $wpdb;
		return $sql." JOIN {$wpdb->prefix}em_locations AS em_locations ON em_locations.post_id = {$wpdb->prefix}posts.ID";
	}

	protected function prepare(WP_Post $location) {
		$prepared_location = parent::prepare($location);
		$prepared_location['location'] = $this->prepare_location($location);
		unset($prepared_location['parent']);
		unset($prepared_location['order']);
		unset($prepared_location['hash']);
		$prepared_location['hash'] = md5(json_encode($prepared_location));
		return $prepared_location;
	}

	private function prepare_location(WP_Post $location) {
		return [
			'id' => is_string($location->location_id) ? (int) $location->location_id : $location->location_id,
			'name' => $location->location_name,
			'address' => $location->location_address,
			'town' => $location->location_town,
			'state' => $location->location_state,
			'postcode' => $location->location_postcode,
			'region' => $location->location_region,
			'country' => $location->location_country,
			'latitude' => $location->location_latitude,
			'longitude' => $location->location_longitude
		];
	}

}
