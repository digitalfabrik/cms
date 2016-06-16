<?php

/**
 * Plugin Name: Content Loader Sprungbrett
 * Description: Template for plugin to include external data into integreat
 * Version: 0.1
 * Author: Julian Orth, Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

function cl_sb_update_content($parent_id, $meta_value, $blog_id) {
	// get stuff from sprungbrett api
    // sprungbrett praktika -> ig-content-loader-sprungbrett
    if($meta_value == "Sprungbrett Praktika") {
        
        $json = file_get_contents('http://localhost/json.txt');
        $json = json_decode($json, TRUE);
        $html = cl_sb_json_to_html($json);

        cl_save_content( $parent_id, $html, $blog_id);
        //do_action('cl_save_html_as_attachement', $parent_id, $html);

        return;
    //    
        
    }

}
add_action('cl_update_content','cl_sb_update_content', 10, 3);


// get json data and transform them to html list
// geht nicht mehr mit 2 json objects
function cl_sb_json_to_html($json) {

    $html_table_prefix = '<table>';
    $html_table_suffix = '</table>';
    
    foreach($json as $jobitem) {
        $htmlstring .= '<tr><td><b>'.$jobitem['title'].'</b></td>'.
                       '<td>'.$jobitem['description'].'</td>'.
                       '<td>'.$jobitem['zip'].'</td></tr>';
    }
    
    $htmlstring = $html_table_prefix.$htmlstring.$html_table_suffix;

    return $htmlstring;
}

// registriert plugin in base and return meta infos
function cl_sb_metabox_item($array) {
    $array[] = json_decode('{"id": "ig-content-loader-sprungbrett", "name": "Sprungbrett Praktika"}');
    return $array;
}
add_filter('cl_metabox_item', 'cl_sb_metabox_item');

// stylesheet
// load css into the website's front-end
function mytheme_enqueue_style() {
    wp_enqueue_style( 'mytheme-style', get_stylesheet_uri() ); 
}
add_action( 'wp_enqueue_scripts', 'mytheme_enqueue_style' );



?>