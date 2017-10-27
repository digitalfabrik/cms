<?php
/**
 * Plugin Name: Import Nuernberger Kalender
 * Description: Importiert Kalender der der Region Nuernberg-Fuerth-Erlangen
 * Version: 1.0
 * Author: Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */


function ig_ncal_menu() {
	add_submenu_page('edit.php?post_type=event', 'N&uuml;rnberg Import', 'N&uuml;rnberg Import', 'edit_events', 'ig_ncal_import','ig_ncal_import');
}
add_action( 'admin_menu', 'ig_ncal_menu' );


/*
 * The ig_ncal_import() function is usually called by a WP cron job.
 * It fetches the data from the Nuremberg region event calendar and stores all events as Event Manager event posts.
 * The source ID is saved as a meta value. If the source ID is already stored, the event will not be processed again.
 */
function ig_ncal_import() {
	/*
	 * Include PHP file containing class for handling EM events and parsing XML.
	 */
	require_once("em-event-wrapper.php");

	/*
	 * Get data from API and parse XML
	 */
    $cal_xml_file = file_get_contents('https://www.meine-veranstaltungen.net/export.php5');
    if ( ! $cal_xml_file ) {
        echo "<div class='notice notice-error'>Can not connect to source. Please try again later or contact the admin.</div>";
        return false;
    }
	$events = new SimpleXMLElement($cal_xml_file);


	foreach( $events as $event ) {
        $id = (string) $event->attributes()['ID'];
        if( "" == $id ) {
            continue;
        }
		$args = array(
			'meta_key' => 'ncal_event_id',
			'meta_value' => $id,
			'post_type' => 'event',
			'post_status' => 'any',
			'posts_per_page' => -1
        );
		$posts = get_posts($args);
		if( count( $posts ) > 0 || $id == "") {
			/* 
			 * Event already stored or has no event ID, continue with next event,
			 * We may want to update existing posts in the future.
			 */
            echo "<div class='notice notice-warning'>Event existiert bereits: <i>".$posts[0]->post_title."</i></div>";
            continue;
		}

        $dates = ig_ncal_get_dates ( $event );

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
            $date = $dates[0];
			$newEMEvent = new IG_NCAL_Event;
			$newEMEvent->import_xml_data( $date, $event );
			$newEMEvent->save_nue_event();
			unset( $newEMEvent );
		}
	}
}

?>
