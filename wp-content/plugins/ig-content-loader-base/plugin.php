<?php
/**
 * Plugin Name: Content Loader Base
 * Description: Template for plugin to include external data into integreat
 * Version: 0.1
 * Author: Julian Orth, Sven Seeberg
 * Author URI: https://github.com/Integreat
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

/**
 *  Meta Box registrieren und an Hook binden
 */
function cl_generate_selection_box() {
    add_meta_box( 'meta-box-id', __( 'Fremdinhalte einfügen', 'textdomain' ), 'cl_my_display_callback', 'page', 'side' );
}
add_action( 'add_meta_boxes_page', 'cl_generate_selection_box' );
 
/**
 * Meta box display callback.
 *
 * @param WP_Post $post Current post object.
 */
function cl_my_display_callback( $post ) {
    // Display code/markup goes here. Don't forget to include nonces!
    wp_nonce_field( basename( __FILE__ ), 'prfx_nonce' );
    $prfx_stored_meta = get_post_meta( $post->ID );
    ?>

    <!-- Fremdinhalt Dropdown-select -->
    <p>
        <label style="font-weight:600" for="meta-select" class="prfx-row-title">
            <?php _e( 'Inhalt wählen', 'prfx-textdomain' )?>
        </label>
        <select name="meta-select" id="meta-select" style="width:100%; margin-top:10px; margin-bottom:10px">
            <option value="select-one" <?php if ( isset ( $prfx_stored_meta[ 'meta-select'] ) ) selected( $prfx_stored_meta[ 'meta-select'][0], 'select-one' ); ?>>
                <?php _e( 'Sprungbrett into work', 'prfx-textdomain' )?>
            </option>';
            <option value="select-two">
                <?php _e( 'Sprungbrett bayern', 'prfx-textdomain' )?>
            </option>';

        </select>
    </p>


    <!-- Inhalt position Radios -->
    <p>
        <span style="font-weight:600" class="prfx-row-title"><?php _e( 'Inhalt Einfügen', 'prfx-textdomain' )?></span>
        <div class="prfx-row-content">
            <label for="meta-radio-one" style="display: block;box-sizing: border-box; margin-bottom: 8px;">
                <input type="radio" name="meta-radio" id="meta-radio-one" value="radio-one">
                <?php _e( 'Am Anfang', 'prfx-textdomain' )?>
            </label>
            <label for="meta-radio-two">
                <input checked type="radio" name="meta-radio" id="meta-radio-two" value="radio-two">
                <?php _e( 'Am Ende', 'prfx-textdomain' )?>
            </label>
        </div>
    </p>


    <!-- Inhalt laden und einfügen -->
    <div>
        <hr style="margin-bottom:10px;">
        <input style="width:100%;" name="loadandinsert" type="submit" class="button button-primary button-large" id="s" value="Einfügen">
    </div>

    <?php
}
 
/**
 * Save meta box content.
 *
 * @param int $post_id Post ID
 */
function cl_save_meta_box( $post_id ) {
    // Save logic goes here. Don't forget to include nonce checks!
	//wenn element aus cl_generate_selection_box ausgewählt wurde, irgendwie in postmeta speichern
	$cl_content = $_GET['cl_content'];
	
	//save in postmeta
}
add_action( 'save_page', 'cl_save_meta_box' );

function cl_save_content() {
	do_action('cl_save_content');
	
	save_post($posttype='attachment',$title,$content,$parent_id);
	// eigene datenstruktur oder wp_posts und eigenen datentypen definieren bzw attachment(!!!) benutzen?
}

function cl_modify_post() {
	//lädt aus datenbank den zwischengespeicherten fremdcontent
	echo "";
}
add_action('rest_api_print_post', 'modify_post', 1);



// do_action in der rest api: bei ausgabe von posts muss bei entsprechendem meta tag ein do_action('rest_api_print_post') aufgerufen werden
function cl_update () {
    global $wp_query;
    // wird regelmäßig durch cronjob gestartet
    $cl_action = $wp_query->query_vars['content-loader'];
    
    if( $cl_action == "update" ) {
        
        do_action('cl_update_content');
        
        exit();
    }
}
add_action( 'template_redirect', 'cl_update' );


// do_action in der rest api: bei ausgabe von posts muss bei entsprechendem meta tag ein do_action('rest_api_print_post') aufgerufen werden

function cl_rewrite() {

    add_rewrite_tag( '%content-loader%', '([^&]+)' );
    //add_rewrite_rule( 'content-loader/([^/]*)/?', 'index.php?content-loader=$matches[1]', 'top');

}
add_action( 'init', 'cl_rewrite' );


?>