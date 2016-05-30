<?php

/**
 * Plugin Name: Content Loader Template
 * Description: Template for plugin to include external data into integreat
 * Version: 0.1
 * Author: Julian Orth, Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

function sb_update() {
	// get stuff from sprungbrett api
	
	// build html from json
	
	if(function_exists("cl_save_content")) 
		// save html to wordpress with cl_save_content($html)
	else
		//throw error
}
add_action('cl_get_update_content','sb_update');
