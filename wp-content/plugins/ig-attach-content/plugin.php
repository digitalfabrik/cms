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
add_action( 'plugins_loaded', function() {
	load_plugin_textdomain('ig-attach-content', false, basename( dirname( __FILE__ )));
});

/**
 * Add meta box to pages. The meta box should have 2 drop down menus, one for the blog and a second
 * for the page. It also contains 2 radio buttons: attach content to beginning or end of current page.
 */
function ig_ac_generate_selection_box() {
	add_meta_box( 'ig_ac_metabox', __( 'Live-Content', 'ig-attach-content' ), 'ig_ac_create_metabox', 'page', 'side' );
}
add_action( 'add_meta_boxes_page', 'ig_ac_generate_selection_box' );

/**
 * Generate meta box.
 *
 * @param WP_Post $post Current post object.
 */
function ig_ac_create_metabox( $post ) {
	wp_nonce_field( basename( __FILE__ ), 'prfx_nonce' );
	ig_ac_meta_box_html();
}

/**
 * Writes meta box HTML directly to the output buffer.
 */
function ig_ac_meta_box_html( ) {
	global $post;
	$ac_position = get_post_meta( $_GET['post'], 'ig-attach-content-position', true);
?>
	<script type="text/javascript" >
		jQuery(document).ready(function($) {
			jQuery("#ig-attach-content-blog").on('change', function() {
				var data = {
					'action': 'ig_ac_pages_dropdown',
					'ig-attach-content-language': '<?php echo ICL_LANGUAGE_CODE; ?>',
					'ig-attach-content-blog': this.value
				};
				jQuery.post(ajaxurl, data, function(response) {
					jQuery('#ig_ac_metabox_pages').html(response);
				});
			});
		});
	</script>
	<!-- Radio-button: Insert foreign content before or after page and preselect saved item, if there was any -->
	<p id="cl_metabox_position">
		<span style="font-weight:600" class="cl-row-title"><?php __( 'Insert Live-Content', 'ig-attach-content' )?></span>
		<div class="cl-row-content">
			<p id="positioninfo"><?php echo __( 'Where should the mirrored data be displayed?', 'ig-attach-content')?></p>
			<label for="ig-attach-content-position-one" style="display: block;box-sizing: border-box; margin-bottom: 8px;">
				<input type="radio" name="ig-attach-content-position" id="ig-attach-content-position-one" value="beginning" <?php checked( $ac_position, 'beginning' ); ?>>
				<?php echo __( 'At the Beginning', 'ig-attach-content' )?>
			</label>
			<label for="ig-attach-content-position-two">
				<input type="radio" name="ig-attach-content-position" id="ig-attach-content-position-two" value="end" <?php checked( $ac_position, 'end' ); ?>>
				<?php echo __( 'At the End', 'ig-attach-content' )?>
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
function ig_ac_save_meta_box( $post_id ) {
	$key_position = 'ig-attach-content-position';
	$key_blog = 'ig-attach-content-blog';
	$key_page = 'ig-attach-content-page';
	if ( -1 == $_POST[$key_blog] ) {
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


/**
 * This function creates an HTML select with all available blogs.
 *
 * @param boolean $ajax Set to false if HTML should not be written to output buffer
 * @return string
 */
function ig_ac_blogs_dropdown( $ajax = false ) {
	$blog_id = get_post_meta( $_GET['post'], 'ig-attach-content-blog', true );
	global $wpdb;
	// get all blogs / instances (augsburg, regensburg, etc)
	$query = "SELECT blog_id FROM wp_blogs where blog_id > 1 ORDER BY domain ASC";
	$all_blogs = $wpdb->get_results($query);
	$output = '<div id="div_ig_ac_metabox_instance">
	<p style="font-weight:bold;" id="ig_ac_title">'.__('Select city', 'ig-attach-content').'</p>
	<select style="width: 100%;" id="ig-attach-content-blog" name="ig-attach-content-blog">
		<option value="-1">'.__('Please select', 'ig-attach-content').'</option>';
		foreach( $all_blogs as $blog ){
			$blog_disabled = apply_filters('ig-site-disabled', $blog);
			if( $blog_disabled == false ) {
				$blog_name = get_blog_details( $blog->blog_id )->blogname;
				$output .= "<option value='".$blog->blog_id."' ".selected( $blog->blog_id, $blog_id, false ).">$blog_name</option>";
			}
		}
	$output .= '</select>
	<p id="ig_ac_metabox_pages">'.( $blog_id > 0 ? ig_ac_pages_dropdown( $blog_id = $blog_id, $ajax = false ) : '').'</p>
	</div>';
	if ( $ajax == true ) {
		echo $output;
		exit;
	} else {
		return $output;
	}
}

/**
 * This function creates an HTML select with all available pages of a defined bloag
 * and language. If this function is called in an AJAX call, then the HTML code
 * is directly written to the output buffer.
 *
 * @param int $blog_id
 * @param boolean $ajax Set to false if HTML should not be written to output buffer
 * @return string
 */
function ig_ac_pages_dropdown( $blog_id = false, $ajax = true ) {
	if ( $blog_id == false ) {
		$blog_id = $_POST['ig-attach-content-blog'];
	}
	$post_id = get_post_meta( $_GET['post'], 'ig-attach-content-page', true );

	switch_to_blog( $blog_id );
	$args = array(
		'sort_order' => 'asc',
		'sort_column' => 'post_title',
		'post_type' => 'page',
		'post_status' => 'publish',
		'hierarchical' => 0,
	);
	$pages = get_pages($args);
	$output = '<select id="ig-attach-content-page" name="ig-attach-content-page">';
	foreach ($pages as $page) {
		$orig_title = get_the_title( icl_object_id($page->ID, 'post', true, wpml_get_default_language()));
		$output .= "<option value=\"".$page->ID."\" ".selected( $page->ID, $post_id, false ).">".$orig_title." — ".$page->post_title."</option>";
	}
	$output .= "</select>";
	restore_current_blog();
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
 * @param WP_Post $post current post object
 * @return WP_Post
 */
function ig_ac_modify_post( $post ) {
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
	if(strlen($ac_position) > 0 ) {
		$ac_blog = get_post_meta( $post->ID, 'ig-attach-content-blog', true );
		$ac_page = get_post_meta( $post->ID, 'ig-attach-content-page', true );

		switch_to_blog($ac_blog);
		$attach_content = get_post($ac_page)->post_content;
		restore_current_blog();
		if ( 'end' == $ac_position ) {
			$post->post_content = $post->post_content . $attach_content;
		} elseif ( 'beginning' == $ac_position ) {
			$post->post_content = $attach_content . $post->post_content;
		}
	}
	return $post;
}
/**
 * The page should be modified if it is loaded by normal themes with the the_post
 * function or via the API.
 */
add_filter('wp_api_extensions_pre_post', 'ig_ac_modify_post', 10, 2);
add_action('the_post', 'ig_ac_modify_post');


/**
 * A migration function from ig-content-loader-instance to ig-attach-content
 * On migration, all content-loader-instance settings are removed.
 */
function ig_ac_cl_migration () {
	global $wpdb;
	$query = "SELECT blog_id FROM wp_blogs where blog_id > 1";
	$all_blogs = $wpdb->get_results($query);
	foreach( $all_blogs as $blog ){
		$blog_id = $blog->blog_id;
		$results = "select * from ".$wpdb->base_prefix.$blog_id."_postmeta where meta_key = 'ig-content-loader-base'";
		$result = $wpdb->get_results($results);
		foreach($result as $item) {
			switch_to_blog($blog_id);
			$old_extension = get_post_meta( $item->post_id, 'ig-content-loader-base', true );
			$old_position = get_post_meta( $item->post_id, 'ig-content-loader-base-position', true );
			if("anfang" == $old_position)
				$old_position = "beginning";
			else
				$old_position = "end";
			$old_blog_id = get_post_meta( $item->post_id, 'ig-content-loader-instance-blog-id', true );
			$old_page_id = get_post_meta( $item->post_id, 'ig-content-loader-instance-post-id', true );
			file_put_contents("cl-ac-migration.log", "Old position: $old_position, old blog id: $old_blog_id, old page: $old_page_id\n", FILE_APPEND);
			if ( "ig-content-loader-instance" != $old_extension ) {
				file_put_contents("cl-ac-migration.log", "Skipping\n", FILE_APPEND);
				continue;
			}
			update_post_meta( $item->post_id, 'ig-attach-content-position', $old_position);
			update_post_meta( $item->post_id, 'ig-attach-content-blog', $old_blog_id);
			update_post_meta( $item->post_id, 'ig-attach-content-page', $old_page_id);
			delete_post_meta( $item->post_id, 'ig-content-loader-base');
			delete_post_meta( $item->post_id, 'ig-content-loader-base-position');
			delete_post_meta( $item->post_id, 'ig-content-loader-instance-blog-id');
			delete_post_meta( $item->post_id, 'ig-content-loader-instance-post-id');
		}
	}
	restore_current_blog();
}
register_activation_hook( __FILE__, 'ig_ac_cl_migration' );


/**
 * Append attachment status for tree view plugin. Hooks into
 * custom Integreat hook.
 *
 * @param array $status array of status labels
 * @param integer $post_id ID of the post item
 * @return array
 */
function ig_attach_content_tree_view_status( $status, $post_id ) {
	if( get_post_meta( $post_id, 'ig-attach-content-page', true ) != "" ) {
		$status[] = __('Live-Content', 'ig-attach-content');
	}
	return $status;
}
add_filter( 'ig-cms-tree-view-status',  'ig_attach_content_tree_view_status', 10, 2);

/**
 * Add a meta box directly below the editor.
 *
 * @param  string $post_type
 * @return null
 */
function ig_ac_preview_metabox( $post_type ) {
	if ( in_array( $post_type, array( 'page' ) ) ) {
		add_meta_box( 'ig_attach_content_preview', __( 'Live-Content Preview', 'ig-attach-content' ), 'ig_attach_content_preview', 'page', 'normal', 'high' );
	}
}
add_action( 'add_meta_boxes', 'ig_ac_preview_metabox' );


/**
 * Check if an attached page is selected and then output its content.
 *
 * @return null
 */
function ig_attach_content_preview ( ) {
	global $post;
	echo "<style>.ig-ac-img img {max-width:100%;height:auto;}</style>";
	echo "<div id='attach_content_preview' class='ig-ac-img'>";
	$ac_position = get_post_meta( $post->ID, 'ig-attach-content-position', true );
	if(strlen($ac_position) > 0 ) {
		$ac_blog = get_post_meta( $post->ID, 'ig-attach-content-blog', true );
		$ac_page = get_post_meta( $post->ID, 'ig-attach-content-page', true );

		switch_to_blog($ac_blog);
		$attach_content = get_post($ac_page)->post_content;
		restore_current_blog();
		echo $attach_content;
	} else {
		echo __("No live content selected.", "ig-attach-content");
	}
	echo "</div>";
}
