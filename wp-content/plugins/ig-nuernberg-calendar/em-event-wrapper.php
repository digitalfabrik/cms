<?php

function compute_number_events() {

}

function parse_oeffnungszeiten_type1() {

}

function parse_oeffnungszeiten_type2() {

}

function parse_oeffnungszeiten_type3() {

}


class IG_NUE_Event extends EM_Event {
	var $ig_nue_source_id;


	function import_xml_data( $xml_element ) {
		$this->ig_nue_source_id = (int) $xml_element['ID'];
		// $this->event_slug;
		$this->event_owner = get_current_user_id();
		$this->event_name = (string) $xml_element->TITEL;
		$this->event_start_time;
		$this->event_end_time;
		$this->event_all_day;
		$this->event_start_date;
		$this->event_end_date;
		$this->post_content = (string) $xml_element->UNTERTITEL + "<br><a href='" + (string) $xml_element->DETAILLINK + "'>" + (string) $xml_element->DETAILLINK + "</a>";
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
		$this->location = new EM_Location;
		
		$this->location->location_name = (string) $xml_element->ORT;
	}

	function save_nue_event () {
		//var_dump($this);

		/**
		 * return, we do not want to spam the database during testing (yet)
		**/
		//return true;

		/**
		 * Use the EM saving function. But we need to create an additional post meta with the source ID.
		**/
		//$save_location_return = $this->location->save();
		$this->location_id = $this->location->id;
		echo "<p>Location: $save_location_return</p>";
		//$save_return = $this->save();
		echo "<p>Event: $save_return</p>";
		if( $this->post_id ) {
			
		}
	}
}

?>
