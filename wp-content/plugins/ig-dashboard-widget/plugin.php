<?php
/**
 * Plugin Name: Integreat Dashboard Widget
 * Description: Adds a Welcome-Widget to the dashboard of all non-admin user
 * Version: 1.0
 * Author: Jan-Ulrich Holtgrave
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

add_action('wp_dashboard_setup', 'ig-dashboard-widget');

// Define Functione for Widget-Creation
function ig_dashboard_widget() {
global $wp_meta_boxes;

wp_add_dashboard_widget('ig_welcome_widget', 'Wilkommen im Integreat-CMS', 'ig_welcome');
}

// Create visible Content 
function ig_welcome() {
echo '<p>Wilkommen im Content-Bereich von Integreat. Von hier aus haben Sie die Möglichkeit Ihre Inthalte entsprechend anzupassen.</p>';
}

?>