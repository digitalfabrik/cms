<?php

function scripts_enqueue() {

    // jQuery
    wp_deregister_script('jquery');
    // TODO jquery via google cdn: wp_register_script('jquery', "//ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js", false, null);
    wp_register_script('jquery', get_stylesheet_directory_uri() . '/js/jquery.min.js', false, null);
    wp_enqueue_script('jquery');

    // custom scripts
    wp_enqueue_script( 'custom', get_stylesheet_directory_uri() . '/js/custom.js', array( 'jquery' ) );

    // live search and highlight plugin
    wp_enqueue_script( 'hideseek', get_stylesheet_directory_uri() . '/js/jquery.hideseek.js', array( 'jquery' ) );

    // search
    wp_enqueue_script( 'search', get_stylesheet_directory_uri() . '/js/search.js', array( 'jquery', 'hideseek' ) );

    // navigation
    wp_enqueue_script( 'navigation', get_stylesheet_directory_uri() . '/js/navigation.js', array( 'jquery', 'scrollbars' ) );

    // responsive tables
    wp_enqueue_script( 'responsive-tables', get_stylesheet_directory_uri() . '/js/responsive-tables.js', array( 'jquery' ) );

    // custom scrollbars
    wp_enqueue_script( 'scrollbars', get_stylesheet_directory_uri() . '/js/jquery.custom-scrollbar.js', array( 'jquery' ) );

}
if (!is_admin()) add_action("wp_enqueue_scripts", "scripts_enqueue", 11);

?>