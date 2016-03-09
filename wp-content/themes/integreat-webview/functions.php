<?php

// enqueue scripts
include __DIR__ . '/includes/enqueue_scripts.php';

// custom menu walker
include __DIR__ . '/includes/custom_menu_walker.php';

// breadcrumb
include __DIR__ . '/includes/breadcrumb.php';

// cookies
include __DIR__ . '/includes/cookies.php';

// customizer
include __DIR__ . '/includes/customizer.php';

// make theme available for translation
function theme_textdomain() {
    load_theme_textdomain( 'integreat', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'theme_textdomain' );

// do not show wordpress version
function wp_remove_version() {
    return '';
}
add_filter('the_generator', 'wp_remove_version');

?>