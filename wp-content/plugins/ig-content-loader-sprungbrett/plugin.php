<?php

/**
 * Plugin Name: Content Loader Sprungbrett
 * Description: Template for plugin to include external data into integreat
 * Version: 0.1
 * Author: Julian Orth, Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

function cl_sb_update_content($parent_id, $meta_value) {
	// get stuff from sprungbrett api
    
    if($meta_value == "Sprungbrett Praktika") {
        
	$json = file_get_contents('http://localhost/json.txt');
    $json = json_decode($json);
    $html = cl_sb_json_to_html($json);
//    var_dump($html);
    do_action('cl_save_html_as_attachement', $parent_id, $html);
        
    exit();
//    cl_save_content( $parent_id, $html );
        
    }

}
add_action('cl_update_content','cl_sb_update_content', 10, 2);

// get json data and transform them to html list
function cl_sb_json_to_html($json) {
    // aus json html liste erstellen
    $htmlstring = '';
    $arr = json_decode('[{"var1":"9","var2":"16","var3":"16"},{"var1":"8","var2":"15","var3":"15"}]');
    foreach($json as $jobitem) { //foreach element in $arr
        $htmlstring .= $jobitem;
        $htmlstringarr = objectToArray($htmlstring);
 
    }

    return $htmlstring;
}

// registriert plugin in base and return meta infos
function cl_sb_metabox_item($array) {
    $array[] = json_decode('{"id": "ig-content-loader-sprungbrett", "name": "Sprungbrett Praktika"}');
    return $array;
}
add_filter('cl_metabox_item', 'cl_sb_metabox_item');


// HIER
// http://www.if-not-true-then-false.com/2009/php-tip-convert-stdclass-object-to-multidimensional-array-and-convert-multidimensional-array-to-stdclass-object/
function objectToArray($d) {
        if (is_object($d)) {
            // Gets the properties of the given object
            // with get_object_vars function
            $d = get_object_vars($d);
        }
 
        if (is_array($d)) {
            /*
            * Return array converted to object
            * Using __FUNCTION__ (Magic constant)
            * for recursive call
            */
            return array_map(__FUNCTION__, $d);
        }
        else {
            // Return array
            return $d;
        }
}

?>