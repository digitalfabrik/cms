<?php
/*
Plugin Name: PDF Creator LITE
Plugin URI: http://www.cite.soton.ac.uk
Description: Let visitors and admins create PDFs of your site content at the click of a button.
Version: 1.2
Author: Alex Furr, Simon Ward
Author URI: http://www.cite.soton.ac.uk
License: GPL

*/


// Plugin definitions
//-------------------
define( 'SSAPDF_PLUGIN_URL', plugins_url('pdf-creator-lite' , dirname( __FILE__ )) );
define( 'EXPORT_AS_PDF_PATH', plugin_dir_path(__FILE__) );


// Include files
//--------------
require_once EXPORT_AS_PDF_PATH . 'functions.php';
require_once EXPORT_AS_PDF_PATH . 'scripts/shortcodes.php';

//TCpdf
require_once EXPORT_AS_PDF_PATH . 'tcpdf/config/tcpdf_config.php';
require_once EXPORT_AS_PDF_PATH . 'tcpdf/tcpdf.php';

//custom TCpdf extension
require_once EXPORT_AS_PDF_PATH . 'scripts/extend-class-tcpdf.php';

//via ajax stuff
require_once EXPORT_AS_PDF_PATH . 'scripts/ajax.php';
require_once EXPORT_AS_PDF_PATH . 'scripts/build-pdf-admin.php';
require_once EXPORT_AS_PDF_PATH . 'scripts/build-pdf-frontend.php';

//admin screen
require_once EXPORT_AS_PDF_PATH . 'adminpage.php';



// WP hooks
//---------

//admin
add_action('admin_menu', 'SSAPDF_addAdminPage'); 			// admin menus

//frontend template
add_action('wp_head', 'SSAPDF_frontendHeadActions');		// frontend head
add_action('wp_footer', 'SSAPDF_frontendFooterActions');	// frontend footer

//shortcodes
if ( ! is_admin() )
{
	add_shortcode('pdf-lite', 'drawFrontEndPDF_download');	// frontend shortcode
}

//ajax
add_action( 'wp_ajax_frontEndDownloadPDF', 'frontEndDownloadPDF' );			// fires if logged-in user
add_action( 'wp_ajax_nopriv_frontEndDownloadPDF', 'frontEndDownloadPDF' );	// fires if non-logged-in user
add_action( 'wp_ajax_SSAPDFadminBuildPDF', 'SSAPDFadminBuildPDF' );			//admin-side PDF builder




//Default callbacks
//-------------------
function getCaseIDs_default ( $newIDarray, $currentPostID = "" )
{
	return $newIDarray;
}


//Initialise default callbacks
//------------------------------
function addDefaultFilters()
{
	//cases default
	if ( has_filter('getCaseIDs') === false ) //if not registered
	{
		if ( ! function_exists('getCaseIDs') )
		{
			function getCaseIDs( $newIDarray )
			{
				$data = apply_filters('getCaseIDs', $newIDarray );
				return $data;
			}
		}
		add_filter( 'getCaseIDs', 'getCaseIDs_default', 10, 1 ); //params - hookname, the fired function, priority(not important here), no of args sent to fired function.
	}
	
	//others...
}

add_action('init', 'addDefaultFilters', 100); //lower priority callback than extensions, this ideally runs last for speed.



?>