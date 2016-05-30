<?php
/**
 * Plugin Name: Content Loader Template
 * Description: Template for plugin to include external data into integreat
 * Version: 0.1
 * Author: Sven Seeberg
 * Author URI: https://github.com/sven15
 * License: MIT
 */

global $cl_template_db_version;
$cl_template_db_version = '1.0';

function cl_template_install() {
	global $wpdb;
	global $cl_template_db_version;

	$table_name = $wpdb->prefix . '_template_content';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		name tinytext NOT NULL,
		text text NOT NULL,
		url varchar(55) DEFAULT '' NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'cl_template_db_version', $cl_template_db_version );
}

function jal_install_data() {
	global $wpdb;
	
	$welcome_name = 'Mr. WordPress';
	$welcome_text = 'Congratulations, you just completed the installation!';
	
	$table_name = $wpdb->prefix . 'liveshoutbox';
	
	$wpdb->insert( 
		$table_name, 
		array( 
			'time' => current_time( 'mysql' ), 
			'name' => $welcome_name, 
			'text' => $welcome_text, 
		) 
	);
}

// generiert box im editor fenster, in der alle content loader ausgewählt werden können
function cl_generate_selection_box () {
	//apply_filters ( 'cl_content_list', array() );
	$list = array('ig-cl-sprungbrett'=>'Sprungbrett','plugin-name'=>'description');
	//list zu html drop down verwurtschteln
	
	
	
	echo "<select name='cl_content'></select>";
	
	
}
add_action('wenn eine seite bearbeitet wird','cl_generate_selection_box')


function cl_save_page () {
	//wenn element aus cl_generate_selection_box ausgewählt wurde, irgendwie in postmeta speichern
	$cl_content = $_GET['cl_content'];
	
	//save in postmeta
}
add_action('save_page','cl_save_page');

function cl_save_content() {
	do_action('cl_save_content');
	
	save_post($posttype='attachment',$title,$content,$parent_id)
	// eigene datenstruktur oder wp_posts und eigenen datentypen definieren bzw attachment(!!!) benutzen?
}

function cl_modify_post() {
	//lädt aus datenbank den zwischengespeicherten fremdcontent
	echo "";
}
add_action('rest_api_print_post', 'modify_post', 1);

function cl_update () {
	// wird regelmäßig durch cronjob gestartet
	do_action('cl_get_updated_content');
}

// do_action in der rest api: bei ausgabe von posts muss bei entsprechendem meta tag ein do_action('rest_api_print_post') aufgerufen werden

?>
