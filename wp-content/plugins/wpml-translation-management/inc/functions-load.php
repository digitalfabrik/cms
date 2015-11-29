<?php

/**
 * @return WPML_TM_Element_Translations
 */
function wpml_tm_load_element_translations(){
	global $wpml_tm_element_translations;

	if(!isset($wpml_tm_element_translations)){
		require WPML_TM_PATH . '/inc/core/wpml-tm-element-translations.class.php';
		$wpml_tm_element_translations = new WPML_TM_Element_Translations();
	}

	return $wpml_tm_element_translations;
}

function wpml_tm_load_status_display_filter() {
	global $wpml_tm_status_display_filter, $iclTranslationManagement, $sitepress, $wpdb;

	new WPML_TM_API( $wpdb, $iclTranslationManagement );

	if ( !isset( $wpml_tm_status_display_filter ) ) {
		$user_id                       = get_current_user_id ();
		$lang_pairs                    = get_user_meta ( $user_id, $wpdb->prefix . 'language_pairs', true );
		$wpml_tm_status_display_filter = new WPML_TM_Translation_Status_Display(
			$user_id,
			current_user_can ( 'manage_options' ),
			$lang_pairs,
			$sitepress->get_current_language (),
			$sitepress->get_active_languages ()
		);
	}

	$wpml_tm_status_display_filter->init ( false );
}

/**
 * @return WPML_Translation_Proxy_Basket_Networking
 */
function wpml_tm_load_basket_networking() {
	global $iclTranslationManagement, $wpdb;

	require_once WPML_TM_PATH . '/inc/translation-proxy/wpml-translationproxy-basket-networking.class.php';

	$basket = new WPML_Translation_Basket( $wpdb );

	return new WPML_Translation_Proxy_Basket_Networking( $basket, $iclTranslationManagement );
}

/**
 * @return WPML_TM_TP_Network_Mock|WPML_Translation_Proxy_Networking
 */
function wpml_tm_load_tp_networking() {
	global $wpml_tm_tp_networking;

	if ( ! isset( $wpml_tm_tp_networking ) ) {
		require WPML_TM_PATH . '/inc/translation-proxy/wpml-translation-proxy-networking.class.php';
		$wpml_tm_tp_networking = new WPML_Translation_Proxy_Networking();
	}

	return $wpml_tm_tp_networking;
}

/**
 * @return WPML_TM_Mail_Notification
 */
function wpml_tm_init_mail_notifications() {
	global $wpml_tm_mailer, $sitepress, $wpdb, $iclTranslationManagement, $wpml_translation_job_factory;

	if ( ! isset( $wpml_tm_mailer ) ) {
		require WPML_TM_PATH . '/inc/local-translation/wpml-tm-mail-notification.class.php';
		$blog_translators         = new WPML_TM_Blog_Translators( $wpdb );
		$iclTranslationManagement = $iclTranslationManagement ? $iclTranslationManagement : wpml_load_core_tm();
		if ( empty( $iclTranslationManagement->settings ) ) {
			$iclTranslationManagement->init();
		}
		$settings                 = isset( $iclTranslationManagement->settings['notification'] )
			? $iclTranslationManagement->settings['notification'] : array();
		$wpml_tm_mailer           = new WPML_TM_Mail_Notification( $sitepress,
		                                                           $wpdb,
		                                                           $wpml_translation_job_factory,
		                                                           $blog_translators,
		                                                           $settings );
	}
	$wpml_tm_mailer->init();

	return $wpml_tm_mailer;
}

/**
 * @return WPML_Dashboard_Ajax
 */
function wpml_tm_load_tm_dashboard_ajax(){
	global $wpml_tm_dashboard_ajax;

	if(!isset($wpml_tm_dashboard_ajax)){
	require WPML_TM_PATH . '/menu/dashboard/wpml-tm-dashboard-ajax.class.php';
		$wpml_tm_dashboard_ajax = new WPML_Dashboard_Ajax();
	}

	return $wpml_tm_dashboard_ajax;
}

if ( defined( 'DOING_AJAX' ) ) {
    $wpml_tm_dashboard_ajax = wpml_tm_load_tm_dashboard_ajax();
    add_action( 'init', array( $wpml_tm_dashboard_ajax, 'init_ajax_actions' ) );
} elseif ( is_admin() && isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == WPML_TM_FOLDER . '/menu/main.php'
    && ( !isset( $_GET[ 'sm' ] ) || $_GET['sm'] === 'dashboard' ) )
{
    $wpml_tm_dashboard_ajax = wpml_tm_load_tm_dashboard_ajax();
    add_action( 'wpml_tm_scripts_enqueued', array( $wpml_tm_dashboard_ajax, 'enqueue_js' ) );
}

function tm_after_load() {
	require WPML_TM_PATH . '/inc/actions/wpml-tm-action-helper.class.php';
	require WPML_TM_PATH . '/inc/translation-jobs/collections/wpml-abstract-job-collection.class.php';
	require WPML_TM_PATH . '/inc/translation-proxy/wpml-translation-basket.class.php';
	require WPML_TM_PATH . '/inc/translation-jobs/wpml-translation-batch.class.php';
	require WPML_TM_PATH . '/inc/translation-jobs/wpml-translation-job-factory.class.php';
	require WPML_TM_PATH . '/inc/translation-proxy/translationproxy.class.php';
	require WPML_TM_PATH . '/inc/ajax.php';

	global $wpml_translation_job_factory, $wpdb, $wpml_tm_translation_status;
	$wpml_translation_job_factory = new WPML_Translation_Job_Factory( $wpdb );
	wpml_tm_init_mail_notifications();
	wpml_tm_load_element_translations();
	$wpml_tm_translation_status = new WPML_TM_Translation_Status();
	$wpml_tm_translation_status->init();
	add_action( 'wpml_pre_status_icon_display', 'wpml_tm_load_status_display_filter' );
	require WPML_TM_PATH . '/inc/wpml-private-actions.php';
}

function wpml_tm_load_dashboard_widget() {
	if ( is_admin() ) {
		global $pagenow;
		if ( $pagenow === 'index.php' ) {
			global $sitepress, $wpdb;
			$widget = new WPML_TM_CPT_Dashboard_Widget( $wpdb, $sitepress );
			echo $widget->render();
		}
	}
}

add_action( 'icl_dashboard_widget_notices', 'wpml_tm_load_dashboard_widget' );