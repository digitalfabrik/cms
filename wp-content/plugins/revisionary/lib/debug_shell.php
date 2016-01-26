<?php // avoid bombing out if the actual debug file is not loaded
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

if ( ! function_exists('d_echo') ) {
function d_echo($str) {
	return;
}
}

if ( ! function_exists('rvy_errlog') ) {
	function rvy_errlog($message, $line_break = true) {
		return;
	}
}

if ( ! function_exists('agp_bt_die') ) {
function agp_bt_die() {
	return;
}
}

if ( ! function_exists('rvy_memory_new_usage') ) {
function rvy_memory_new_usage () {
	return;
}
}

if ( ! function_exists('rvy_log_mem_usage') ) {
function rvy_log_mem_usage( $label, $display_total = true ) {
	return;
}
}

if ( ! function_exists('dump') ) {
function dump(&$var, $info = FALSE, $display_objects = true) { 
	return; 
}
}
?>