<?php 
//define and clean up formats for display
$summary_format = str_replace ( ">", "&gt;", str_replace ( "<", "&lt;", get_option ( 'dbem_ical_description_format' ) ) );
$description_format = str_replace ( ">", "&gt;", str_replace ( "<", "&lt;", get_option ( 'dbem_ical_real_description_format') ) );
$location_format = str_replace ( ">", "&gt;", str_replace ( "<", "&lt;", get_option ( 'dbem_ical_location_format' ) ) );
$parsed_url = parse_url(get_bloginfo('url'));
$site_domain = preg_replace('/^www./', '', $parsed_url['host']);

//figure out limits
$ical_limit = get_option('dbem_ical_limit');
$page_limit = $ical_limit > 50 || !$ical_limit ? 50:$ical_limit; //set a limit of 50 to output at a time, unless overall limit is lower
//get passed on $args and merge with defaults
$args = !empty($args) ? $args:array(); /* @var $args array */
$args = array_merge(array('limit'=>$page_limit, 'page'=>'1', 'owner'=>false, 'orderby'=>'event_start_date', 'scope' => get_option('dbem_ical_scope') ), $args);
$args = apply_filters('em_calendar_template_args',$args);
//get first round of events to show, we'll start adding more via the while loop
$EM_Events = EM_Events::get( $args );

//calendar header
$output = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//wp-events-plugin.com//".EM_VERSION."//EN";
echo preg_replace("/([^\r])\n/", "$1\r\n", $output);

//loop through events
$count = 0;
while ( count($EM_Events) > 0 ){
	foreach ( $EM_Events as $EM_Event ) {
		/* @var $EM_Event EM_Event */
	    if( $ical_limit != 0 && $count > $ical_limit ) break; //we've reached our maximum
	    //calculate the times along with timezone offsets
		if($EM_Event->event_all_day){
			$dateStart	= ';VALUE=DATE:'.date('Ymd',$EM_Event->start); //all day
			$dateEnd	= ';VALUE=DATE:'.date('Ymd',$EM_Event->end + 86400); //add one day
		}else{
			$dateStart	= ':'.get_gmt_from_date(date('Y-m-d H:i:s', $EM_Event->start), 'Ymd\THis\Z');
			$dateEnd = ':'.get_gmt_from_date(date('Y-m-d H:i:s', $EM_Event->end), 'Ymd\THis\Z');
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
    $output .= "
{$image}";
}
//add categories if there are any
if( !empty($categories) ){
	$categories = wordwrap("CATEGORIES:".implode(',', $categories), 74, "\n ", true);
    $output .= "
{$categories}";
}
//Location if there is one
if( $location ){
	$output .= "
{$location}";
	//geo coordinates if they exist
	if( $geo ){
	$output .= "
{$geo}";
	}
	//create apple-compatible feature for locations
	if( !empty($apple_structured_location) ){
	$output .= "
{$apple_structured_location}";
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

//calendar footer
$output = "\r\n"."END:VCALENDAR";
echo preg_replace("/([^\r])\n/", "$1\r\n", $output);