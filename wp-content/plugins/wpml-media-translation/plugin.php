<?php
/**
 * Plugin Name: WPML Media
 * Plugin URI: https://wpml.org/
 * Description: Add multilingual support for Media files | <a href="https://wpml.org">Documentation</a> | <a href="https://wpml.org/version/media-translation-2-2-3/">WPML Media Translation 2.2.3 release notes</a>
 * Author: OnTheGoSystems
 * Author URI: http://www.onthegosystems.com/
 * Version: 2.2.3
 * Plugin Slug: wpml-media-translation
 */

if ( defined( 'WPML_MEDIA_VERSION' ) ) {
	return;
}

define( 'WPML_MEDIA_VERSION', '2.2.3' );
define( 'WPML_MEDIA_PATH', dirname( __FILE__ ) );

$autoloader_dir = WPML_MEDIA_PATH . '/vendor';
if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	$autoloader = $autoloader_dir . '/autoload.php';
} else {
	$autoloader = $autoloader_dir . '/autoload_52.php';
}
require_once $autoloader;

require WPML_MEDIA_PATH . '/inc/constants.inc';
require WPML_MEDIA_PATH . '/inc/private-filters.php';
require WPML_MEDIA_PATH . '/inc/wpml-media-dependencies.class.php';
require WPML_MEDIA_PATH . '/inc/wpml-media-upgrade.class.php';
if( is_admin() ){
	require_once(ABSPATH . 'wp-admin/includes/image.php');
}

global $WPML_media, $wpdb, $sitepress, $iclTranslationManagement;
$WPML_media = new WPML_Media( false, $sitepress, $wpdb );

add_action( 'wpml_loaded', 'wpml_media_load_components' );
function wpml_media_load_components() {
	global $wpdb, $sitepress, $iclTranslationManagement, $WPML_media;

	$attachments_query = new WPML_Media_Attachments_Query();
	$attachments_query->add_hooks();

	$attachments_texts_sync = new WPML_Media_Texts_Sync( $sitepress, $wpdb, WPML_Media::get_setting( 'new_content_settings' ) );
	$attachments_texts_sync->add_hooks();

	$attachment_image_updater = new WPML_Media_Attachment_Image_Update( $wpdb );
	$attachment_image_updater->add_hooks();

	$image_translator = new WPML_Media_Image_Translate( $sitepress, $wpdb );
	$image_updater    = new WPML_Media_Translated_Images_Update( new WPML_Media_Img_Parse(), $image_translator );

	$media_localization_settings = WPML_Media::get_setting( 'media_files_localization' );

	if ( $media_localization_settings['posts'] ) {
		$post_images_translation = new WPML_Media_Post_Images_Translation( $image_updater, $sitepress, $wpdb );
		$post_images_translation->add_hooks();
	}

	if ( $media_localization_settings['custom_fields'] ) {
		$custom_field_images_translation = new WPML_Media_Custom_Field_Images_Translation(
			$image_updater, $sitepress, $iclTranslationManagement );
		$custom_field_images_translation->add_hooks();
	}

	if ( class_exists( 'WPML_Current_Screen_Loader_Factory' ) ) {
		$loaders              = array( 'WPML_Media_Edit_Hooks_Factory' );
		$action_filter_loader = new WPML_Action_Filter_Loader();
		$action_filter_loader->load( $loaders );
	}

}

add_action( 'wpml_st_loaded', 'wpml_media_load_components_st' );
function wpml_media_load_components_st() {
	global $sitepress, $wpdb;

	$media_localization_settings = WPML_Media::get_setting( 'media_files_localization' );

	$image_translator = new WPML_Media_Image_Translate( $sitepress, $wpdb );
	$image_updater    = new WPML_Media_Translated_Images_Update( new WPML_Media_Img_Parse(), $image_translator );

	if ( $media_localization_settings['strings'] ) {
		$string_factory            = new WPML_ST_String_Factory( $wpdb );
		$string_images_translation = new WPML_Media_String_Images_Translation( $image_updater, $string_factory );
		$string_images_translation->add_hooks();
	}
}



