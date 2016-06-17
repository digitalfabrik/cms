<?php
/**
 * Plugin Name: Content Loader Base
 * Description: Template-base to include any foreign content into integreat
 * Version: 0.1
 * Author: Julian Orth, Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

/**
 *  Register Meta box Hook
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

	wp_nonce_field( basename( __FILE__ ), 'prfx_nonce' );
    
	$prfx_stored_meta = get_post_meta( $post->ID );
	
	$dropdown_items = apply_filters('cl_metabox_item', $array);

?>


    <!-- Dropdown-select for foreign contents -->
    <p>
        <label style="font-weight:600" for="meta-select" class="prfx-row-title">
            <?php _e( 'Inhalt wählen', 'prfx-textdomain' )?>
        </label>
        <select name="cl_content_select" id="meta-select" style="width:100%; margin-top:10px; margin-bottom:10px">
            <!-- build select items from filtered plugin list -->
            <option>Plugin picken</option>
            <?php 
				foreach($dropdown_items as $cl_plugin_name_option) {
//					print('<option name="cl_content_select" value="'.$cl_plugin_name_option[id].'">'.$cl_plugin_name_option[id].'</option>'."\n");  
					print('<option name="cl_content_select_item">'.$cl_plugin_name_option->name.'</option>');
				}
			?>

        </select>
    </p>


    <!-- Radio-button: Insert foreign content before or after page -->
    <p>
        <span style="font-weight:600" class="prfx-row-title"><?php _e( 'Inhalt Einfügen', 'prfx-textdomain' )?></span>
        <div class="prfx-row-content">
            <label for="meta-radio-one" style="display: block;box-sizing: border-box; margin-bottom: 8px;">
                <input type="radio" name="meta-radio" id="insert-pre-radio" value="Am Anfang">
                <?php _e( 'Am Anfang', 'prfx-textdomain' )?>
            </label>
            <label for="meta-radio-two">
                <input checked type="radio" name="meta-radio" id="insert-suf-radio" value="Am Ende">
                <?php _e( 'Am Ende', 'prfx-textdomain' )?>
            </label>
        </div>
    </p>


    <?php	   
   
}
 


add_action('save_post', 'cl_save_meta_box');
add_action('edit_post', 'cl_save_meta_box');
add_action('publish_post', 'cl_save_meta_box');
add_action('edit_page_form', 'cl_save_meta_box');

/**
* Save meta box contents in post_meta db-table
*
* @param int $post_id Post ID
*/
function cl_save_meta_box($post_id) {

	// key for base-plugin in wp_postmeta
	$meta_key = 'ig-content-loader-base';
  
	//get the selected value from the meta box dropdown-select
    $meta_value = ( isset( $_POST['cl_content_select'] ) ? $_POST['cl_content_select'] : '' );
    
	//read old post meta setting
	$old_meta_value = get_post_meta( $post_id, $meta_key, true );
  
	
	if ($meta_value != '') {
	  
	//if there was no old post meta entry, add it
	if ( '' == $old_meta_value )
		add_post_meta( $post_id, $meta_key, $meta_value, true );

	//if the old post meta value is different from the posted one,change it
	elseif ( $old_meta_value != $meta_value )
		update_post_meta( $post_id, $meta_key, $meta_value );
	}
  
	//if there is an old meta value but now new meta value, remve meta value from wp_postmeta
	elseif ( '' == $meta_value && $old_meta_value ) {
		delete_post_meta( $post_id, $meta_key, $meta_value );
	}
}

