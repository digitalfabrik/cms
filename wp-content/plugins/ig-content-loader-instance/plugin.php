<?php
/**
 * Plugin Name: Content Loader Instance
 * Description: Load content from another instance
 * Version: 1.0
 * Author: Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 * Text Domain: ig-content-loader-instance
 */

function cl_init_instance() {
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain( 'ig-content-loader-instance', false, $plugin_dir );
}
add_action( 'init', 'cl_init_instance' );


function cl_in_update_content( $parent_id, $meta_value, $blog_id ) {

	// sprungbrett praktika -> ig-content-loader-sprungbrett
	if( $meta_value == "ig-content-loader-instance" ) {
		switch_to_blog( $blog_id );
 
		$key_blog_id = 'ig-content-loader-instance-blog-id';
		$key_post_id = 'ig-content-loader-instance-post-id';	   
		
		$source_blog_id = get_post_meta( $parent_id, $key_blog_id, true );
		$source_post_id = get_post_meta( $parent_id, $key_post_id, true );

		// switch to data origin block
		switch_to_blog( $source_blog_id );

		$html = get_post( $source_post_id )->post_content;

		// switch back to network site
		switch_to_blog( 0 );

		cl_save_content( $parent_id, $html, $blog_id);

		return;
	}
}
add_action( 'cl_update_content','cl_in_update_content', 10, 3 );

function cl_in_metabox_item( $array ) {
	$array[] = array('id'=>'ig-content-loader-instance', 'name'=>__('Page from other instance', 'ig-content-loader-instance'));
	return $array;
}
add_filter( 'cl_metabox_item', 'cl_in_metabox_item' );

function cl_in_add_js() {
?>
	<script type="text/javascript" >
	jQuery(document).ready(function($) {
		jQuery("#cl_content_select").on('change', function() {
			if(this.value == 'ig-content-loader-instance') {
				var data = {
					'action': 'cl_in_blogs_dropdown'
				};
				jQuery.post(ajaxurl, data, function(response) {
					jQuery('#cl_metabox_extra').html(response);
				});
			} else {
				jQuery("#div_cl_in_metabox_instance").html('')
				jQuery("#div_cl_in_metabox_instance").remove()
			}
		});
		jQuery(document).bind('DOMNodeInserted', function(e) {
			jQuery("#cl_in_select_blog_id").on('change', function() {
				var data = {
					'action': 'cl_in_pages_dropdown',
					'cl_in_post_language': '<?php echo ICL_LANGUAGE_CODE; ?>',
					'cl_in_blog_id': this.value
				};
				jQuery.post(ajaxurl, data, function(response) {
					jQuery('#cl_in_metabox_pages').html(response);
				});
			});
		});
	});

	</script> <?php
}
add_action( 'cl_add_js', 'cl_in_add_js' );

function cl_in_blogs_dropdown( $blog_id = false, $pages_dropdown = '' ) {
	global $wpdb;
	if ( $blog_id ) {
		$ajax = false;
	} else {
		$ajax = true;
	}
	// get all blogs / instances (augsburg, regensburg, etc)
	$query = "SELECT blog_id FROM wp_blogs where blog_id > 1";
	$all_blogs = $wpdb->get_results($query);
	$output = '<div id="div_cl_in_metabox_instance">
	<p style="font-weight:bold;" id="cl_in_title">'.__('Select city', 'ig-content-loader-instance').'</p>
	<select style="width: 100%;" id="cl_in_select_blog_id" name="cl_in_select_blog_id">
		<option value="">'.__('Please select', 'ig-content-loader-instance').'</option>';
		foreach( $all_blogs as $blog ){
			
			$blog_name = get_blog_details( $blog->blog_id )->blogname;
			$output .= "<option value='".$blog->blog_id."' ".selected( $blog->blog_id, $blog_id, false ).">$blog_name</option>";
		}
	$output .= '</select>
	<p id="cl_in_metabox_pages">'.$pages_dropdown.'</p>
	</div>';
	if ( $ajax == true ) {
		echo $output;
		exit;
	} else {
		return $output;
	}
}
add_action( 'wp_ajax_cl_in_blogs_dropdown', 'cl_in_blogs_dropdown' );

function cl_in_pages_dropdown( $blog_id = false, $language_code = false, $post_id = false ) {
	if ( $blog_id == false ) {
		$blog_id = $_POST['cl_in_blog_id'];
		$ajax = true;
	} else {
		$ajax = false;
	}
	if ( $language_code == false ) {
		$language_code = $_POST['cl_in_post_language'];
	}

	$original_blog_id = get_current_blog_id(); 
	switch_to_blog( $blog_id ); 
	$pages = get_pages();
	$output = '<select id="cl_in_select_post_id" name="cl_in_select_post_id">';
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
add_action( 'wp_ajax_cl_in_pages_dropdown', 'cl_in_pages_dropdown' );

function cl_in_save_meta_box ( $post_id, $old_meta_value, $meta_value ) {

	$added_blog_id = $_POST['cl_in_select_blog_id'];
	$added_post_id = $_POST['cl_in_select_post_id'];

	$key_blog_id = 'ig-content-loader-instance-blog-id';
	$key_post_id = 'ig-content-loader-instance-post-id';

	$old_blog_id = get_post_meta( $post_id, $key_blog_id, true );
	$old_post_id = get_post_meta( $post_id, $key_post_id, true );

	// if the content loader instance is removed, we want to remove all related meta data
	if ( $old_meta_value == 'ig-content-loader-instance' && $meta_value != 'ig-content-loader-instance' ) {
		delete_post_meta( $post_id, $key_blog_id, $meta_value );
		delete_post_meta( $post_id, $key_post_id, $meta_value );
	}	
	// content loader instance is added, save meta data
	elseif ( $meta_value == 'ig-content-loader-instance' ) {
		if ( $added_blog_id && $added_post_id ) {			
			if ( $old_blog_id )
				update_post_meta( $post_id, $key_blog_id, $added_blog_id );
			else
				add_post_meta( $post_id, $key_blog_id, $added_blog_id );
				
			if ( $old_post_id )
				update_post_meta( $post_id, $key_post_id, $added_post_id );
			else
				add_post_meta( $post_id, $key_post_id, $added_post_id );
		}
	}
}
add_action( 'cl_save_meta_box', 'cl_in_save_meta_box' , 10, 3 );

function cl_in_metabox_extra( $cl_metabox_extra, $module, $post_id ) {
	if( $module == 'ig-content-loader-instance' ) {
		$key_blog_id = 'ig-content-loader-instance-blog-id';
		$key_post_id = 'ig-content-loader-instance-post-id';

		$old_blog_id = get_post_meta( $post_id, $key_blog_id, true );
		$old_post_id = get_post_meta( $post_id, $key_post_id, true );

		$output = cl_in_blogs_dropdown( $old_blog_id, cl_in_pages_dropdown( $old_blog_id, ICL_LANGUAGE_CODE, $old_post_id ) );
		return $output;
	}
}
add_filter( 'cl_metabox_extra', 'cl_in_metabox_extra', 10, 3);

?>
