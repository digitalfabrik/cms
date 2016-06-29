<?php
/*
Plugin Name: WPML Multilingual CMS
Plugin URI: https://wpml.org/
Description: WPML Multilingual CMS | <a href="https://wpml.org">Documentation</a> | <a href="https://wpml.org/version/wpml-3-3/">WPML 3.3 release notes</a>
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
Version: 3.4.1
Plugin Slug: sitepress-multilingual-cms
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

if ( defined( 'ICL_SITEPRESS_VERSION' ) || (bool) get_option( '_wpml_inactive' ) === true ) {
	return;
}
define( 'ICL_SITEPRESS_VERSION', '3.4.1' );

// Do not uncomment the following line!
// If you need to use this constant, use it in the wp-config.php file
//define('ICL_SITEPRESS_DEV_VERSION', '3.4-dev');

define( 'ICL_PLUGIN_PATH', dirname( __FILE__ ) );
define( 'ICL_PLUGIN_FILE', basename( __FILE__ ) );
define( 'ICL_PLUGIN_FULL_PATH', basename( ICL_PLUGIN_PATH ) . '/' . ICL_PLUGIN_FILE );
define( 'ICL_PLUGIN_FOLDER', basename( ICL_PLUGIN_PATH ) );

//PHP 5.2 backward compatibility
if ( ! defined( 'FILTER_SANITIZE_FULL_SPECIAL_CHARS' ) ) {
	define( 'FILTER_SANITIZE_FULL_SPECIAL_CHARS', FILTER_SANITIZE_STRING );
}
require ICL_PLUGIN_PATH . '/inc/functions-helpers.php';

if ( ! class_exists( 'Twig_Autoloader' ) ) {
	require_once ICL_PLUGIN_PATH . '/lib/Twig/Autoloader.php';
	Twig_Autoloader::register();
}

$autoloader_dir = ICL_PLUGIN_PATH . '/embedded';
if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	$autoloader = $autoloader_dir . '/autoload.php';
} else {
	$autoloader = $autoloader_dir . '/autoload_52.php';
}
require_once $autoloader;

require ICL_PLUGIN_PATH . '/embedded/wpml/commons/src/dependencies/class-wpml-dependencies.php';

require ICL_PLUGIN_PATH . '/inc/wpml-private-actions.php';
require ICL_PLUGIN_PATH . '/inc/functions.php';
require ICL_PLUGIN_PATH . '/inc/functions-sanitation.php';
require ICL_PLUGIN_PATH . '/inc/functions-security.php';
require ICL_PLUGIN_PATH . '/inc/wpml-post-comments.class.php';
require ICL_PLUGIN_PATH . '/inc/icl-admin-notifier.php';

if ( !function_exists( 'filter_input' ) ) {
    wpml_set_plugin_as_inactive();
    add_action( 'admin_notices', 'wpml_missing_filter_input_notice' );
    return;
}

$icl_plugin_url = untrailingslashit( plugin_dir_url( __FILE__ ) );
if ( (bool) wpml_get_setting_filter( array(), 'language_domains' ) === true && isset( $_SERVER['HTTP_HOST'] ) ) {
    global $wpdb, $wpml_include_url_filter;

    $wpml_include_url_filter = new WPML_Include_Url( $wpdb, $_SERVER['HTTP_HOST'] );
    $icl_plugin_url          = $wpml_include_url_filter->filter_include_url( $icl_plugin_url );
}
define( 'ICL_PLUGIN_URL', $icl_plugin_url );

if ( ( ! defined( 'WPML_BYPASS_ICL_CHECK' ) || ! WPML_BYPASS_ICL_CHECK ) && wpml_version_is( '3.2', '>=' ) && wpml_site_uses_icl() ) {
	wpml_set_plugin_as_inactive();
	add_action( 'admin_notices', 'wpml_site_uses_icl_message_notice' );
	if ( is_admin() ) {
		activate_installer();
	}
	return;
}

require ICL_PLUGIN_PATH . '/inc/template-functions.php';
require ICL_PLUGIN_PATH . '/inc/js-scripts.php';
require ICL_PLUGIN_PATH . '/inc/lang-data.php';
require ICL_PLUGIN_PATH . '/inc/setup/sitepress-setup.class.php';

require ICL_PLUGIN_PATH . '/inc/not-compatible-plugins.php';
if ( ! empty( $icl_ncp_plugins ) ) {
    return;
}

require ICL_PLUGIN_PATH . '/inc/setup/sitepress-schema.php';

require ICL_PLUGIN_PATH . '/inc/functions-load.php';
require ICL_PLUGIN_PATH . '/inc/constants.php';
require ICL_PLUGIN_PATH . '/inc/taxonomy-term-translation/wpml-term-translations.class.php';
require ICL_PLUGIN_PATH . '/inc/functions-troubleshooting.php';
require ICL_PLUGIN_PATH . '/menu/term-taxonomy-menus/taxonomy-translation-display.class.php';
require ICL_PLUGIN_PATH . '/inc/taxonomy-term-translation/wpml-term-translation.class.php';

require ICL_PLUGIN_PATH . '/inc/post-translation/wpml-post-translation.class.php';
require ICL_PLUGIN_PATH . '/inc/post-translation/wpml-admin-post-actions.class.php';
require ICL_PLUGIN_PATH . '/inc/post-translation/wpml-frontend-post-actions.class.php';

require ICL_PLUGIN_PATH . '/inc/url-handling/wpml-url-filters.class.php';
require ICL_PLUGIN_PATH . '/inc/url-handling/wpml-url-converter.class.php';
require ICL_PLUGIN_PATH . '/inc/utilities/wpml-languages.class.php';
require ICL_PLUGIN_PATH . '/inc/utilities/wpml-wp-api.class.php';
require ICL_PLUGIN_PATH . '/menu/post-menus/post-edit-screen/wpml-meta-boxes-post-edit-html.class.php';

load_essential_globals();

require ICL_PLUGIN_PATH . '/inc/query-filtering/wpml-query-utils.class.php';
require ICL_PLUGIN_PATH . '/sitepress.class.php';
require ICL_PLUGIN_PATH . '/inc/query-filtering/wpml-query-filter.class.php';
require ICL_PLUGIN_PATH . '/inc/hacks.php';
require ICL_PLUGIN_PATH . '/inc/upgrade.php';
require ICL_PLUGIN_PATH . '/inc/language-switcher.php';
require ICL_PLUGIN_PATH . '/inc/import-xml.php';

// using a plugin version that the db can't be upgraded to
if(defined('WPML_UPGRADE_NOT_POSSIBLE') && WPML_UPGRADE_NOT_POSSIBLE) return;

if(is_admin() || defined('XMLRPC_REQUEST')){
    require ICL_PLUGIN_PATH . '/lib/icl_api.php';
    require ICL_PLUGIN_PATH . '/inc/utilities/xml2array.php';
    require ICL_PLUGIN_PATH . '/lib/Snoopy.class.php';
    if ( !defined ( 'DOING_AJAX' ) ) {
        require ICL_PLUGIN_PATH . '/menu/wpml-admin-scripts-setup.class.php';
    }
    require ICL_PLUGIN_PATH . '/inc/pointers.php';
}elseif(preg_match('#wp-comments-post\.php$#', $_SERVER['REQUEST_URI'])){
	require_once ICL_PLUGIN_PATH . '/inc/translation-management/translation-management.class.php';
}

if ( function_exists('is_multisite') && is_multisite() ) {
    $wpmu_sitewide_plugins = (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
    if(false === get_option('icl_sitepress_version', false) && isset($wpmu_sitewide_plugins[ICL_PLUGIN_FOLDER.'/'.basename(__FILE__)])){
        icl_sitepress_activate();
    }

    include_once ICL_PLUGIN_PATH . '/inc/functions-network.php';
    if(get_option('_wpml_inactive', false) && isset($wpmu_sitewide_plugins[ICL_PLUGIN_FOLDER.'/sitepress.php'])){
        wpml_set_plugin_as_inactive();
        return;
    }
}

if ( ! wp_next_scheduled( 'update_wpml_config_index' ) ) {
	//Set cron job to update WPML config index file from CDN
	wp_schedule_event( time(), 'daily', 'update_wpml_config_index' );
}
/** @var WPML_Post_Translation $wpml_post_translations */
global $sitepress, $wpdb, $wpml_url_filters, $wpml_post_translations,
       $wpml_term_translations, $wpml_url_converter, $wpml_language_resolution,
       $wpml_slug_filter;

