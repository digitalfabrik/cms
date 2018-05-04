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
	define('IG_MPDF_PATH', get_site_url(null, '/wp-content/plugins/ig-mpdf/', 'https'));
}

add_action('init', function() {
	// init API
	new IntegreatMpdfAPI();
	// process request if form was submitted (must run ad 'init' to enable pdf output)
	if (isset($_POST['submit']) && $_SERVER['QUERY_STRING'] == 'page=ig-mpdf') {
		if(isset($_POST['page'])) {
			$mpdf = new IntegreatMpdf($_POST['page'], isset($_POST['toc']));
			try {
				$mpdf->get_pdf();
			} catch (\Mpdf\MpdfException $e) {
				echo '<div class="notice notice-error is-dismissible"><p><strong>' . $e->getMessage() . '</strong></p></div>';
			}
		} else {
			echo '<div class="notice notice-warning is-dismissible"><p><strong>Bitte wählen Sie mindestens eine Seite aus.</strong></p></div>';
		}
	}
});

// add admin menu
add_action(
	'admin_menu',
	function () {
		add_menu_page(
			'PDF Export',
			'PDF Export',
			'read',
			'ig-mpdf',
			[
				'IntegreatMpdfSettings',
				'create_admin_page'
			],
			'dashicons-format-aside'
		);
	}
);