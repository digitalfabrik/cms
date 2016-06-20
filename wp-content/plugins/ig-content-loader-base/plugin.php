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
 * Register Cron Job Hook to update data every 12 hour 
 */
//register_activation_hook(__FILE__, 'cl_update_content');
//
//function cl_update_content() {
//    if (! wp_next_scheduled ( 'cl_update_contents' )) {
//	wp_schedule_event(time(), 'twicedaily', 'cl_update_contents');
//    }
//}
//
//add_action('cl_update_contents', 'cl_update_content');



/**
 * Initiate Rewrite Tag for Cron Job and Register custom Post Type for HTML Atachement
 */
function cl_init() {
	add_rewrite_tag( '%content-loader%', '([^&]+)' );

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


/**
 *  Register Meta box Hook
 */
function cl_generate_selection_box() {
	add_meta_box( 'meta-box-id', __( 'Fremdinhalte einfügen', 'textdomain' ), 'cl_my_display_callback', 'page', 'side' );
}
add_action( 'add_meta_boxes_page', 'cl_generate_selection_box' );

 
/**
 * Meta Box display Callback.
 *
 * @param WP_Post $post Current post object.
 */
function cl_my_display_callback( $post ) {

	wp_nonce_field( basename( __FILE__ ), 'cl_nonce' );
    
	$dropdown_items = apply_filters('cl_metabox_item', $array);
    
    // get post meta for radio group and option-select to preselect the saved value
    $radio_value = get_post_meta( $post->ID, 'ig-content-loader-base-position', true );
    $option_value = get_post_meta( $post->ID, 'ig-content-loader-base', true );
?>

    <!-- Dropdown-select for foreign contents -->
    <p>
        <label style="font-weight:600" for="meta-select" class="cl-row-title">
            <?php _e( 'Inhalt wählen', 'cl-textdomain' )?>
        </label>
        <select name="cl_content_select" id="meta-select" style="width:100%; margin-top:10px; margin-bottom:10px">
            <!-- build select items from filtered plugin list and preselect saved item, if there was any -->
            <option>--Plugin picken--</option>
            <?php 
				foreach($dropdown_items as $cl_plugin_name_option) {
					print('<option name="cl_content_select_item" value="'.$cl_plugin_name_option->id.'"'.selected( $option_value, $cl_plugin_name_option->id ).'>'.$cl_plugin_name_option->name.'</option>');
				}
			?>

        </select>
    </p>

    <!-- Radio-button: Insert foreign content before or after page and preselect saved item, if there was any -->
    <p>
        <span style="font-weight:600" class="cl-row-title"><?php _e( 'Inhalt Einfügen', 'cl-textdomain' )?></span>
        <div class="cl-row-content">
            <label for="meta-radio-one" style="display: block;box-sizing: border-box; margin-bottom: 8px;">
                <input type="radio" name="meta-radio" id="insert-pre-radio" value="anfang" <?php checked( $radio_value, 'anfang' ); ?>>
                <?php _e( 'Am Anfang', 'cl-textdomain' )?>
            </label>
            <label for="meta-radio-two">
                <input type="radio" name="meta-radio" id="insert-suf-radio" value="ende" <?php checked( $radio_value, 'ende' ); ?>>
                <?php _e( 'Am Ende', 'cl-textdomain' )?>
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
* Save Meta Box contents (content dropdown + append before or after radiogroup) in post_meta database
*
* @param int $post_id Post ID
*/
function cl_save_meta_box($post_id) {

	// key for base-plugin in wp_postmeta
	$meta_key = 'ig-content-loader-base';
    $meta_key_position = 'ig-content-loader-base-position';
  
	//get the selected value from the meta box dropdown-select and the radio group
    $meta_value = ( isset( $_POST['cl_content_select'] ) ? $_POST['cl_content_select'] : '' );
    $meta_value_position = ( isset( $_POST['meta-radio'] ) ? $_POST['meta-radio'] : '' );
    
	//read old post meta settings
	$old_meta_value = get_post_meta( $post_id, $meta_key, true );
    $old_meta_value_position = get_post_meta ($post_id, $meta_key_position, true);
	
    /* meta value for dropdown select */
	if ($meta_value != '') {
	  
	//if there was no old post meta entry, add it
	if ( '' == $old_meta_value )
		add_post_meta( $post_id, $meta_key, $meta_value, true );

	//if the old post meta value is different from the posted one, change it
	elseif ( $old_meta_value != $meta_value )
		update_post_meta( $post_id, $meta_key, $meta_value );
	}
  
	//if there is no plugin selected but there is one in the db, remvoe meta value from wp_postmeta and deactive content-loader plugin
	elseif ( '' == $meta_value && $old_meta_value ) {
		delete_post_meta( $post_id, $meta_key, $meta_value );
	}
    
    /* meta value for radio buttons */
    if($meta_value_position != '') {
        
    if( '' == $old_meta_value_position) 
        add_post_meta($post_id, $meta_key_position, $meta_value_position, true);
        
	elseif ( $old_meta_value_position != $meta_value_position )
		update_post_meta( $post_id, $meta_key_position, $meta_value_position );
	}

	elseif ( '' == $meta_value_position && $old_meta_value_position ) {
		delete_post_meta( $post_id, $meta_key_position, $meta_value_position );
	}
}

/**
* Safe foreign content in database as html code
* 
* @param int $parent_id, string $attachment, int $blog_id
*/
function cl_save_content( $parent_id, $attachment, $blog_id) {
	global $wpdb;
    
    // get content item from database
	$sql = "SELECT * FROM ".$wpdb->prefix.$blog_id."_posts WHERE post_parent =".$parent_id." AND post_type = 'cl_html'";
	$sql_results = $wpdb->get_results($sql);
    
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


/**
 * Modify Post by getting foreign content form database and adding it to the page
 * Also check post_meta value for radio group to concatenate content before or after page-contents
 *
 * @param $post current post object
 */
function cl_modify_post($post) {
	global $wpdb;
	global $array;
    
    

	if(!$array)
		$array = array();
	if(!in_array($post->ID,$array)) {
		$array[] = $post->ID;
		
		// get foreign cotennt from database
		$query = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_parent=".$post->ID." AND post_type = 'cl_html'";
		
        // execute sql statement in $query
		$result = $wpdb->get_results($query);

        /* get saved post meta for radio group from db */
        $option_value = get_post_meta( $post->ID, 'ig-content-loader-base-position', true );
        // get post meta from db and compare
        if($option_value == 'ende') {
        // add foreign content from db to the end of the post
		$post->post_content = $post->post_content.$result[0]->post_content."ok";
        } else {
        // add foreign content from db to the front of the post
        $post->post_content = $result[0]->post_content.$post->post_content.$meta_value;
        }
        return $post;
	}
}

add_action('rest_api_print_post', 'cl_modify_post', 1);
add_action('the_post', 'cl_modify_post');


/**
 * Update Contents by parsing update-url, gets called twice daily by Cron Job
 *
 */
function cl_update () {
	global $wp_query;
	global $wpdb;
	
    // query url for 'content-loader'
	$cl_action = $wp_query->query_vars['content-loader'];
	//if url contains ...=update
	if( $cl_action == "update" ) {
        
		// get all blogs / instances (augsburg, regensburg, etc)
		$query = "SELECT blog_id FROM wp_blogs where blog_id > 1";
		$all_blogs = $wpdb->get_results($query);
		foreach( $all_blogs as $blog ){
			$blog_id = $blog->blog_id;
            // query all objects in db with meta_key = ig-content-loader-base
			$results = "select * from ".$wpdb->base_prefix.$blog_id."_postmeta where meta_key = 'ig-content-loader-base'";

			$result = $wpdb->get_results($results);

			foreach($result as $item) {
				$parent_id = "".$item->post_id;
				$meta_val = "".$item->meta_value;
                $blog_name = get_blog_details($blog_id)->blogname;

				do_action('cl_update_content', $parent_id, $meta_val, $blog_id, $blog_name);
			}
		}
		exit;
	}
	else{}

}

add_action( 'template_redirect', 'cl_update' );

?>