<?php 
//define and clean up formats for display
$summary_format = str_replace ( ">", "&gt;", str_replace ( "<", "&lt;", get_option ( 'dbem_ical_description_format' ) ) );
$description_format = str_replace ( ">", "&gt;", str_replace ( "<", "&lt;", get_option ( 'dbem_ical_real_description_format') ) );
$location_format = str_replace ( ">", "&gt;", str_replace ( "<", "&lt;", get_option ( 'dbem_ical_location_format' ) ) );
$parsed_url = parse_url(get_bloginfo('url'));
$site_domain = preg_replace('/^www./', '', $parsed_url['host']);
$timezone_support = defined('EM_ICAL_TIMEZONE_SUPPORT') ? EM_ICAL_TIMEZONE_SUPPORT : true;

//figure out limits
$ical_limit = get_option('dbem_ical_limit');
$page_limit = $ical_limit > 50 || !$ical_limit ? 50:$ical_limit; //set a limit of 50 to output at a time, unless overall limit is lower
//get passed on $args and merge with defaults
$args = !empty($args) ? $args:array(); /* @var $args array */
$args = array_merge(array('limit'=>$page_limit, 'page'=>'1', 'owner'=>false, 'orderby'=>'event_start_date,event_start_time', 'scope' => get_option('dbem_ical_scope') ), $args);
$args = apply_filters('em_calendar_template_args',$args);
//get first round of events to show, we'll start adding more via the while loop
$EM_Events = EM_Events::get( $args );
$timezones = array();

//calendar header
$output_header = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//wp-events-plugin.com//".EM_VERSION."//EN";

//if timezone is supported, we output the blog timezone here
if( $timezone_support ){
	//get default blog timezone and output only if we're not in UTC or with manual offsets
	$blog_timezone = EM_DateTimeZone::create()->getName();
	if( !preg_match('/^UTC/', $blog_timezone) ){
		$output_header .= "
TZID:{$blog_timezone}
X-WR-TIMEZONE:{$blog_timezone}";
	}
}

echo preg_replace("/([^\r])\n/", "$1\r\n", $output_header);

