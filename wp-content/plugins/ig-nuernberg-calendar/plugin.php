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
    //var_dump($events);
    foreach( $events as $event ) {
        echo( $event['ID'] );
    }
}

?>
