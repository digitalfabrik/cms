<?php

/**
 * Plugin Name: Content Loader Sprungbrett
 * Description: Template for plugin to include external data into integreat
 * Version: 0.1
 * Author: Julian Orth, Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

function cl_sb_update_content( $parent_id, $meta_value) {
	// get stuff from sprungbrett api
    if($meta_value == "ig-content-loader-sprungbrett") {
	$json = file_get_contents('http://localhost/json.txt');
    $json = json_decode($json);
    $html = cl_sb_jsontohtml($json);
    cl_save_content( $parent_id, $html );
        
    }

}
add_action('cl_update_content','cl_sb_update_content');


function cl_sb_jsontohtml($json) {
    // aus json html liste erstellen
    $html ="<p>testcontent</p>";
    return $html;
}

// registriert plugin in base and return meta infos
function cl_sb_metabox_item($array) {
    $array[] = json_decode('{"id": "ig-content-loader-sprungbrett", "name": "Sprungbrett Praktika"}');
    return $array;
}
add_filter('cl_metabox_item', 'cl_sb_metabox_item');