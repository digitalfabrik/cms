<?php
/*
Plugin Name: WPML Media
Plugin URI: https://wpml.org/
Description: Add multilingual support for Media files
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
Version: 2.1.19
Plugin Slug: wpml-media-translation
*/

if (defined('WPML_MEDIA_VERSION')) {
	return;
}

define('WPML_MEDIA_VERSION', '2.1.19');
define('WPML_MEDIA_PATH', dirname(__FILE__));

require_once 'embedded/wpml/commons/autoloader.php';
$wpml_auto_loader_instance = WPML_Auto_Loader::get_instance();
$wpml_auto_loader_instance->register( WPML_MEDIA_PATH . '/' );

require WPML_MEDIA_PATH . '/inc/wpml-dependencies-check/wpml-bundle-check.class.php';
require WPML_MEDIA_PATH . '/inc/constants.inc';
require WPML_MEDIA_PATH . '/inc/private-filters.php';
require WPML_MEDIA_PATH . '/inc/wpml-media-dependencies.class.php';
require WPML_MEDIA_PATH . '/inc/wpml-media-upgrade.class.php';
require WPML_MEDIA_PATH . '/inc/wpml-media.class.php';

global $WPML_media;
$WPML_media = new WPML_Media();
