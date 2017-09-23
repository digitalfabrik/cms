<?php

class IG_NUE_Event extends EM_Event {
	var $ig_nue_source_id;


	function import_xml_data( $xml_element ) {
		$this->ig_nue_source_id;
		$this->event_slug;
		$this->event_owner;
		$this->event_name;
		$this->event_start_time;
		$this->event_end_time;
		$this->event_all_day;
		$this->event_start_date;
		$this->event_end_date;
		$this->post_content;
		$this->event_rsvp;
		$this->event_rsvp_date;
		$this->event_rsvp_time = "00:00:00";
		$this->event_rsvp_spaces;
		$this->event_spaces;
		$this->event_private;
		$this->location_id;
		$this->recurrence_id;
		$this->event_status;
		$this->blog_id;
		$this->group_id;
		$this->event_attributes = array();
		$this->recurrence;
		$this->recurrence_interval;
		$this->recurrence_freq;
		$this->recurrence_byday;
		$this->recurrence_days = 0;
		$this->recurrence_byweekno;
		$this->recurrence_rsvp_days;
		$this->event_owner_anonymous;
		$this->event_owner_name;
		$this->event_owner_email;
		echo "<p>";
		var_dump( (string)$event->ORT );
		echo "</p>";
	}

	function save_nue_event () {
		
	}
}

?>
