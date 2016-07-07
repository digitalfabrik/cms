<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
Plugin Name: Customize Editor Integreat
Description: Helps to simplify the TinyMCE editor by adding, moving and removing Buttons
Version: 1.0
Author: Blanz
*/

/* List of default buttons
	$buttons = array(
			// Core
			'bold' => 'Bold',
			'italic' => 'Italic',
			'underline' => 'Underline',
			'strikethrough' => 'Strikethrough',
			'alignleft' => 'Align left',
			'aligncenter' => 'Align center',
			'alignright' => 'Align right',
			'alignjustify' => 'Justify',
			'styleselect' => 'Formats',
			'formatselect' => 'Paragraph',
			'fontselect' => 'Font Family',
			'fontsizeselect' => 'Font Sizes',
			'cut' => 'Cut',
			'copy' => 'Copy',
			'paste' => 'Paste',
			'bullist' => 'Bulleted list',
			'numlist' => 'Numbered list',
			'outdent' => 'Decrease indent',
			'indent' => 'Increase indent',
			'blockquote' => 'Blockquote',
			'undo' => 'Undo',
			'redo' => 'Redo',
			'removeformat' => 'Clear formatting',
			'subscript' => 'Subscript',
			'superscript' => 'Superscript',

			// From plugins
			'hr' => 'Horizontal line',
			'link' => 'Insert/edit link',
			'unlink' => 'Remove link',
			'image' => 'Insert/edit image',
			'charmap' => 'Special character',
			'pastetext' => 'Paste as text',
			'print' => 'Print',
			'anchor' => 'Anchor',
			'searchreplace' => 'Find and replace',
			'visualblocks' => 'Show blocks',
			'visualchars' => 'Show invisible characters',
			'code' => 'Source code',
			'wp_code' => 'Code',
			'fullscreen' => 'Fullscreen',
			'insertdatetime' => 'Insert date/time',
			'media' => 'Insert/edit video',
			'nonbreaking' => 'Nonbreaking space',
			'table' => 'Table',
			'ltr' => 'Left to right',
			'rtl' => 'Right to left',
			'emoticons' => 'Emoticons',
			'forecolor' => 'Text color',
			'backcolor' => 'Background color',
		);
 */

/**
 * adds buttons to the options table
 * check out tadv_admin.php: e.g.: update_option( 'tadv_settings', $settings );
 * @param $buttons
 * @return mixed
 */
