<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
Plugin Name: pdf_creator_lite_font_extension
Version: 1.2
Author: Blanz
Depends: pdf-creator-lite, PDF Creator LITE
*/

$DELIMITER_WINDOWS = '\\';
$DELIMITER = '/';
$FONTS = 'fonts';
$EXT_TTF='.ttf';
$EXT_Z='.z';
$EXT_PHP='.php';
$EXT_CTG_Z='.ctg.z';
$EXT_ERROR='ERROR';
$FONT_DIRECTORY='pdf-creator-lite/tcpdf/fonts/';


/****************************************/
/*        Import new fonts              */
/****************************************/                                        

function set_fonts($pdf){
	$font = $pdf->text_font;
	$path = __FILE__;	// --> ...wordpress\wp-content\plugins\pdf-creator-lite-font-extension\pdfcl_fox.php
	$path = return_path_wo_file($path); // --> ...wordpress/wp-content/plugins/pdf-creator-lite-font-extension
	if($path === false){
		//bad path
		error_log(print_r('BAD PATH!', true));
		return false;
	}
	install_fonts($path,$pdf);
	$pdf->SetFont( $font, '', 11, '', true, true );
	//TODO make it dependent from chosen font
	$pdf->SetRTL(false);
	if(ICL_LANGUAGE_CODE!==null){
		if(ICL_LANGUAGE_CODE=='ar' || ICL_LANGUAGE_CODE=='fa'){
			$pdf->SetRTL(true);
		}
	}
	return $pdf;
}

/**
* 1) Remove the file from the path to get the directory
* e.G.: 
* input:	c:\abc\def\ghi.jkl
* output:	c:\abc\def
*
* 2) Normalize path (for linux)
* e.G.:
* input:	c:\abc\def
* output:	c:/abc/def
*/
function return_path_wo_file($path){
	$path = str_replace($GLOBALS["DELIMITER_WINDOWS"],$GLOBALS["DELIMITER"],$path);
	$last = strlen($path)-1;
	$first = 0;
	$path=strrev($path);
	$x = strpos($path,$GLOBALS["DELIMITER"]);
	if($x===false){
		//invalid path
		error_log(print_r('INVALID PATH!', true));
		return false;
	}
	$x = $last-$x;
	$path = strrev($path);
	$path = substr($path,$first,$x);
	return $path;
}

/**
* Add fonts to tcpdf
*/
function install_fonts($path,$pdf){
	$worked;
	$path = $path.$GLOBALS["DELIMITER"].$GLOBALS["FONTS"].$GLOBALS["DELIMITER"];
	foreach (glob($path."*".$GLOBALS["EXT_TTF"]) as $file) {
		if(has_font($file)){
			if(file_exists($file)){
				$worked = $pdf->addTTFfont($file);
				//something went wrong with the font --> add ERROR file extension
				if($worked === false){
					$filename = basename($file);
					$new_filename = $filename.$GLOBALS["EXT_ERROR"];
					$filename = $path.$filename;
					$new_filename = $path.$new_filename;
					rename($filename,$new_filename);
				}
			}						
		}
		//else font already added
	}
}

/**
* check if tcpdf has the font installed already
* e.G.: 
* input: /wordpress/wp-content/plugins/pdf-creator-lite-font-extension/fonts/dejavusans.ttf
* returns true if font is not exisiting
*/
function has_font($file){
	$path = return_path_wo_file($file); // --> .../wordpress/wp-content/plugins/pdf-creator-lite-font-extension/fonts
	if($path === false){
		error_log(print_r('INVALID PATH!', true));
		return false;
	}
	$filename = basename($file);	// --> e.g.: dejavusans.ttf
	$filename = str_replace($GLOBALS["EXT_TTF"],'',$filename); // --> e.g.: defavusans
	$filename = strtolower($filename);
	/**
	* 
	* navigate from 
	*    ".../wordpress/wp-content/plugins/pdf-creator-lite-font-extension/fonts/"
	* to ".../wordpress/wp-content/plugins/pdf-creator-lite/tcpdf/fonts/"
	*/
	$path = return_path_wo_file($path); // --> .../wordpress/wp-content/plugins/pdf-creator-lite-font-extension
	if($path === false){
		error_log(print_r('INVALID PATH!', true));
		return false;
	}
	$path = return_path_wo_file($path);  // --> .../wordpress/wp-content/plugins
	if($path === false){
		error_log(print_r('INVALID PATH!', true));
		return false;
	}
	$path .= $GLOBALS["DELIMITER"].$GLOBALS["FONT_DIRECTORY"]; // --> .../wordpress/wp-content/plugins/pdf-creator-lite/tcpdf/fonts/
	//$path = strtolower($path);
	//TODO TCPDF might modify the filename. E.g.: by removing '-'
	if(file_exists($path.$filename.$GLOBALS["EXT_CTG_Z"]) &&
		file_exists($path.$filename.$GLOBALS["EXT_Z"]) &&
		file_exists($path.$filename.$GLOBALS["EXT_PHP"])){
			return false;
	}
	return true;
}

/****************************************/
/*     Add select for each new font     */
/****************************************/ 

/**
* For each font in the plugins fonts-folder, add select boxes to the adminpage.php
*/
function set_select(){
	$first = '';
	$selection = '';
	$path = __FILE__;
	$path = return_path_wo_file($path);
	$path = $path.$GLOBALS["DELIMITER"].$GLOBALS["FONTS"].$GLOBALS["DELIMITER"]; //--> ".../wordpress/wp-content/plugins/pdf-creator-lite-font-extension/fonts/"
	foreach (glob($path."*".$GLOBALS["EXT_TTF"]) as $file) {
		$filename = basename($file,$GLOBALS["EXT_TTF"]);
		$filename = strtolower($filename);
		if(ICL_LANGUAGE_CODE!==null){
			//for persian we use dejavusans
			if(strcasecmp($filename, 'dejavusans') == 0){
				if(strcasecmp(ICL_LANGUAGE_CODE, 'fa') == 0){
					$first = '<option value="'.$filename.'" selected>'.$filename.'</option>';
				}
				else{
					$selection.= '<option value="'.$filename.'">'.$filename.'</option>';
				}
			}
			//for arabic we use aefurat
			else if(strcasecmp($filename, 'aefurat') == 0){
				if(strcasecmp(ICL_LANGUAGE_CODE, 'ar') == 0){
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
?>