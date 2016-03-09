<?php

// enqueue scripts
include __DIR__ . '/includes/enqueue_scripts.php';

// cookies
include __DIR__ . '/includes/cookies.php';

// make theme available for translation
load_theme_textdomain( 'integreat-sites', get_template_directory() . '/languages' );

// do not show wordpress version
function wp_remove_version() {
    return '';
}
add_filter('the_generator', 'wp_remove_version');

?>