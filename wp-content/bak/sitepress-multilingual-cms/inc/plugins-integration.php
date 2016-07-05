<?php

add_action('plugins_loaded', 'wpml_plugins_integration_setup', 10);

//Todo: do not include files: move to autoloaded classes
function wpml_plugins_integration_setup(){
    global $sitepress, $wpml_url_converter, $authordata;
    // WPSEO integration
    if ( defined( 'WPSEO_VERSION' ) && version_compare( WPSEO_VERSION, '1.0.3', '>=' ) ){
        new WPML_WPSEO_XML_Sitemaps_Filter( $sitepress, $wpml_url_converter );
        $wpseo_filters = new WPML_WPSEO_Filters( $sitepress, $authordata );
        $wpseo_filters->init_hooks();
    }
    // NextGen Gallery
    if ( defined( 'NEXTGEN_GALLERY_PLUGIN_VERSION' ) ){
        require_once ICL_PLUGIN_PATH . '/inc/plugin-integration-nextgen.php';
    }
}