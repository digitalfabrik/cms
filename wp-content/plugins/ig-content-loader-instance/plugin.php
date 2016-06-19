<?php
/**
 * Plugin Name: Content Loader Instance
 * Description: Load content from another instance
 * Version: 0.1
 * Author: Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */


/**
 * Get Sprungbrett JSON-DATA, transform it to html code (cl_sb_json_to_html()) and send it to base-plugin (cl_save_content) with Parameters $parent_id , $html and $blog_id
 *
 */
function cl_in_update_content($parent_id, $meta_value, $blog_id) {

    // sprungbrett praktika -> ig-content-loader-sprungbrett
    if($meta_value == "Sprungbrett Praktika") {
        
        $json = file_get_contents('http://localhost/json.txt');
        $json = json_decode($json, TRUE);
        $html = cl_sb_json_to_html($json);

        cl_save_content( $parent_id, $html, $blog_id);

        return;  
        
    }
}
add_action('cl_update_content','cl_sb_update_content', 10, 3);

// registriert plugin in base and return meta infos
function cl_in_metabox_item($array) {
    $array[] = json_decode('{"id": "ig-content-loader-instance", "name": "Seite aus Fremdinstanz", "ajax_callback": "cl_in_metabox_ajax"}');
    return $array;
}
add_filter('cl_metabox_item', 'cl_in_metabox_item');

function cl_in_metabox_ajax() {
	$selected_instance = $_POST['cl-metabox-instance-id'];
	$selected_post = $_POST['cl-metabox-instance-post-id'];

	/*
	 * Neither instance nor post has been selected. Return instance dropdown.
	 */
	if ( !$selected_instance && !$selected_post ) {
		echo cl_in_generate_instance_dropdown();
	}
	/*
	 * An instance has been selected but no page. Therefore return a dropdown with all pages in instance 
	 */
	elseif ( $selected_instance && !$selected_post ) {
		echo cl_in_generate_post_dropdown( $blog_id );
	}
	/*
	 * Instance and post selected. Needs no ajax but need to save and save_post
	 */ 
	elseif ( $selected_instance && $selected_post ) {
		
	}
	
}

function cl_in_generate_instance_dropdown ( ) {
	
}

function cl_in_generate_post_dropdown( $blog_id ) {
	
}

?>
