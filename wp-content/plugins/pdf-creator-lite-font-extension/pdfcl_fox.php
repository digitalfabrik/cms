<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
Plugin Name: pdf_creator_lite_font_extension
Version: 1.2
Author: Blanz
Depends: pdf-creator-lite, PDF Creator LITE
*/
require_once('fox_constants.php');

/**
 *
 * @param $pdf
 * @return bool
 */
function set_fonts($pdf){
	$font = $pdf->text_font;
	$path = __FILE__;				// --> ...wordpress\wp-content\plugins\pdf-creator-lite-font-extension\pdfcl_fox.php
	$path = dirname($path); 		// --> ...wordpress/wp-content/plugins/pdf-creator-lite-font-extension
	install_fonts($path,$pdf);
	$pdf->SetFont( $font, '', 11, '', true, true );
	$pdf->SetRTL(false);
	if(ICL_LANGUAGE_CODE!==null){
		if(ICL_LANGUAGE_CODE==fox_constants::FOX_LANGUAGE_ARABIC || ICL_LANGUAGE_CODE==fox_constants::FOX_LANGUAGE_PERSIAN){
			$pdf->SetRTL(true);
		}
	}
	return $pdf;
}

/**
 * Add fonts to tcpdf
 * @param $path: path where the font has to be installed
 * @param $pdf: object the font is added to
 */
function install_fonts($path,$pdf){
	$worked = false;
	$path = $path.fox_constants::FOX_DELIMITER.fox_constants::FOX_FONTS.fox_constants::FOX_DELIMITER;
	foreach (glob($path."*".fox_constants::FOX_EXT_TTF) as $file) {
		if(has_font($file)){
			if(file_exists($file)){
				$worked = $pdf->addTTFfont($file);
				//something went wrong with the font --> add ERROR file extension
				if($worked === false){
					$filename = basename($file);
					$new_filename = $filename.fox_constants::FOX_EXT_ERROR;
					$filename = $path.$filename;
					$new_filename = $path.$new_filename;
					rename($filename,$new_filename);
				}
			}						
		}
		//else font already added --> do nothing
	}
}

/**
 * check if tcpdf has the font installed already
 * e.G.:
 * input: /wordpress/wp-content/plugins/pdf-creator-lite-font-extension/fonts/dejavusans.ttf
 * returns true if font is not exisiting
 *
 * @param $file: the font which has to be checked regarding existance
 * @return bool
 */
function has_font($file){
	$path = dirname($file); 		// --> .../wordpress/wp-content/plugins/pdf-creator-lite-font-extension/fonts
	$filename = basename($file);	// --> e.g.: dejavusans.ttf
	$filename = str_replace(fox_constants::FOX_EXT_TTF,'',$filename); // --> e.g.: defavusans
	$filename = strtolower($filename);
	$path = dirname($path); 		// --> .../wordpress/wp-content/plugins/pdf-creator-lite-font-extension
	$path = dirname($path);  		// --> .../wordpress/wp-content/plugins
	$path .= fox_constants::FOX_DELIMITER.fox_constants::FOX_FONT_DIRECTORY; // --> .../wordpress/wp-content/plugins/pdf-creator-lite/tcpdf/fonts/

	//TODO TCPDF might modify the filename. E.g.: by removing '-'
	if(file_exists($path.$filename.fox_constants::FOX_EXT_CTG_Z) &&
		file_exists($path.$filename.fox_constants::FOX_EXT_Z) &&
		file_exists($path.$filename.fox_constants::FOX_EXT_PHP)){
			return false;
	}
	return true;
}

/**
 * For each font in the plugins fonts-folder, add select boxes to the adminpage.php
 */
function set_select(){
	$first = '';
	$selection = '';
	$path = __FILE__;
	$path = dirname($path);
	$path = $path.fox_constants::FOX_DELIMITER.fox_constants::FOX_FONTS.fox_constants::FOX_DELIMITER; //--> ".../wordpress/wp-content/plugins/pdf-creator-lite-font-extension/fonts/"
	foreach (glob($path."*".fox_constants::FOX_EXT_TTF) as $file) {
		$filename = basename($file,fox_constants::FOX_EXT_TTF);		//e.G.: basename("/etc/sudoers.d", ".d"). --> sudoers
		$filename = strtolower($filename);
		if($filename == fox_constants::FOX_DEJAVUSANS){				//for persian we use dejavusans
			if(ICL_LANGUAGE_CODE == fox_constants::FOX_LANGUAGE_PERSIAN){
				$first = '<option value="'.$filename.'" selected>'.$filename.'</option>';
			}
			else{
				$selection.= '<option value="'.$filename.'">'.$filename.'</option>';
			}
		}
		else if($filename == fox_constants::FOX_AEFURAT){				//for arabic we use aefurat
			if(ICL_LANGUAGE_CODE == fox_constants::FOX_LANGUAGE_ARABIC){
				$first = '<option value="'.$filename.'" selected>'.$filename.'</option>';
			}
			else{
				$selection.= '<option value="'.$filename.'">'.$filename.'</option>';
			}
		}
		else{
			$selection.= '<option value="'.$filename.'">'.$filename.'</option>';
		}
	}	
	echo($first.$selection);
}

/****************************************/
/*         Filters and Actions          */
/****************************************/ 

add_filter('fox_modify_pdf','set_fonts');

add_action('fox_add_fonts','set_select');