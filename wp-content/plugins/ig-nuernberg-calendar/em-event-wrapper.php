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
 * @param boolean $multiple  Create multiple events if true
 * 
 * @return 
 */ 
function ig_ncal_get_dates ( $xml_element, $multiple = false ) {
	$date_type = ig_ncal_get_type( $xml_element );
	if ( 1 == $date_type ) {
		return ig_ncal_get_oez_type1( $xml_element );
	}
	elseif ( 2 == $date_type) {
		if ( $multiple == true )
			return ig_ncal_get_oez_type2_multiple( $xml_element );
		else
			return ig_ncal_get_oez_type2_single( $xml_element );
	}
	elseif ( 3 == $date_type ) {
		if ( $multiple == true )
			return ig_ncal_get_oez_type3_multiple( $xml_element );
		else
			return ig_ncal_get_oez_type3_single( $xml_element );
	}
}

/**
 * Parse event format type 1 into array of events. Array contains a single element.
 *
 * @param SimpleXMLElement $xml_element Part of parsed XML
 * 
 * @return Array
 */ 
function ig_ncal_get_oez_type1( $xml_element ) {
	$dates = array();
	$dates[0]['event_start_date'] 	= 	(string) $xml_element->OEFFNUNGSZEITEN->DATUM;
	$dates[0]['event_end_date'] 	=	$dates[0]['event_start_date'];
	$dates[0]['event_start_time'] 	= 	(string) $xml_element->OEFFNUNGSZEITEN->DATUM->attributes()['BEGINN'];
	$dates[0]['event_end_time'] 	=	(string) $xml_element->OEFFNUNGSZEITEN->DATUM->attributes()['ENDE'];
	return $dates;
}

/**
 * Parse event format type 2 into array of events. Array can contain multiple elements.
 *
 * @param SimpleXMLElement $xml_element  Part of parsed XML
 * 
 * @return Array
 */ 
