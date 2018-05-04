<?php
/*
Plugin Name: Integreat mpdf
Description: Plugin for pdf exports of single and multiple sites via mpdf library
Author:      Sascha Beele
 */

include __DIR__ . '/IntegreatMpdf.php';
include __DIR__ . '/IntegreatMpdfAPI.php';
include __DIR__ . '/IntegreatMpdfSettings.php';

// constant for plugin path
if(!defined('IG_MPDF_PATH')) {
	define('IG_MPDF_PATH', get_site_url(null, '/wp-content/plugins/ig-mpdf/', 'https'));
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