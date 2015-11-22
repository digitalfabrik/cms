<?php

add_action('plugins_loaded', 'wpml_plugins_integration_setup', 10);

//Todo: do not include files: move to autoloaded classes
function wpml_plugins_integration_setup(){
    // WPSEO XML Sitemaps integration
    if ( defined( 'WPSEO_VERSION' ) && version_compare( WPSEO_VERSION, '1.0.3', '>=' ) ){
        require_once ICL_PLUGIN_PATH . '/inc/wpseo-sitemaps-filter.php';
    }
    // NextGen Gallery
    if ( defined( 'NEXTGEN_GALLERY_PLUGIN_VERSION' ) ){
        require_once ICL_PLUGIN_PATH . '/inc/plugin-integration-nextgen.php';
    }
}