//loop through events
$count = 0;
while ( count($EM_Events) > 0 ){
	foreach ( $EM_Events as $EM_Event ) {
		/* @var $EM_Event EM_Event */
	    if( $ical_limit != 0 && $count > $ical_limit ) break; //we've reached our maximum
	    //figure out the timezone of this event, or if it's an offset and add to list of timezones and date ranges to define in VTIMEZONE
	    $show_timezone = $timezone_support && !preg_match('/^UTC/', $EM_Event->get_timezone()->getName());
	    if( $show_timezone ){
	    	$timezone = $EM_Event->start()->getTimezone()->getName();
	    	if( empty($timezones[$timezone]) ){
	    		$timezones[$timezone] = array( $EM_Event->start()->getTimestamp(), $EM_Event->end()->getTimestamp() );
	    	}else{
	    		if( $timezones[$timezone][0] > $EM_Event->start()->getTimestamp() ) $timezones[$timezone][0] = $EM_Event->start()->getTimestamp();
	    		if( $timezones[$timezone][1] < $EM_Event->end()->getTimestamp() ) $timezones[$timezone][1] = $EM_Event->end()->getTimestamp();
	    	}
	    }
	    //calculate the times along with timezone offsets
		if($EM_Event->event_all_day){
			//we get local time since we're representing a date not a time
			$dateStart	= ';VALUE=DATE:'.$EM_Event->start()->format('Ymd'); //all day
			$dateEnd	= ';VALUE=DATE:'.$EM_Event->end()->copy()->add('P1D')->format('Ymd'); //add one day
		}else{
			//get date output with timezone and local time if timezone output is enabled, or UTC time if not and/or if offset is manual
			if( $show_timezone ){
				//show local time and define a timezone
				$dateStart	= ':'.$EM_Event->start()->format('Ymd\THis');
				$dateEnd = ':'.$EM_Event->end()->format('Ymd\THis');
			}else{
				//create a UTC equivalent time for all events irrespective of timezone
				$dateStart	= ':'.$EM_Event->start(true)->format('Ymd\THis\Z');
				$dateEnd = ':'.$EM_Event->end(true)->format('Ymd\THis\Z');
			}
		}
		if( $show_timezone ){
			$dateStart = ';TZID='.$timezone . $dateStart;
			$dateEnd = ';TZID='.$timezone . $dateEnd;
		}
		if( !empty($EM_Event->event_date_modified) && $EM_Event->event_date_modified != '0000-00-00 00:00:00' ){
			$dateModified =  get_gmt_from_date($EM_Event->event_date_modified, 'Ymd\THis\Z');
		}else{
		    $dateModified = get_gmt_from_date($EM_Event->post_modified, 'Ymd\THis\Z');
		}
		
		//formats
		$summary = em_mb_ical_wordwrap('SUMMARY:'.$EM_Event->output($summary_format,'ical'));
		$description = em_mb_ical_wordwrap('DESCRIPTION:'.$EM_Event->output($description_format,'ical'));
		$url = 'URL:'.$EM_Event->get_permalink();
		$url = wordwrap($url, 74, "\n ", true);
		$location = $geo = $apple_geo = $apple_location = $apple_location_title = $apple_structured_location = $categories = false;
		if( $EM_Event->location_id ){
			$location = em_mb_ical_wordwrap('LOCATION:'.$EM_Event->output($location_format, 'ical'));
			if( $EM_Event->get_location()->location_latitude || $EM_Event->get_location()->location_longitude ){
				$geo = 'GEO:'.$EM_Event->get_location()->location_latitude.";".$EM_Event->get_location()->location_longitude;
			}
			if( !defined('EM_ICAL_APPLE_STRUCT') || !EM_ICAL_APPLE_STRUCT ){
				$apple_location = $EM_Event->output('#_LOCATIONFULLLINE, #_LOCATIONCOUNTRY', 'ical');
				$apple_location_title = $EM_Event->output('#_LOCATIONNAME', 'ical');
				$apple_geo = !empty($geo) ? $EM_Event->get_location()->location_latitude.",".$EM_Event->get_location()->location_longitude:'0,0';
				$apple_structured_location = "X-APPLE-STRUCTURED-LOCATION;VALUE=URI;X-ADDRESS={$apple_location};X-APPLE-RADIUS=100;X-TITLE={$apple_location_title}:geo:{$apple_geo}";
				$apple_structured_location = str_replace('"', '\"', $apple_structured_location); //google chucks a wobbly with these on this line
				$apple_structured_location = em_mb_ical_wordwrap($apple_structured_location);
			}
		}
		$categories = array();
		foreach( $EM_Event->get_categories() as $EM_Category ){ /* @var EM_Category $EM_Category */
			$categories[] = $EM_Category->name;
		}
		$image = $EM_Event->get_image_url();
		
		//create a UID, make it unique and update independent
		$UID = $EM_Event->event_id . '@' . $site_domain;
		if( is_multisite() ) $UID = absint($EM_Event->blog_id) . '-' . $UID;
		$UID = wordwrap("UID:".$UID, 74, "\r\n ", true);
		
//output ical item		
$output = "\r\n"."BEGIN:VEVENT
{$UID}
DTSTART{$dateStart}
DTEND{$dateEnd}
DTSTAMP:{$dateModified}
{$url}
{$summary}";
//Description if available
if( $description ){
    $output .= "\r\n" . $description;
}
//add featured image if exists
if( $image ){
	$image = wordwrap("ATTACH;FMTTYPE=image/jpeg:".esc_url_raw($image), 74, "\n ", true);
	$output .= "\r\n" . $image;
}
//add categories if there are any
if( !empty($categories) ){
	$categories = wordwrap("CATEGORIES:".implode(',', $categories), 74, "\n ", true);
	$output .= "\r\n" . $categories;
}
//Location if there is one
if( $location ){
	$output .= "\r\n" . $location;
	//geo coordinates if they exist
	if( $geo ){
		$output .= "\r\n" . $geo;
	}
	//create apple-compatible feature for locations
	if( !empty($apple_structured_location) ){
		$output .= "\r\n" . $apple_structured_location;
	}
}
//end the event
$output .= "
END:VEVENT";

		//clean up new lines, rinse and repeat
		echo preg_replace("/([^\r])\n/", "$1\r\n", $output);
		$count++;
	}
	if( $ical_limit != 0 && $count >= $ical_limit ){ 
	    //we've reached our limit, or showing one event only
	    break;
	}else{
	    //get next page of results
	    $args['page']++;
		$EM_Events = EM_Events::get( $args );
	}
}