function modify_buttons($buttons)
{
	/*
	 * set to true in order to get the default settings, than do
	 * refresh 2 times
	 */
	$restore_defaults = false;
	$settings = get_option('tadv_settings', false);
	clear_toolbars($settings);
	set_op($settings);
	if ($settings && !$restore_defaults) {
		//first toolbar
		insert_button($settings, 'bold', 'toolbar_1', 1);
		insert_button($settings, 'italic', 'toolbar_1', 2);
		insert_button($settings, 'underline', 'toolbar_1', 3);
		insert_button($settings, 'forecolor', 'toolbar_1', 4);
		insert_button($settings, 'bullist', 'toolbar_1', 5);
		insert_button($settings, 'numlist', 'toolbar_1', 6);
		insert_button($settings, 'table', 'toolbar_1', 7);
		insert_button($settings, 'formatselect', 'toolbar_1', 8);
		insert_button($settings, 'undo', 'toolbar_1', 9);
		insert_button($settings, 'redo', 'toolbar_1', 10);
		insert_button($settings, 'wp_adv', 'toolbar_1', 11);

		//second toolbar
		insert_button($settings, 'alignleft', 'toolbar_2', 1);
		insert_button($settings, 'aligncenter', 'toolbar_2', 2);
		insert_button($settings, 'alignright', 'toolbar_2', 3);
		insert_button($settings, 'alignjustify', 'toolbar_2', 4);
		insert_button($settings, 'strikethrough', 'toolbar_2', 5);
		insert_button($settings, 'indent', 'toolbar_2', 6);
		insert_button($settings, 'outdent', 'toolbar_2', 7);
		insert_button($settings, 'link', 'toolbar_2', 8);

		update_option('tadv_settings', $settings);
	} elseif ($settings && $restore_defaults) {
		//first toolbar
		insert_button($settings, 'bold', 'toolbar_1', 1);
		insert_button($settings, 'italic', 'toolbar_1', 2);
		insert_button($settings, 'underline', 'toolbar_1', 3);
		insert_button($settings, 'bullist', 'toolbar_1', 4);
		insert_button($settings, 'numlist', 'toolbar_1', 5);
		insert_button($settings, 'alignleft', 'toolbar_1', 6);
		insert_button($settings, 'aligncenter', 'toolbar_1', 7);
		insert_button($settings, 'alignright', 'toolbar_1', 8);
		insert_button($settings, 'table', 'toolbar_1', 9);
		insert_button($settings, 'link', 'toolbar_1', 10);
		insert_button($settings, 'unlink', 'toolbar_1', 11);
		insert_button($settings, 'blockquote', 'toolbar_1', 12);
		insert_button($settings, 'undo', 'toolbar_1', 13);
		insert_button($settings, 'redo', 'toolbar_1', 14);
		insert_button($settings, 'fullscreen', 'toolbar_1', 15);
		insert_button($settings, 'wp_adv', 'toolbar_1', 16);
		//second toolbar
		//first toolbar
		insert_button($settings, 'formatselect', 'toolbar_2', 1);
		insert_button($settings, 'alignjustify', 'toolbar_2', 2);
		insert_button($settings, 'strikethrough', 'toolbar_2', 3);
		insert_button($settings, 'outdent', 'toolbar_2', 4);
		insert_button($settings, 'indent', 'toolbar_2', 5);
		insert_button($settings, 'pastetext', 'toolbar_2', 6);
		insert_button($settings, 'removeformat', 'toolbar_2', 7);
		insert_button($settings, 'charmap', 'toolbar_2', 8);
		insert_button($settings, 'emoticons', 'toolbar_2', 9);
		insert_button($settings, 'forecolor', 'toolbar_2', 10);
		insert_button($settings, 'wp_help', 'toolbar_2', 11);

		update_option('tadv_settings', $settings);
	}
	return $buttons;
}

// sample entry
// a:6:{s:9:"toolbar_1";s:84:"bold,italic,...,,redo";s:9:"toolbar_2";...s:9:"toolbar_4";s:0:"";s:7:"options";s:25:"advlist,contextmenu,image";s:7:"plugins";s:25:"table,advlist,contextmenu";}
function clear_toolbars(&$array){
	$toolbar = 'toolbar_';
	for($i=1;$i<=4;$i++){
		$help = $toolbar.$i;
		$array[$help]='';
	}
}

/**
 * options to, e.g.: add the menu bar
 * @param $array
 */
function set_op(&$array){
	$array['options'] = 'menubar,advlist,contextmenu,image';
	$array['plugins'] = 'table,advlist,contextmenu';
}

function insert_button(&$array,$to_insert,$menue,$position){
	$toolbar_string = $array[$menue];
	$toolbar_array = explode(',',$toolbar_string);
	//remove if element is existing --> get desired order
	$existing_position = false;
	if(($existing_position = array_search(strtolower($to_insert),array_map('strtolower',$toolbar_array)))!==false) {
		unset($toolbar_array[$existing_position]);
	}
	if($position>count($toolbar_array)){
		$position=count($toolbar_array);
	}
	array_splice($toolbar_array, $position, 0, $to_insert);
	$toolbar_string = implode(',',$toolbar_array);
	$array[$menue]=$toolbar_string;
}

function remove_button(&$array,$to_remove,$menue){
	$toolbar_1_string = $array[$menue];
	$toolbar_1_array = explode(',',$toolbar_1_string);
	//remove element
	$existing_position = false;
	if(($existing_position = array_search(strtolower($to_remove),array_map('strtolower',$toolbar_1_array)))!==false) {
		unset($toolbar_1_array[$existing_position]);
	}
	$toolbar_1_string = implode(',',$toolbar_1_array);
	$array[$menue]=$toolbar_1_string;
}

add_filter( 'tadv_allowed_buttons', 'modify_buttons' );