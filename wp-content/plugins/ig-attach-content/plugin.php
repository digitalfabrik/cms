<?php
/**
 * Plugin Name: Integreat - Attach Content
 * Description: Attach other pages to page
 * Version: 1.0
 * Author: Sven Seeberg <seeberg@integreat-app.de>
 * Author URI: https://github.com/Integreat
 * License: MIT
 * Text Domain: ig-attach-content
 */


/**
 * Load plugin text domain for translations in backend
 */
function ig_ac_backend() {
	load_plugin_textdomain( 'ig-attach-content', false, $plugin_dir );
}
add_action( 'admin_menu', 'ig_ac_backend' );

/**
 * Add meta box to pages. The meta box should have 2 drop down menus, one for the blog and a second
 * for the page. It also contains 2 radio buttons: attach content to beginning or end of current page. 
 */
function ig_ac_generate_selection_box() {
	add_meta_box( 'meta-box-id', __( 'Load other page', 'ig-content-loader-base' ), 'ig_ac_create_metabox', 'page', 'side' );
}
add_action( 'add_meta_boxes_page', 'ig_ac_generate_selection_box' );
 
/**
 * Generate meta box.
 *
 * @param WP_Post $post Current post object.
 */
function ig_ac_create_metabox( $post ) {
	wp_nonce_field( basename( __FILE__ ), 'prfx_nonce' );
	$ac_position = get_post_meta( $post->ID, 'ig-attach-content-position', true );
	$ac_blog = get_post_meta( $post->ID, 'ig-attach-content-blog', true );
	$ac_page = get_post_meta( $post->ID, 'ig-attach-content-page', true );
	ig_ac_meta_box_html( $options, $radio_value, $cl_metabox_extra );
}

/**
 * Writes meta box HTML directly to the output buffer.
 *
 * @param integer $selected_blog ID of preselected blog
 * @param integer $radio_value Selected radio button ID
 */
function ig_ac_meta_box_html( $selected_blog, $radio_value, $cl_metabox_extra = '' ) {
	global $post;
?>
	<script type="text/javascript" >
	jQuery(document).ready(function($) {
		jQuery("#cl_content_select").on('change', function() {
			if(this.value == 'ig-content-loader-instance') {
				var data = {
					'action': 'ig_ac_blogs_dropdown'
				};
				jQuery.post(ajaxurl, data, function(response) {
					jQuery('#ig_ac_metabox_extra').html(response);
				});
			} else {
				jQuery("#div_ig_ac_metabox_instance").html('')
				jQuery("#div_ig_ac_metabox_instance").remove()
			}
		});
		jQuery(document).bind('DOMNodeInserted', function(e) {
			jQuery("#ig_ac_select_blog_id").on('change', function() {
				var data = {
					'action': 'ig_ac_pages_dropdown',
					'ig_ac_post_language': '<?php echo ICL_LANGUAGE_CODE; ?>',
					'ig_ac_blog_id': this.value
				};
				jQuery.post(ajaxurl, data, function(response) {
					jQuery('#ig_ac_metabox_pages').html(response);
				});
			});
		});
	});
	</script> 
	<!-- Radio-button: Insert foreign content before or after page and preselect saved item, if there was any -->
	<p id="cl_metabox_position">
		<span style="font-weight:600" class="cl-row-title"><?php __( 'Insert content', 'ig-content-loader-base' )?></span>
		<div class="cl-row-content">
			<label for="meta-radio-one" style="display: block;box-sizing: border-box; margin-bottom: 8px;">
				<input type="radio" name="meta-radio" id="insert-pre-radio" value="beginning" <?php checked( $radio_value, 'beginning' ); ?>>
				<?php echo __( 'At beginning', 'ig-content-loader-base' )?>
			</label>
			<label for="meta-radio-two">
				<input type="radio" name="meta-radio" id="insert-suf-radio" value="end" <?php checked( $radio_value, 'end' ); ?>>
				<?php echo __( 'At end', 'ig-content-loader-base' )?>
			</label>
		</div>
	</p>

	<div id="ig_ac_metabox_extra"><?php echo ig_ac_blogs_dropdown(); ?></div>
	<?php  
}

/**
* Save Meta Box contents (content dropdown + append before or after radiogroup) in post_meta database
*
* @param int $post_id Post ID
*/
function ig_ac_save_meta_box($post_id) {


	$key_position = 'ig-attach-content-position';
	$key_blog = 'ig-attach-content-blog';
	$key_page = 'ig-attach-content-page';

	if($_POST[$key_blog] == -1) {
		delete_post_meta( $post_id, $key_position);
		delete_post_meta( $post_id, $key_blog);
		delete_post_meta( $post_id, $key_page);
	} else {
		update_post_meta( $post_id, $key_position, $_POST[$key_position] );
		update_post_meta( $post_id, $key_blog, $_POST[$key_blog] );
		update_post_meta( $post_id, $key_page, $_POST[$key_page] );
	}
}
add_action('save_post', 'ig_ac_save_meta_box');
add_action('edit_post', 'ig_ac_save_meta_box');