/**
* Safe foreign content in db as html code
* 
*
*/
function cl_save_content( $parent_id, $attachment, $blog_id) {
	global $wpdb;


	$sql = "SELECT * FROM ".$wpdb->prefix.$blog_id."_posts WHERE post_parent =".$parent_id." AND post_type = 'cl_html'";
	$sql_results = $wpdb->get_results($sql);
	echo "<br><br>";
    
    // if there is already an value in the db, update it, else insert it
	if(count($sql_results) > 0) {
		$update = "UPDATE ".$wpdb->prefix.$blog_id."_posts SET post_content = '$attachment' WHERE ID = ".$sql_results[0]->ID;
		$wpdb->query($update);
	} else {
		$insert = "INSERT INTO ".$wpdb->prefix.$blog_id."_posts(post_content, post_type, post_mime_type, post_parent, post_status) VALUES('$attachment','cl_html', 'text/html', '$parent_id', 'inherit')";
		$wpdb->query($insert);
	}

}
add_action('cl_save_html_as_attachement', 'cl_save_content', 10 , 3);

function cl_modify_post($post) {
	global $wpdb;
	global $array;
	// quick and dirty, so the fnc will get called only once
	if(!$array)
		$array = array();
	if(!in_array($post->ID,$array)) {
		$array[] = $post->ID;
		
		//lädt aus datenbank den zwischengespeicherten fremdcontent
		//läd attachment aus db und fügt es an beitrag an.
		$query = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_parent=".$post->ID." AND post_type = 'cl_html'";
		
		$result = $wpdb->get_results($query);

		$post->post_content = $post->post_content.$result[0]->post_content;
		return $post;
	}
}

add_action('rest_api_print_post', 'cl_modify_post', 1);
add_action('the_post', 'cl_modify_post');


// do_action in der rest api: bei ausgabe von posts muss bei entsprechendem meta tag ein do_action('rest_api_print_post') aufgerufen werden
// do_action in der rest api: bei ausgabe von posts muss bei
//entsprechendem meta tag ein do_action('rest_api_print_post') aufgerufen
//werden
function cl_update () {
	global $wp_query;
	global $wpdb;
	
	// wird regelmäßig durch cronjob gestartet
	// parse var content-loader aus url
	$cl_action = $wp_query->query_vars['content-loader'];
	//update content loader content?
	if( $cl_action == "update" ) {

		// get all blogs / instances (augsburg, regensburg, etc)
		// geh durch alle blogs und schaue nach plugin um prefix id zu
		//bekommen
		$query = "SELECT blog_id FROM wp_blogs where blog_id > 1";
		$all_blogs = $wpdb->get_results($query);
		foreach( $all_blogs as $blog ){
			$blog_id = $blog->blog_id;
			echo "current blog $blog_id<br>";
			$bla = "select * from ".$wpdb->base_prefix.$blog_id."_postmeta where meta_key = 'ig-content-loader-base'";
			// query alle objekte in db mit meta_key =ig-content-loader-base
			// .$wpdb->prefix. anstatt wp_2
			//get all posts from instance where content-loader provides additonal content
			//var_dump($bla);
			$result = $wpdb->get_results($bla);
  
			// ist leerer string obwohl result pointer stimmt ... irgendwie result typ zu string umwandeln
			foreach($result as $item) {
				$parent_id = "".$item->post_id;
				echo "current post_parent $parent_id<br>";
				$meta_val = "".$item->meta_value;;
				do_action('cl_update_content', $parent_id, $meta_val,$blog_id);
			}
		}
		exit;
	}
	else{}

}
add_action( 'template_redirect', 'cl_update' );


// do_action in der rest api: bei ausgabe von posts muss bei entsprechendem meta tag ein do_action('rest_api_print_post') aufgerufen werden
// post type für attachement
function cl_init() {
	// es gibt var content-loader
	add_rewrite_tag( '%content-loader%', '([^&]+)' );
	//add_rewrite_rule( 'content-loader/([^/]*)/?', 'index.php?content-loader=$matches[1]', 'top');
	register_post_type( 'cl_html',
	array(
	  'labels' => array(
		'name' => __( 'Content-loader HTML' ),
		'singular_name' => __( 'Content-loader HTML' )
	  )
	)
  );

}
add_action( 'init', 'cl_init' );


?>
