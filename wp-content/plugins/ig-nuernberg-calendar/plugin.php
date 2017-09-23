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
	
add_action('ig_ncal_import_event', 'ig_ncal_import');
function ig_ncal_import() {
	$cal_xml_file = file_get_contents('https://www.meine-veranstaltungen.net/export.php5');
	$events = new SimpleXMLElement($cal_xml_file);
	foreach( $events as $event ) {
		$post = get_posts( array(
			'meta_key'   => 'ncal_event_id',
			'meta_value' => $events['ID'],
		) );
		if( count( $post ) == 0 ) {
			/* 
			 * Event already stored, continue with next event
			 */
			continue;
		}
		$newEMEvent = new IG_NUE_Event;
		$newEMEvent->import_xml_data( $event );
		$newEMevent->save_nue_event();
		unset( $newEMEvent );
	}
}

?>
