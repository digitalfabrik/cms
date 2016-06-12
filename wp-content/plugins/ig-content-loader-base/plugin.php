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

/* debugging function for testing purposes 
* src: https://www.itsupportguides.com/wordpress/wordpress-how-to-debug-php-to-console/ 
*/
function debug_to_console( $data ) {
if ( is_array( $data ) )
 $output = "<script>console.log( 'Debug Objects: " . implode( ',', $data) . "' );</script>";
 else
 $output = "<script>console.log( 'Debug Objects: " . $data . "' );</script>";
echo $output;
}

/*
* generate select items from plugin list which contain the name prefix 'cl-content-loader'
* src: http://wordpress.stackexchange.com/questions/52144/what-wordpress-api-function-lists-active-inactive-plugins
*/
function cl_generate_select_list() {
    // get all plugins and store them in array
    $cl_plugin_stack = array();
    $cl_plugin_names = array();
    $cl_plugins = get_plugins();
    foreach ( $cl_plugins as $cl_plugin ) {
        array_push($cl_plugin_stack, $cl_plugin);
    }
    
    //loop through plugin array and get all plugin names
    for ($x = 0; $x < count($cl_plugin_stack); $x++) {  
      array_push($cl_plugin_names, $cl_plugin_stack[$x][Name]);    
    }
    //debug_to_console($cl_plugin_names);
    
    $cl_matches = preg_grep('/Content Loader/', $cl_plugin_names); 
    $cl_keys    = array_keys($cl_matches); 
    
    debug_to_console($cl_matches);
}


/**
 *  Meta Box registrieren und an Hook binden
 */
function cl_generate_selection_box() {
    add_meta_box( 'meta-box-id', __( 'Fremdinhalte einfügen', 'textdomain' ), 'cl_my_display_callback', 'page', 'side' );
    
    
}
add_action( 'add_meta_boxes_page', 'cl_generate_selection_box' );
//add_action( 'add_meta_boxes_page', 'cl_generate_select_list' );
 
/**
 * Meta box display callback.
 *
 * @param WP_Post $post Current post object.
 */
function cl_my_display_callback( $post ) {
    // Display code/markup goes here. Don't forget to include nonces!
    wp_nonce_field( basename( __FILE__ ), 'prfx_nonce' );
    $prfx_stored_meta = get_post_meta( $post->ID );
    

    $dropdown_items = apply_filters('cl_metabox_item', $array);
  

?>


    <!-- foreign content: Dropdown-select -->
    <p>
        <label style="font-weight:600" for="meta-select" class="prfx-row-title">
            <?php _e( 'Inhalt wählen', 'prfx-textdomain' )?>
        </label>
        <select name="cl_content_select2" id="meta-select" style="width:100%; margin-top:10px; margin-bottom:10px">
            <!-- build select items from filtered plugin list -->
            
            <?php 
                foreach($dropdown_items as $cl_plugin_name_option) {
//                    print('<option name="cl_content_select" value="'.$cl_plugin_name_option[id].'">'.$cl_plugin_name_option[id].'</option>'."\n");  
                    print('<option name="cl_content_select">'.$cl_plugin_name_option->name.'</option>');
                }
            ?>
            
        </select>
    </p>


    <!-- content position: Radios -->
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


    <!-- load and insert content: Button -->
    <div>
        <hr style="margin-bottom:10px;">
        <input style="width:100%;" name="loadandinsert" type="submit" class="button button-primary button-large" id="s" value="Einfügen">
    </div>

   
    <?php
       
   
}
 
/* Save post meta on the 'save_post' hook. */
  //add_action( 'pre_post_update', 'smashing_save_post_class_meta', 10, 2 );