//Now we sort out timezones and add it to the top of the output
if( $timezone_support && !empty($timezones) ){
	$vtimezones = array();
	foreach( $timezones as $timezone => $timezone_range ){
		$vtimezones[$timezone] = array();
		$previous_offset = false;
		//get the range of transitions, with a year's cushion so we can calculate the TZOFFSETFROM value
		$EM_DateTimeZone = EM_DateTimeZone::create($timezone);
		$timezone_transitions = $EM_DateTimeZone->getTransitions($timezone_range[0] - YEAR_IN_SECONDS, $timezone_range[1] + YEAR_IN_SECONDS);
		do{
			$current_transition = current($timezone_transitions);
			$transition_key = key($timezone_transitions);
			$next_transition = next($timezone_transitions);
			//format the offset to a UTC-OFFSET
			$current_offset_sign = $current_transition['offset'] < 0 ? '-' : '+';
			$current_offset_hours = absint(floor($current_transition['offset'] / HOUR_IN_SECONDS));
			$current_offset_minute_seconds = absint($current_transition['offset']) - $current_offset_hours*HOUR_IN_SECONDS;
			$current_offset_minutes = $current_offset_minute_seconds == 0 ? 0 : absint($current_offset_minute_seconds / MINUTE_IN_SECONDS);
			$current_transition['offset'] = $current_offset_sign . str_pad($current_offset_hours, 2, "0", STR_PAD_LEFT) . str_pad($current_offset_minutes, 2, "0", STR_PAD_LEFT);
			//skip transitions before and after the event date range, assuming we have some in between
			if( !empty($next_transition) && $next_transition['ts'] < $timezone_range[0] ){
				//remember previous offset
				$previous_offset = $current_transition['offset'];
				continue;
			}
			if( $current_transition['ts'] > $timezone_range[1] ) break;
			//modify the transition array directly and add it to vtimezones array
			unset( $current_transition['time'] );
			$current_transition['isdst'] = $current_transition['isdst'] ? 'DAYLIGHT':'STANDARD';
			$EM_DateTime = new EM_DateTime($current_transition['ts'], $EM_DateTimeZone);
			$current_transition['ts'] = $EM_DateTime->format('Ymd\THis');
			$current_transition['offsetfrom'] = $previous_offset === false ? $current_transition['offset'] : $previous_offset;
			$vtimezones[$timezone][] = $current_transition;
			//remember previous offset
			$previous_offset = $current_transition['offset'];
		} while( $next_transition !== false );
	}
	foreach( $vtimezones as $timezone => $timezone_transitions ){
		$output = "
BEGIN:VTIMEZONE
TZID:{$timezone}
X-LIC-LOCATION:{$timezone}";
		foreach( $timezone_transitions as $transition ){
			$output .= "
BEGIN:{$transition['isdst']}
DTSTART:{$transition['ts']}
TZOFFSETFROM:{$transition['offsetfrom']}
TZOFFSETTO:{$transition['offset']}
TZNAME:{$transition['abbr']}
END:{$transition['isdst']}";
		}
		$output .= "
END:VTIMEZONE";
		echo preg_replace("/([^\r])\n/", "$1\r\n", $output);
	}
}

//calendar footer
echo "\r\n"."END:VCALENDAR";