/*
* update post modified date, usually for parent of attached post. Necessary to push updates to App
* @param int $post_id, int $blog_id
*/
function ig_ac_update_parent_modified_date( $parent_id, $blog_id ) {
	global $wpdb;
	$datetime = date("Y-m-d H:i:s");
	$gmtdatetime = gmdate("Y-m-d H:i:s");
	$select = $sql = "SELECT * FROM ".$wpdb->base_prefix.$blog_id."_posts WHERE post_parent =".$parent_id." AND post_type = 'revision' ORDER BY ID DESC LIMIT 1";
	$sql_results = $wpdb->get_results($sql);
	if(count($sql_results) > 0)
		$update_query = "UPDATE ".$wpdb->base_prefix.$blog_id."_posts SET `post_modified` = '".$datetime."', `post_modified_gmt` = '".$gmtdatetime."' WHERE `ID` = '".$sql_results[0]->ID."'";
	else
		$update_query = "UPDATE ".$wpdb->base_prefix.$blog_id."_posts SET `post_modified` = '".$datetime."', `post_modified_gmt` = '".$gmtdatetime."' WHERE `ID` = '".$parent_id."'";
	if($wpdb->query( $update_query ))
		return true;
	else
		return false;
}


function ig_ac_blogs_dropdown( $blog_id = false, $pages_dropdown = '' ) {
	$key_blog_id = 'ig-content-loader-instance-blog-id';
	$key_post_id = 'ig-content-loader-instance-post-id';

	$old_blog_id = get_post_meta( $post_id, $key_blog_id, true );
	$old_post_id = get_post_meta( $post_id, $key_post_id, true );
	global $wpdb;
	if ( $blog_id ) {
		$ajax = false;
	} else {
		$ajax = true;
	}
	// get all blogs / instances (augsburg, regensburg, etc)
	$query = "SELECT blog_id FROM wp_blogs where blog_id > 1";
	$all_blogs = $wpdb->get_results($query);
	$output = '<div id="div_ig_ac_metabox_instance">
	<p style="font-weight:bold;" id="ig_ac_title">'.__('Select city', 'ig-content-loader-instance').'</p>
	<select style="width: 100%;" id="ig_ac_select_blog_id" name="ig_ac_select_blog_id">
		<option value="">'.__('Please select', 'ig-content-loader-instance').'</option>';
		foreach( $all_blogs as $blog ){
			
			$blog_name = get_blog_details( $blog->blog_id )->blogname;
			$output .= "<option value='".$blog->blog_id."' ".selected( $blog->blog_id, $blog_id, false ).">$blog_name</option>";
		}
	$output .= '</select>
	<p id="ig_ac_metabox_pages">'.$pages_dropdown.'</p>
	</div>';
	if ( $ajax == true ) {
		echo $output;
		exit;
	} else {
		return $output;
	}
}
add_action( 'wp_ajax_ig_ac_blogs_dropdown', 'ig_ac_blogs_dropdown' );

function ig_ac_pages_dropdown( $blog_id = false, $language_code = false, $post_id = false ) {
	if ( $blog_id == false ) {
		$blog_id = $_POST['ig_ac_blog_id'];
		$ajax = true;
	} else {
		$ajax = false;
	}
	if ( $language_code == false ) {
		$language_code = $_POST['ig_ac_post_language'];
	}

	$original_blog_id = get_current_blog_id(); 
	switch_to_blog( $blog_id ); 
	$pages = get_pages();
	$output = '<select id="ig_ac_select_post_id" name="ig_ac_select_post_id">';
	foreach ($pages as $page) {
		$orig_title = get_the_title( icl_object_id($page->ID, 'post', true, wpml_get_default_language()));
		$output .= "<option value=\"".$page->ID."\" ".selected( $page->ID, $post_id,false ).">".$orig_title." — ".$page->post_title."</option>";
	}
	$output .= "</select>";
	switch_to_blog( $original_blog_id ); 
	if ( $ajax == true ) {
		echo $output;
		exit;
	} else {
		return $output;
	}
}
add_action( 'wp_ajax_ig_ac_pages_dropdown', 'ig_ac_pages_dropdown' );


/**
 * Modify Post by getting foreign content form database and adding it to the page
 * Also check post_meta value for radio group to concatenate content before or after page-contents.
 * This function should be called when the content is displayed, for example by the REST API.
 *
 * @param $post current post object
 */
function ig_ac_modify_post($post) {
	global $wpdb;
	
	/**
	 * In some cases it seems that the API is working through some posts more than
	 * once. In such cases we don't want to attach the content multiple times.
	 * Therefore we store if we already manipulated a page and return if that is
	 * the case.
	 */
	global $ig_ac_already_manipulated;
	if ( !$ig_ac_already_manipulated ) {
		$ig_ac_already_manipulated = array();
	}
	if ( in_array( $post->ID, $ig_ac_already_manipulated) ) {
		return $post;
	}
	$ig_ac_already_manipulated[] = $post->ID;
	
	/**
	 * Get the post_meta information. get_post_meta returns an empty string if
	 * the key does not exist. If the key is empty, no other page should be attached.
	 * We then return the unmodified post. Otherwise we fetch the content from the
	 * blog and add the content to the beginning or end.
	 */
	$ac_position = get_post_meta( $post->ID, 'ig-attach-content-position', true );
	if(count($ac_position) > 0 ) {
		$ac_blog = get_post_meta( $post->ID, 'ig-attach-content-blog', true );
		$ac_page = get_post_meta( $post->ID, 'ig-attach-content-page', true );

		switch_to_blog($ac_blog);
		$attach_content = get_post($ac_page)->post_content;
		restore_current_blog();
		if ( 'end' == $ac_position ) {
			$post->post_content = $post->post_content . $attach_content;
		} elseif ( 'end' == $ac_position ) {
			$post->post_content = $attach_content . $post->post_content;
		}
	}
	return $post;
}
add_filter('wp_api_extensions_pre_post', 'ig_ac_modify_post', 10, 2);
add_action('the_post', 'ig_ac_modify_post');