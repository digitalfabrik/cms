<?php

require_once __DIR__ . '/RestApi_ModifiedContent.php';
require_once __DIR__ . '/helper/WpmlHelper.php';

/**
 * Retrieve only content that has been modified since a given datetime
 */
class RestApi_ModifiedEvents extends RestApi_ModifiedContent {
	protected function get_subpath() {
		return '/events/';
	}

	protected function get_posts_type() {
		return 'event';
	}

	protected function build_query_select() {
		return parent::build_query_select() . ",
			event_start_date, event_end_date, event_all_day, event_start_time, event_end_time,
			location_name, location_address, location_town, location_state, location_postcode, location_region, location_country,
			location_latitude, location_longitude";
	}

	protected function build_query_from() {
		global $wpdb;
		return parent::build_query_from() . "
			JOIN {$wpdb->prefix}em_events em_events ON em_events.post_id = posts.ID
			LEFT JOIN {$wpdb->prefix}em_locations em_locations ON em_events.location_id = em_locations.location_id";
	}


	protected function prepare_item($post) {
		$item = parent::prepare_item($post);
		$event = $this->prepare_additional($post);
		return array_merge($item, $event);
	}

	private function prepare_additional($post) {
		return [
			'event' => $this->prepare_event($post),
			'location' => $this->prepare_location($post)
		];
	}

	private function prepare_event($post) {
		return [
			'start_date' => $post->event_start_date,
			'end_date' => $post->event_end_date,
			'all_day' => $post->event_all_day,
			'start_time' => $post->event_start_time,
			'end_time' => $post->event_end_time
		];
	}

	private function prepare_location($post) {
		return [
			'name' => $post->location_name,
			'address' => $post->location_address,
			'town' => $post->location_town,
			'state' => $post->location_state,
			'postcode' => $post->location_postcode,
			'region' => $post->location_region,
			'country' => $post->location_country,
			'latitude' => $post->location_latitude,
			'longitude' => $post->location_longitude
		];
	}
}
