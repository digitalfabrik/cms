<?php
/*
Plugin Name: WPML Translation Management
Plugin URI: https://wpml.org/
Description: Add a complete translation process for WPML | <a href="https://wpml.org">Documentation</a> | <a href="https://wpml.org/version/translation-management-2-5-2/">WPML Translation Management 2.5.2 release notes</a>
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
Version: 2.5.2
Plugin Slug: wpml-translation-management
*/

if ( defined( 'WPML_TM_VERSION' ) || get_option( '_wpml_inactive' ) ) {
	return;
}

/** @var array $bundle */
$bundle = json_decode( file_get_contents( dirname( __FILE__ ) . '/wpml-dependencies.json' ), true );
if ( defined( 'ICL_SITEPRESS_VERSION' ) && is_array( $bundle ) ) {
	$sp_version_stripped = ICL_SITEPRESS_VERSION;
	$dev_or_beta_pos = strpos( ICL_SITEPRESS_VERSION, '-' );
	if ( $dev_or_beta_pos > 0 ) {
		$sp_version_stripped = substr( ICL_SITEPRESS_VERSION, 0, $dev_or_beta_pos );
	}
	if ( version_compare( $sp_version_stripped, $bundle[ 'sitepress-multilingual-cms' ], '<' ) ) {
		return;
	}
}

define( 'WPML_TM_VERSION', '2.5.2' );

// Do not uncomment the following line!
// If you need to use this constant, use it in the wp-config.php file
//define( 'WPML_TM_DEV_VERSION', '2.0.3-dev' );

define( 'WPML_TM_PATH', dirname( __FILE__ ) );

if ( ! defined( 'WPML_TM_WC_CHUNK' ) ) {
	define( 'WPML_TM_WC_CHUNK', 1000 );
}

$autoloader_dir = WPML_TM_PATH . '/vendor';
if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	$autoloader = $autoloader_dir . '/autoload.php';
} else {
	$autoloader = $autoloader_dir . '/autoload_52.php';
}
require_once $autoloader;

require_once WPML_TM_PATH . '/inc/constants.php';
require_once WPML_TM_PATH . '/inc/translation-proxy/wpml-pro-translation.class.php';
require_once WPML_TM_PATH . '/inc/functions-load.php';
require_once WPML_TM_PATH . '/inc/js-scripts.php';

new WPML_TM_Requirements();