/* Save the meta box's post metadata. */
function smashing_save_post_class_meta( $post_id, $post ) {

  /* Verify the nonce before proceeding. */
//  if ( !isset( $_POST['smashing_post_class_nonce'] ) || !wp_verify_nonce( $_POST['smashing_post_class_nonce'], basename( __FILE__ ) ) )
//    return $post_id;
    debug_to_console("ok");

  /* Get the post type object. */
  $post_type = get_post_type_object( $post->post_type );

  /* Check if the current user has permission to edit the post. */
  if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
    return $post_id;

  /* Get the posted data and sanitize it for use as an HTML class. */
  $new_meta_value = ( isset( $_POST['cl_content_select'] ) ? sanitize_html_class( $_POST['cl_content_select'] ) : '' );

  /* Get the meta key. */
  $meta_key = 'smashing_post_class';

  /* Get the meta value of the custom field key. */
  $meta_value = get_post_meta( $post_id, $meta_key, true );

  /* If a new meta value was added and there was no previous value, add it. */
  if ( $new_meta_value && '' == $meta_value )
    add_post_meta( $post_id, $meta_key, $new_meta_value, true );
    
  /* If the new meta value does not match the old value, update it. */
  elseif ( $new_meta_value && $new_meta_value != $meta_value )
    update_post_meta( $post_id, $meta_key, $new_meta_value );

  /* If there is no new meta value but an old value exists, delete it. */
  elseif ( '' == $new_meta_value && $meta_value )
    delete_post_meta( $post_id, $meta_key, $meta_value );
}



//add_action( 'save_post', 'cl_save_meta_box' );

/**
 * Save meta box content.
 *
 * @param int $post_id Post ID
 */
add_action('save_post', 'cl_save_meta_box');
add_action('edit_post', 'cl_save_meta_box');
add_action('publish_post', 'cl_save_meta_box');
add_action('edit_page_form', 'cl_save_meta_box');
//save in postmeta
function cl_save_meta_box($post_id) {
    // Save logic goes here. Don't forget to include nonce checks!
	//wenn element aus cl_generate_selection_box ausgewählt wurde, irgendwie in postmeta speichern
	//$cl_content = $_GET['cl_content_select'];
    /* Get the meta key. */
    $meta_key = 'ig-content-loader-base';
    $meta_value = $_REQUEST['cl_content_select2'];
//    var_dump("leck michaksldjfasjkldfjklasdfjkasdjkfjaklsdfkljasdjfklalsjkdfjlkasdfkljasdlkjf");
    var_dump($meta_value);
    
//    var_dump($_POST);
    if ($meta_value) {
        
    /* Get the meta value of the custom field key. */
        $meta_value = get_post_meta( $post_id, $meta_key, true );

    /* If a new meta value was added and there was no previous value, add it. */
    if ( $new_meta_value && '' == $meta_value )
        add_post_meta( $post_id, $meta_key, $new_meta_value, true );

    /* If the new meta value does not match the old value, update it. */
    elseif ( $new_meta_value && $new_meta_value != $meta_value )
        update_post_meta( $post_id, $meta_key, $new_meta_value );

    /* If there is no new meta value but an old value exists, delete it. */
    elseif ( '' == $new_meta_value && $meta_value )
        delete_post_meta( $post_id, $meta_key, $meta_value );

    }
}
   
	
	


// wird aufgerufen mit id und html code, welcher als attach gespeichert wird
function cl_save_content( $parent_id, $attachement) {
	// noch mal prüfen:
	save_post($posttype='attachment',$title,$attachement,$parent_id);
    
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
    global $wpdb;
    // query alle objekte in db mit meta_key = ig-content-loader-base
    $result = $wpdb->get_results("select post_id from ".$wpdb->prefix."_postmeta where meta_key = 'ig-content-loader-base'");
    
    // wird regelmäßig durch cronjob gestartet
    // parse var content-loader aus url
    $cl_action = $wp_query->query_vars['content-loader'];
    
    if( $cl_action == "update" ) {
        
        do_action('cl_update_content', $parent_id, $meta_value );
        
        exit();
    }
}
add_action( 'template_redirect', 'cl_update' );


// do_action in der rest api: bei ausgabe von posts muss bei entsprechendem meta tag ein do_action('rest_api_print_post') aufgerufen werden

function cl_rewrite() {
    // es gibt var content-loader
    add_rewrite_tag( '%content-loader%', '([^&]+)' );
    //add_rewrite_rule( 'content-loader/([^/]*)/?', 'index.php?content-loader=$matches[1]', 'top');

}
add_action( 'init', 'cl_rewrite' );


?>