$sitepress        = new SitePress();

new WPML_Global_AJAX( $sitepress );
$wpml_wp_api = $sitepress->get_wp_api();
if ( $wpml_wp_api->is_support_page() ) {
	new WPML_Support_Page( $wpml_wp_api );
}

wpml_load_query_filter ( icl_get_setting ( 'setup_complete' ) );
$wpml_url_filters = new WPML_URL_Filters( $wpml_post_translations, $wpml_url_converter, $sitepress );
wpml_load_request_handler( is_admin(),
                           $wpml_language_resolution->get_active_language_codes(),
                           $sitepress->get_default_language() );
require ICL_PLUGIN_PATH . '/inc/url-handling/wpml-slug-filter.class.php';
$wpml_slug_filter = new WPML_Slug_Filter( $wpdb, $sitepress, $wpml_post_translations );
/** @var array $sitepress_settings */
$sitepress_settings = $sitepress->get_settings();
wpml_load_term_filters();
wpml_maybe_setup_post_edit();

require ICL_PLUGIN_PATH . '/modules/cache-plugins-integration/cache-plugins-integration.php';
require ICL_PLUGIN_PATH . '/inc/wp-login-filters.php';
require ICL_PLUGIN_PATH . '/inc/plugins-integration.php';

if ( is_admin() ) {
	activate_installer( $sitepress );
	if ( $sitepress->get_setting( 'setup_complete' ) ) {
		setup_admin_menus();
	}
}

if(!empty($sitepress_settings['automatic_redirect'])){
	$wpml_browser_redirect = new WPML_Browser_Redirect( $sitepress );
	$wpml_browser_redirect->init_hooks();
}

// activation hook
register_deactivation_hook( WP_PLUGIN_DIR . '/' . ICL_PLUGIN_FOLDER . '/sitepress.php', 'icl_sitepress_deactivate');

add_filter('plugin_action_links', 'icl_plugin_action_links', 10, 2);

$WPML_Users_Languages_Dependencies = new WPML_Users_Languages_Dependencies( $sitepress );