function wpml_tm_load_ui( $sitepress = null ) {
	if ( ! $sitepress ) {
		global $sitepress;
	}
	if ( ! $sitepress || ! $sitepress instanceof SitePress || ! $sitepress->is_setup_complete() ) {
		return;
	}

	require_once WPML_TM_PATH . '/menu/basket-tab/sitepress-table-basket.class.php';
	require_once WPML_TM_PATH . '/menu/dashboard/wpml-tm-dashboard.class.php';
	require_once WPML_TM_PATH . '/menu/wpml-tm-menus.class.php';
	require_once WPML_TM_PATH . '/menu/wpml-translator-settings.class.php';

	if ( version_compare( ICL_SITEPRESS_VERSION, '3.3.1', '>=' ) ) {
		global $wpdb, $WPML_Translation_Management, $ICL_Pro_Translation;

		$core_translation_management = wpml_load_core_tm();
		$tm_loader                   = new WPML_TM_Loader();
		$wpml_tp_translator          = new WPML_TP_Translator();
		$WPML_Translation_Management = new WPML_Translation_Management( $sitepress, $tm_loader, $core_translation_management, $wpml_tp_translator );
		$WPML_Translation_Management->load();

		if ( ! $ICL_Pro_Translation ) {
			$job_factory         = wpml_tm_load_job_factory();
			$ICL_Pro_Translation = new WPML_Pro_Translation( $job_factory );
		}

		if ( is_admin() ) {
			$wpml_wp_api      = new WPML_WP_API();
			$TranslationProxy = new WPML_Translation_Proxy_API();
			new WPML_TM_Troubleshooting_Reset_Pro_Trans_Config( $sitepress, $TranslationProxy, $wpml_wp_api, $wpdb );
			new WPML_TM_Troubleshooting_Clear_TS( $wpml_wp_api );
			new WPML_TM_Promotions( $wpml_wp_api );

			if ( defined( 'DOING_AJAX' ) ) {
				$wpml_tm_options_ajax = new WPML_TM_Options_Ajax( $sitepress );
				$wpml_tm_options_ajax->ajax_hooks();

				$wpml_tm_pickup_mode_ajax = new WPML_TM_Pickup_Mode_Ajax( $sitepress, $ICL_Pro_Translation );
				$wpml_tm_pickup_mode_ajax->ajax_hooks();
			}
		}

		$action_filter_loader = new WPML_Action_Filter_Loader();

		if ( class_exists( 'WPML_TF_Settings_Read' ) ) {
			$tf_settings_read = new WPML_TF_Settings_Read();
			/** @var WPML_TF_Settings $tf_settings */
			$tf_settings = $tf_settings_read->get( 'WPML_TF_Settings' );
			$translation_feedback_module = new WPML_TM_TF_Module( $action_filter_loader, $tf_settings );
			$translation_feedback_module->run();
		}

		$action_filter_loader->load( array(
			                             'WPML_TM_Jobs_Deadline_Estimate_AJAX_Action_Factory',
			                             'WPML_TM_Jobs_Deadline_Cron_Hooks_Factory',
			                             'WPML_TM_Emails_Settings_Factory',
			                             'WPML_TM_Jobs_Summary_Report_Hooks_Factory',
		));
	}

	$actions = array(
		'WPML_TM_Translation_Services_Admin_Section_Resources_Factory',
		'WPML_TM_Translation_Services_Admin_Section_Ajax_Factory',
		'WPML_TM_Translation_Service_Authentication_Ajax_Factory',
		'WPML_TM_Translation_Services_Refresh_Services_Factory',
		'WPML_TM_Default_Settings_Factory',
		'WPML_TP_Lock_Notice_Factory',
		'WPML_TM_API_Hooks_Factory',
	);

	$action_filter_loader = new WPML_Action_Filter_Loader();
	$action_filter_loader->load( $actions );
}

add_action( 'wpml_loaded', 'wpml_tm_load_ui', 10, 1 );

function wpml_tm_word_count_init() {
	global $sitepress, $wpdb;

	$wpml_wp_api = $sitepress->get_wp_api();
	$wpml_tm_words_count = new WPML_TM_Words_Count( $wpdb, $sitepress );
	$wpml_tm_words_count->init();

	if ( $wpml_wp_api->is_dashboard_tab() ) {
		new WPML_TM_Words_Count_Resources( $wpml_wp_api );
		new WPML_TM_Words_Count_Box_UI( $wpml_wp_api );
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$wpml_tm_words_count_summary = new WPML_TM_Words_Count_Summary_UI( $wpml_tm_words_count, $wpml_wp_api );
		new WPML_TM_Words_Count_AJAX( $wpml_tm_words_count, $wpml_tm_words_count_summary, $wpml_wp_api );
	}
}

if ( is_admin() && isset( $GLOBALS['sitepress'] ) ) {
	add_action( 'wpml_tm_loaded', 'wpml_tm_word_count_init' );
}

/**
 * @param array $blocks
 *
 * @return array
 */
function wpml_tm_support_info( array $blocks ) {
	$support_info = new WPML_TM_Support_Info();

	$ui = new WPML_TM_Support_Info_Filter( $support_info );

	return $ui->filter_blocks( $blocks );
}

/** This filter is documented WPML Core in classes/support/class-wpml-support-info-ui.php */
add_filter( 'wpml_support_info_blocks', 'wpml_tm_support_info' );

function wpml_tm_icl20_migration() {
	//@todo Remove `|| ( defined( 'WPML_TP_ICL_20_MIGRATION_OFF' ) && WPML_TP_ICL_20_MIGRATION_OFF )` after testing?
	if ( defined( 'WPML_TP_ICL_20_MIGRATION_OFF' )
	     && WPML_TP_ICL_20_MIGRATION_OFF ) {
		return;
	}

	global $sitepress;
	$loader = new WPML_TM_ICL20_Migration_Loader($sitepress->get_wp_api(), new WPML_TM_ICL20_Migration_Factory());
	$loader->run();
}

if ( ! empty( $GLOBALS['sitepress'] ) && is_admin() ) {
	add_action( 'wpml_tm_loaded', 'wpml_tm_icl20_migration' );
}



