<?php
/**
 * Plugin Name: Integreat mPDF
 * Description: Plugin for pdf exports of single and multiple sites via mpdf library
 * Version: 1.1
 * Author: Integreat Team / Sascha Beele
 * Author URI: https://github.com/Integreat
 * License: MIT
 * Text Domain: ig-mpdf
 * Domain Path: /
 */

include __DIR__ . '/IntegreatMpdf.php';
include __DIR__ . '/IntegreatMpdfAPI.php';
include __DIR__ . '/IntegreatMpdfSettings.php';

// constant for plugin path
if(!defined('IG_MPDF_PATH')) {
	define('IG_MPDF_PATH', get_option('siteurl') . '/wp-content/plugins/ig-mpdf/');
}

// create database table on activation
function ig_mpdf_install() {
	IntegreatMpdfSettings::create_database_table();
}
register_activation_hook( __FILE__, 'ig_mpdf_install' );

// init backend functionality and API
function init_ig_mpdf() {
	if(is_admin()) {
		new IntegreatMpdfSettings();
	}
	new IntegreatMpdfAPI();
}
add_action('init', 'init_ig_mpdf');