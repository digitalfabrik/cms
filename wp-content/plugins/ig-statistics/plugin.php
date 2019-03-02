<?php
/**
 * Plugin Name: Integreat Statistics
 * Description: Create nice statistics from Matomo with Chart.js
 * Version: 1.0
 * Author: Ulli Holtgrave <holtgrave@integreat-app.de>, Sven Seeberg <seeberg@integreat-app.de>
 * Author URI: https://github.com/Integreat
 * License: MIT
 * Text Domain: ig-attach-content
 */

/**
 * Load plugin text domain for translations in backend
 */
add_action( 'plugins_loaded', function() {
	load_plugin_textdomain('ig-statistics', false, basename( dirname( __FILE__ )));
});

/**
 * Add menu entries to single blog
 */
function ig_statistics_menu() {
    add_submenu_page( 'index.php', 'My Statistics', 'My Statistics', 'edit_pages', 'ig_statistics', 'ig_statistics' );
}
add_action( 'admin_menu', 'ig_statistics_menu' );

/**
 * Show tracking data
 */
function ig_statistics() {
    $matomo_token = get_option('ig-statistics-matomo-token');
    $api_data = file_get_contents("https://statistics.integreat-app.de/index.php?date=2019-02-03,2019-03-04&expanded=1&filter_limit=100&format=JSON&format_metrics=1&idSite=2&method=API.get&module=API&period=day&token_auth=$matomo_token");
    include("statistics.php");
}

/**
 * Function for registering the plugin after activation. This installs
 * or updates the database and also sets the default configuration
 * values.
 */
function ig_statistics_registration() {
    $sites = wp_get_sites();
    $current_blog = get_current_blog_id();
    foreach ( $sites as $site ) {
        switch_to_blog($site['blog_id']);
        $pw_piwik_token = get_option('wp-piwik_global-token')
        if (strlen($wp_piwik_token) > 0) {
            $matomo_token = $wp_piwik_token;
        } else {
            $matomo_token = "";
        }
        update_option('ig-statistics-matomo-token', $matomo_token);
    }
    switch_to_blog($current_blog);
}
register_activation_hook( __FILE__, 'ig_statistics_registration' );