<?php
if( version_compare( '0.3.3', get_option('em_wpml_version') )){
    global $wpdb;
    $wpdb->query('DROP TABLE '.$wpdb->prefix.'em_wpml_events');
}
update_option('em_wpml_version', EM_WPML_VERSION);