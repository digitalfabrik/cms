<?php

require_once __DIR__ . '/RestApi_ModifiedContent.php';
require_once __DIR__ . '/helper/WpmlHelper.php';

/**
 * Retrieve only content that has been modified since a given datetime
 */
class RestApi_ModifiedEventsV0 extends RestApi_ModifiedContentV0 {
	
	protected function get_subpath() {
		return '/events/';
	}

	protected function get_posts_type() {
		return 'event';
	}

	protected function build_query_select() {
		return parent::build_query_select() . ",
			em_events.event_id,
			em_events.event_start_date, em_events.event_end_date,
			em_events.event_all_day, em_events.event_start_time, em_events.event_end_time,
			em_events.recurrence_id, em_events.recurrence, em_events.recurrence_interval,
			em_events.recurrence_freq, em_events.recurrence_byday, em_events.recurrence_byweekno,
			em_events.recurrence_days, em_events.recurrence_rsvp_days,
			em_locations.location_id, em_locations.location_name,
			em_locations.location_address, em_locations.location_town, em_locations.location_state, em_locations.location_postcode,
			em_locations.location_region, em_locations.location_country,
			em_locations.location_latitude, em_locations.location_longitude,
			GROUP_CONCAT(CONCAT(terms.term_id, ':', terms.name)) AS terms";
	}

	protected function build_query_from($initial_event = null) {
		global $wpdb;
		return parent::build_query_from($initial_event) . "
			JOIN {$wpdb->prefix}em_events em_events
					ON em_events.post_id = posts.ID
			LEFT JOIN {$wpdb->prefix}em_locations em_locations
					ON em_events.location_id = em_locations.location_id
			LEFT JOIN {$wpdb->prefix}term_relationships term_relationships
					ON term_relationships.object_id = posts.ID
			LEFT JOIN {$wpdb->prefix}terms terms
					ON terms.term_id = term_relationships.term_taxonomy_id";
	}

	protected function build_query_groups() {
		$groups = parent::build_query_groups();
		$groups[] = "posts.id";
		return $groups;
	}


	protected function prepare_item($post) {
		$item = parent::prepare_item($post);
		$event = $this->prepare_additional($post);
		return array_merge($item, $event);
	}

	private function prepare_additional($post) {
		return [
			'event' => $this->prepare_event($post),
			'location' => $this->prepare_location($post),
			'tags' => $this->prepare_tags($post),
			'categories' => $this->prepare_categories($post),
			'page' => $this->prepare_page($post),
			'recurrence_id' => $post->recurrence_id // event_id of meta-event
		];
	}

	private function prepare_event($post) {
		return [
			'id' => $post->event_id,
			'start_date' => $post->event_start_date,
			'end_date' => $post->event_end_date,
			'all_day' => $post->event_all_day,
			'start_time' => $post->event_start_time,
			'end_time' => $post->event_end_time
		];
	}

	private function prepare_location($post) {
		return [
			'id' => $post->location_id,
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

	private function prepare_tags($post) {
		if (empty($post->terms)) {
			return [];
		}
		$elements = explode(",", $post->terms);
		return array_map(function ($idname) {
			$pair = explode(":", $idname);
			return ["id" => $pair[0], "name" => $pair[1]];
		}, $elements);
	}

	private function prepare_categories($post) {
		return []; // disabled for now - keep in the result to indicate the possibility
	}

	private function prepare_page($post) {
		return null; // disabled for now - keep in the result to indicate the possibility
	}
}
