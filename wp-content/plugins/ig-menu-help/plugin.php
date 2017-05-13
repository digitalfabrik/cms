<?php
/**
 * Plugin Name: Menu Link to hilfe.integreat-app.de
 * Description: Template-base to include any foreign content into Integreat
 * Version: 1.0
 * Author: Julian Orth
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

add_action('admin_menu', 'example_admin_menu');
 
/**
* add external link to Tools area
*/
function example_admin_menu() {
    global $submenu;
    $url = 'https://hilfe.integreat-app.de/qa/';
    $submenu['tools.php'][] = array('Integreat Hilfe', 'edit_pages', $url);
}
