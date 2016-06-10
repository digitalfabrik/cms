<?php

/**
 * Plugin Name: Content Loader Sprungbrett
 * Description: Template for plugin to include external data into integreat
 * Version: 0.1
 * Author: Julian Orth, Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

function cl_sb_update_content() {
	// get stuff from sprungbrett api
	$json = file_get_contents('http://localhost/json.txt');
    $json = json_decode($json);
    
    var_dump($json);

}
add_action('cl_update_content','cl_sb_update_content');


