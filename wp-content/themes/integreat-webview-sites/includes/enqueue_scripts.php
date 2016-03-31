<?php

function scripts_enqueue() {

    // jQuery
    wp_deregister_script('jquery');
    wp_register_script('jquery', "//ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js", false, null);
    wp_enqueue_script('jquery');

    // custom scripts
    wp_enqueue_script( 'custom', get_stylesheet_directory_uri() . '/js/custom.js', array( 'jquery' ) );

}
if (!is_admin()) add_action("wp_enqueue_scripts", "scripts_enqueue", 11);

?>