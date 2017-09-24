<?php

/**
 * Event dates come in 3 different formats. The format is defined by the TYP attribute in the OEFFNUNGSZEITEN tag.
 *
 * @param SimpleXMLElement  $xml_element	Part of parsed XML
 * 
 * @return Integer  Type of event, 0 is no event
 */
function ig_ncal_get_type( $xml_element ) {
	
	if ( ((string) $xml_element->OEFFNUNGSZEITEN->attributes()['TYP']) == "1" )
		return 1;
	elseif ( ((string) $xml_element->OEFFNUNGSZEITEN->attributes()['TYP']) == "2" )
		return 2;
	elseif ( ((string) $xml_element->OEFFNUNGSZEITEN->attributes()['TYP']) == "3" )
		return 3;
	else
		return 0;
}

/**
 * Determine date type and parse into array of dates.
 *
 * @param SimpleXMLElement $xml_element  Part of parsed XML
 * 
 * @return 
 */ 
function ig_ncal_parse_dates ( $xml_element ) {
	$date_type = ig_ncal_get_type( $xml_element );
	if ( 1 == $date_type ) {
		return ig_ncal_parse_oeffnungszeiten_type1( $xml_element );
	}
	elseif ( 2 == $date_type ) {
		return ig_ncal_parse_oeffnungszeiten_type2( $xml_element );
	}
	elseif ( 3 == $date_type ) {
		return ig_ncal_parse_oeffnungszeiten_type3( $xml_element );
	}
}

/**
 * Parse event format type 1 into array of events.
 *
 * @param SimpleXMLElement $xml_element  Part of parsed XML
 * 
 * @return Array
 */ 
function ig_ncal_parse_oeffnungszeiten_type1( $xml_element ) {
	$dates = array();
	$dates[0]['event_start_date'] 	= 	(string) $xml_element->OEFFNUNGSZEITEN->DATUM;
	$dates[0]['event_end_date'] 	=	$dates[0]['event_start_date'];
	$dates[0]['event_start_time'] 	= 	(string) $xml_element->OEFFNUNGSZEITEN->DATUM->attributes()['BEGINN'];
	$dates[0]['event_end_time'] 	=	(string) $xml_element->OEFFNUNGSZEITEN->DATUM->attributes()['ENDE'];
	return $dates;
}

/**
 * Parse event format type 2 into array of events.
 *
 * @param SimpleXMLElement $xml_element  Part of parsed XML
 * 
 * @return Array
 */ 
function ig_ncal_parse_oeffnungszeiten_type2( $xml_element ) {
	$dates = array();
	$n = 0;
	foreach ( $xml_element->OEFFNUNGSZEITEN->DATUM as $date ) {
		$dates[$n]['event_start_date'] 	= 	(string) $date;
		$dates[$n]['event_end_date'] 	=	$dates[$n]['event_start_date'];
		$dates[$n]['event_start_time'] 	= 	(string) $date->attributes()['BEGINN'];
		$dates[$n]['event_end_time'] 	=	(string) $date->attributes()['ENDE'];
		$n++;
	}
	return $dates;
}

/**
 * Parse event format type 3 into array of events.
 *
 * @param SimpleXMLElement $xml_element  Part of parsed XML
 * 
 * @return Array
 */ 
function ig_ncal_parse_oeffnungszeiten_type3( $xml_element ) {
	$dates = array();

	// create list of dates between start and end
	$begin = new DateTime( (string) $xml_element->OEFFNUNGSZEITEN->DATUM1 );
	$end = new DateTime( (string) $xml_element->OEFFNUNGSZEITEN->DATUM2 );
	$interval = new DateInterval('P1D');
	$daterange = new DatePeriod($begin, $interval ,$end);

	$exceptions = split(';', (string) $xml_element->OEFFNUNGSZEITEN->AUSNAHMEN );

	$n = 0;
	/*
	 * Iterate through list of dates and skip exception dates
	 */
	foreach($daterange as $date){
		if ( !in_array( $date->format("Y-m-d"), $exceptions ) )
			echo $date->format("Y-m-d") . "<br>";
			
			// drop days that are not listed weekdays and add start and end time

	}
}


class IG_NCAL_Event extends EM_Event {
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
		$this->post_content = (string) $xml_element->UNTERTITEL . "<br><a href='" . (string) $xml_element->DETAILLINK . "'>" . (string) $xml_element->DETAILLINK . "</a>";
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

		//echo "<p>" + (string) $xml_element->OEFFNUNGSZEITEN->attributes()['TYP'] + "</p>";

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
