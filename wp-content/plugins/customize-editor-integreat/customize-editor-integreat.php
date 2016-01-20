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


/*used to add buttons to the tinymce (NOT NEEDED if tinymce-advanced is activated)*/
/*//add buttons
function add_button( $buttons ){
	$to_insert = array('underline');
	$position = 0;
	foreach($to_insert as &$value){
		//check if the button is already in the list
		if(!array_search(strtolower($value),array_map('strtolower',$buttons))) {
			array_splice($buttons, 0 , 0, $value);
		}
	}
	return $buttons;
}*/

/**
 * adds buttons to the options table
 * check out tadv_admin.php: e.g.: update_option( 'tadv_settings', $settings );
 * @param $buttons
 * @return mixed
 */
function modify_buttons($buttons){
	$settings = get_option( 'tadv_settings', false );
	if($settings){
		//add buttons
		//first toolbar
		insert_button($settings,'underline','toolbar_1',2);
		insert_button($settings,'alignjustify','toolbar_1',6);
		//second toolbar
		insert_button($settings,'blockquote','toolbar_2',1);
		insert_button($settings,'aligncenter','toolbar_2',2);
		insert_button($settings,'alignright','toolbar_2',3);
		insert_button($settings,'fullscreen','toolbar_2',999);

		//remove buttons
		//first toolbar
		remove_button($settings,'blockquote','toolbar_1');
		remove_button($settings,'unlink','toolbar_1');
		remove_button($settings,'aligncenter','toolbar_1');
		remove_button($settings,'alignright','toolbar_1');
		remove_button($settings,'fullscreen','toolbar_1');
		//second toolbar
		remove_button($settings,'alignjustify','toolbar_2');
		//upload to DB
		update_option( 'tadv_settings', $settings );
	}
	return $buttons;
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

//hook into the first raw of buttons, NOT NEEDED if tinymce-advanced is activated
//add_filter( 'mce_buttons','add_button' );