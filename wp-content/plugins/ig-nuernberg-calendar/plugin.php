<?php
/**
 * Plugin Name: Import Nuernberger Kalender
 * Description: Importiert Kalender der der Region Nuernberg-Fuerth-Erlangen
 * Version: 1.0
 * Author: Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */


require_once("sort-events.php");
register_activation_hook(__FILE__, 'ig_ncal_activation');

function ig_ncal_activation() {
	if (! wp_next_scheduled ( 'ig_ncal_import_event' )) {
		//wp_schedule_event(time(), 'daily', 'ig_ncal_import_event');
	}
}

/*
 * The ig_ncal_import() function is usually called by a WP cron job.
 * It fetches the data from the Nuremberg region event calendar and stores all events as Event Manager event posts.
 * The source ID is saved as a meta value. If the source ID is already stored, the event will not be processed again.
 */
add_action('ig_ncal_import_event', 'ig_ncal_import');
function ig_ncal_import() {
	/*
	 * Include PHP file containing class for handling EM events and parsing XML.
	 */
	require_once("em-event-wrapper.php");

	/*
	 * Get data from API and parse XML
	 */
	$cal_xml_file = file_get_contents('https://www.meine-veranstaltungen.net/export.php5');
	$events = new SimpleXMLElement($cal_xml_file);


	foreach( $events as $event ) {
		$id = (string) $event->attributes()['ID'];
		$post = get_posts( array(
			'meta_key'   => 'ncal_event_id',
			'meta_value' => $id,
		) );
		$dates = ig_ncal_get_dates ( $event );
		if( count( $post ) > 0 || $id == "") {
			/* 
			 * Event already stored or has no event ID, continue with next event,
			 * We may want to update existing posts in the future.
			 */
			continue;
		}

		/*
		 * For now we only want one EM event per source event
		 */
		$multiple = false;
		if ( $multiple == true ) {
			/*
			* Create a new event for each date, import XML data and save
			*/
			foreach( $dates as $date ) {
				$newEMEvent = new IG_NCAL_Event;
				$newEMEvent->import_xml_data( $date, $event );
				$newEMEvent->save_nue_event( true );
				unset( $newEMEvent );
			}
		} else {
			/*
			* Create one event per source event ID
			*/
			$newEMEvent = new IG_NCAL_Event;
			$newEMEvent->import_xml_data( $date, $event );
			$newEMEvent->save_nue_event( true );
			unset( $newEMEvent );
		}
	}
}

?>