function ig_ncal_get_oez_type2_multiple( $xml_element ) {
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
 * Parse event format type 2 into array of events. Array contains a single element.
 *
 * @param SimpleXMLElement $xml_element  Part of parsed XML
 * 
 * @return Array
 */ 
function ig_ncal_get_oez_type2_single( $xml_element ) {
	$dates = array();
	$dates[$n]['event_start_time'] 	= 	$xml_element->OEFFNUNGSZEITEN->DATUM1;
	$dates[$n]['event_end_time'] 	=	$xml_element->OEFFNUNGSZEITEN->DATUM2;
	$dates[$n]['event_start_time'] 	= 	"00:00";
	$dates[$n]['event_end_time'] 	=	"23:59";
	return $dates;
}

/**
 * Parse event format type 3 into array of events. Array can contain multiple elements.
 *
 * @param SimpleXMLElement $xml_element  Part of parsed XML
 * 
 * @return Array
 */ 
function ig_ncal_get_oez_type3_multiple( $xml_element ) {
	$dates = array();

	/*
	 * create list of dates between start and end
	 */
	$begin = new DateTime( (string) $xml_element->OEFFNUNGSZEITEN->DATUM1 );
	$end = new DateTime( (string) $xml_element->OEFFNUNGSZEITEN->DATUM2 );
	$interval = new DateInterval('P1D');
	$daterange = new DatePeriod($begin, $interval ,$end);

	$exceptions = split(';', (string) $xml_element->OEFFNUNGSZEITEN->AUSNAHMEN );

	/*
	 * Event start and end times are defined per weekday. Get number of weekday and the start and end times.
	 */
	$day = 0;
	$weekdays = array();
	foreach ( $xml_element->OEFFNUNGSZEITEN->OFFENETAGE->OFFENERTAG as $date ) {
		//var_dump( (string) $date );
		if( (string) $date == "mo" )
			$day = 1;
		elseif( (string) $date == "di" )
			$day = 2;
		elseif( (string) $date == "mi" )
			$day = 3;
		elseif( (string) $date == "do" )
			$day = 4;
		elseif( (string) $date == "fr" )
			$day = 5;
		elseif( (string) $date == "sa" )
			$day = 6;
		elseif( (string) $date == "so" )
			$day = 7;
		else
			continue;
		$weekdays[$day]['day'] 					=	$day;
		$weekdays[$day]['event_start_time'] 	= 	(string) $date->attributes()['BEGINN'];
		$weekdays[$day]['event_end_time'] 		=	(string) $date->attributes()['ENDE'];
	}

	$n = 0;
	/*
	 * Iterate through list of dates and skip exception dates. Set start and end time for each day.
	 */
	foreach($daterange as $date){
		$day = $date->format("N");
		$date = $date->format("Y-m-d");
		if ( !in_array( $date, $exceptions ) ) {		
			$dates[$n]['event_start_date'] 	= 	$date;
			$dates[$n]['event_end_date'] 	=	$date;
			$dates[$n]['event_start_time'] 	= 	$weekdays[$day]['event_start_time'];
			$dates[$n]['event_end_time'] 	=	$weekdays[$day]['event_end_time'];
			$dates[$n];
			$n++;
		}
	}
	return $dates;
}


/**
 * Parse event format type 3 into array of events. Array contains a single element.
 *
 * @param SimpleXMLElement $xml_element  Part of parsed XML
 * 
 * @return Array
 */ 
function ig_ncal_get_oez_type3_single( $xml_element ) {
	$dates = array();
	$dates[$n]['event_start_date'] 	= 	$xml_element->OEFFNUNGSZEITEN->DATUM1;
	$dates[$n]['event_end_date'] 	=	$xml_element->OEFFNUNGSZEITEN->DATUM2;
	$dates[$n]['event_start_time'] 	= 	"00:00";
	$dates[$n]['event_end_time'] 	=	"23:59";
	return $dates;
}


/**
 * Parse event format type 2 and 3 dates into text.
 *
 * @param SimpleXMLElement $xml_element  Part of parsed XML
 * 
 * @return string
 */ 
function ig_ncal_dates_to_text( $type, $xml_element, $multiple = false ) {
	$text = "<p>&Ouml;ffnungszeiten<br>";
	if( $type == 2 )
		$dates = ig_ncal_get_oez_type2_multiple( $xml_element );
	else
		$dates = ig_ncal_get_oez_type3_multiple( $xml_element );
	foreach ( $dates as $date ) {
		$text .= $date['event_start_date'] . ": " . $date['event_start_time'] . " Uhr " . ( $date['event_end_time'] != "" ? " bis " . $date['event_end_time'] . " Uhr" : "" ) . "<br>";
	}
	return $text;
}


class IG_NCAL_Event extends EM_Event {
	function import_xml_data( $date, $xml_element ) {
		/*
		 * Type as defined by API (OEFFNUNGSZEIT TYPE)
		 */
		$this->event_type = ig_ncal_get_type( $xml_element );

        $this->ig_nue_source_id = (int) $xml_element['ID'];
		// $this->event_slug;
		$this->event_owner = get_current_user_id();
		$this->event_name = (string) $xml_element->TITEL;
		$this->event_start_time = $date['event_start_time'];
		$this->event_end_time = $date['event_end_time'];
		$this->event_start_date = $date['event_start_time'];
		$this->event_end_date = $date['event_start_time'];
		$this->post_content = (string) $xml_element->UNTERTITEL . "<br><a href='" . (string) $xml_element->DETAILLINK . "'>" . (string) $xml_element->DETAILLINK . "</a>";
		$multiple = false;
		if( $this->event_type == 1 ) {
			/*
			 * If this is an event type 1 it contains only one timeslot. Nothing special has to be done.
			 */
			$this->event_all_day = False;
		} elseif( $multiple == false ) {
			/*
			 * Event types 2 and 3 contain long lists of timeslots. Parse them to text and add them to the description text.
			 */
			
			$this->event_all_day = True;
			$this->post_content .=  ig_ncal_dates_to_text( $this->event_type, $xml_element, $multiple );
		}
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

	function save_nue_event ( $dry_run = false ) {
		/**
		 * Use the EM saving function. But we need to create an additional post meta with the source ID.
		**/
		//$save_location_return = $this->location->save();
		if( $dry_run == false) {
			$this->location_id = $this->location->save();
		} else {
			echo "<p>Not saving location ".$this->location->location_name."</p>";
		}

		if( $dry_run == false) {
			$save_return = $this->save();
		} else {
			echo "<p>Not saving event:<br>";
			var_dump($this);
			echo "</p>";
		}
		if( $this->post_id ) {
			if( $dry_run == false) {
				update_post_meta( $this->post_id, 'ncal_event_id', $this->ig_nue_source_id );
			} else {
				echo "<p>Not saving post meta: ".$this->post_id." ".'ncal_event_id'." ".$this->ig_nue_source_id;
			}
		}
	}
}

?>
