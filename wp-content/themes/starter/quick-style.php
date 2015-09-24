<?php
header('Content-type: text/css');

$parse_uri = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
$wp_load = $parse_uri[0].'wp-load.php';
require_once($wp_load);

global $themeum;
$output = '';

if(isset($themeum['body_bg'])):
	$output .= 'body{ background: '.$themeum['body_bg'] .'; }';
endif;

if(isset($themeum['header_bg'])):
	$output .= '.navbar.navbar-default{ background: '.$themeum['header_bg'] .'; }';
endif;

if(isset($themeum['footer_bg'])):
	$output .= '#footer{ background: '.$themeum['footer_bg'] .'; }';
endif;

if(isset($themeum['g_select'])):
	$output .= 'body{';
	$output .= 'font-family: "'.$themeum['g_select'].'";';
	$output .= 'font-weight: '.$themeum['body_font_style']['style'].';';
	$output .= 'font-size: '.$themeum['body_font_style']['size'].';';
	$output .= 'color: '.$themeum['body_font_style']['color'].';';
	$output .= '}';
endif;

if(isset($themeum['head_font'])):
	$output .= 'h1,';
	$output .= 'h2,';
	$output .= 'h3,';
	$output .= 'h4,';
	$output .= 'h5,';
	$output .= 'h6{';
	$output .= 'font-family: "'.$themeum['head_font'].'";';
	$output .= 'font-weight: '.$themeum['heading_font_style']['style'].';';
	$output .= 'color: '.$themeum['heading_font_style']['color'].';';
	$output .= '}';
endif;

if(isset($themeum['nav_font'])):
	$output .= '#navigation .navbar-nav > li > a{';
	$output .= 'font-family: "'.$themeum['nav_font'].'";';
	$output .= '}';

endif;

echo $output;