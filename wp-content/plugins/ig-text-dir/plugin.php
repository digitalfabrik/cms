<?php
/**
 * Plugin Name: Integreat Text Direction
 * Description: Text Directions for Country Codes
 * Version: 1.0
 * Author: Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */


function ig_text_dir( $lang_code ) {
	$rtl_languages = array('ar','fa');
	if(in_array($lang_code, $rtl_languages))
		return "rtl";
	else
		return "ltr";
}

function ig_dir_rtl( $content ) {
	$dom = new DOMDocument();
	$dom->loadHTML( $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	$xpath = new DOMXPath( $dom );
	$tags = $xpath->evaluate( "//p" );
	foreach ( $tags as $tag ) {
		$tag->setAttribute("dir", "rtl");
	}
	$tags = $xpath->evaluate( "//div" );
	foreach ( $tags as $tag ) {
		$tag->setAttribute("dir", "rtl");
	}
	$content = $dom->saveHTML();
	return $content;
}

?>
