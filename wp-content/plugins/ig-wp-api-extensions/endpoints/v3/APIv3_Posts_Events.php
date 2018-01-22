<?php

class APIv3_Posts_Events extends APIv3_Posts_Abstract {

	const ROUTE = 'events';
	const POST_TYPE = 'event';

	private $recurring_meta_event;

	public function get_posts(WP_REST_Request $request) {
		/*
		 * Add filters to the SQL query to make it work with events.
		 */
		add_filter('posts_fields', [ $this, 'select_events' ]);
		add_filter('posts_join', [ $this, 'join_events' ]);
		/*
		 * Get all events which are not recurring with the same query as for posts:
		 */
		$events_query = new WP_Query([
			'post_type' => 'event',
			'post_status' => 'publish',
			'orderby' => 'id',
			'order'   => 'ASC',
		]);
		$events = [];
		foreach ($events_query->posts as $event) {
			$events[] = $this->prepare($event);
		}
		/*
		 * Get the meta-events of all recurring events:
		 */
		$recurring_meta_events_query = new WP_Query([
			'post_type' => 'event-recurring',
			'post_status' => 'publish',
			'orderby' => 'id',
			'order'   => 'ASC',
		]);
		/*
		 * Add filters to the SQL query to make it work with recurring events.
		 */
		add_filter('posts_join', [ $this, 'join_translations' ]);
		add_filter( 'posts_where', [ $this, 'where_recurrence' ] );
		$recurring_events = [];
		foreach($recurring_meta_events_query->posts as $this->recurring_meta_event) {
			$recurring_events_query = new WP_Query([
				'post_type' => 'event',
				'post_status' => 'publish',
				'orderby' => 'id',
				'order'   => 'ASC',
			]);
			foreach ($recurring_events_query->posts as $recurring_event) {
				$recurring_events[] = $this->prepare($recurring_event);
			}
		}
		/*
		 * Remove all filters so they don't affect other queries
		 */
		remove_filter('posts_fields', [ $this, 'select_events' ]);
		remove_filter('posts_join', [ $this, 'join_events' ]);
		remove_filter('posts_join', [ $this, 'join_translations' ]);
		remove_filter('posts_where', [ $this, 'where_recurrence' ]);
		return parent::get_changed_posts($request, array_merge($events, $recurring_events));
	}

	/*
	 * Define all fields we want to select to ensure that the where-conditions and joins work properly.
	 */
	public function select_events(String $sql) {
		$post_fields = [
			'ID',
			'post_title',
			'post_type',
			'post_status',
			'post_modified_gmt',
			'post_excerpt',
			'post_content',
			'post_parent',
			'menu_order',
		];
		$event_fields = [
			'event_id',
			'event_start_date',
			'event_end_date',
			'event_all_day',
			'event_start_time',
			'event_end_time',
			'recurrence_id',
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
		$event_fields = array_map(function ($field) { return 'em_events.'.$field; }, $event_fields);
		$location_fields = array_map(function ($field) { return 'em_locations.'.$field; }, $location_fields);
		return implode(', ', array_merge($post_fields, $event_fields, $location_fields));
	}

	public function join_events(String $sql) {
		global $wpdb;
		return $sql." JOIN {$wpdb->prefix}em_events AS em_events ON em_events.post_id = {$wpdb->prefix}posts.ID
			LEFT JOIN {$wpdb->prefix}em_locations AS em_locations ON em_events.location_id = em_locations.location_id";
	}

	/*
	 * The $sql code already contains a join with the translations table - we just have to modify it for recurrent events
	 */
	public function join_translations(String $sql) {
		global $wpdb;
		$sql = str_replace("{$wpdb->prefix}posts.ID = t.element_id", "t.element_id = '{$this->recurring_meta_event->ID}'", $sql);
		$sql = str_replace("t.element_type = CONCAT('post_', {$wpdb->prefix}posts.post_type)", "t.element_type = 'post_event-recurring'", $sql);
		return $sql;
	}

	public function where_recurrence(String $sql) {
		return $sql." AND em_events.recurrence_id = '{$this->recurring_meta_event->event_id}'";
	}

	protected function prepare(WP_Post $event) {
		$prepared_event = parent::prepare($event);
		$prepared_event['event'] = $this->prepare_event($event);
		$prepared_event['location'] = $this->prepare_location($event);
		unset($prepared_event['hash']);
		$prepared_event['hash'] = md5(json_encode($prepared_event));
		return $prepared_event;
	}

	private function prepare_event(WP_Post $event) {
		return [
			'id' => (int) $event->event_id,
			'start_date' => $event->event_start_date,
			'end_date' => $event->event_end_date,
			'all_day' => (bool) $event->event_all_day,
			'start_time' => $event->event_start_time,
			'end_time' => $event->event_end_time,
			'recurrence_id' => $event->recurrence_id // event_id of meta-event (null if not recurring)
		];
	}

	private function prepare_location(WP_Post $event) {
		return [
			'id' => is_string($event->location_id) ? (int) $event->location_id : $event->location_id,
			'name' => $event->location_name,
			'address' => $event->location_address,
			'town' => $event->location_town,
			'state' => $event->location_state,
			'postcode' => $event->location_postcode,
			'region' => $event->location_region,
			'country' => $event->location_country,
			'latitude' => $event->location_latitude,
			'longitude' => $event->location_longitude
		];
	}

}
