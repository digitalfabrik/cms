<?php
/**
 * Plugin Name: Content Loader Instance
 * Description: Load content from another instance
 * Version: 0.1
 * Author: Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */


function cl_in_update_content( $parent_id, $meta_value, $blog_id ) {

	// sprungbrett praktika -> ig-content-loader-sprungbrett
	if( $meta_value == "ig-content-loader-instance" ) {
 
		$key_blog_id = 'ig-content-loader-instance-blog-id';
		$key_post_id = 'ig-content-loader-instance-post-id';	   
		
		$original_blog_id = get_current_blog_id();
		
		$source_blog_id = get_post_meta( $parent_id, $key_blog_id, true);
		$source_post_id = get_post_meta( $parent_id, $key_post_id, true);
		
		// switch to data origin block
		switch_to_blog( $blog_id );
		
		$html = get_post( $source_post_id )->post_content;
		
		// switch back to network site
		switch_to_blog( $original_blog_id );
		
		cl_save_content( $parent_id, $html, $blog_id);

		return;
	}
}
add_action( 'cl_update_content','cl_sb_update_content', 10, 3 );

// registriert plugin in base and return meta infos
function cl_in_metabox_item( $array ) {
	$array[] = array('id'=>'ig-content-loader-instance', 'name'=>'Seite aus Fremdinstanz');
	return $array;
}
add_filter( 'cl_metabox_item', 'cl_in_metabox_item' );

function cl_in_metabox_ajax() {
	$selected_instance = $_POST['cl-metabox-instance-id'];
	$selected_post = $_POST['cl-metabox-instance-post-id'];

	/*
	 * Neither instance nor post has been selected. Return instance dropdown.
	 */
	if ( !$selected_instance && !$selected_post ) {
		echo cl_in_generate_instance_dropdown();
	}
	/*
	 * An instance has been selected but no page. Therefore return a dropdown with all pages in instance 
	 */
	elseif ( $selected_instance && !$selected_post ) {
		echo cl_in_generate_post_dropdown( $blog_id );
	}
	/*
	 * Instance and post selected. Needs no ajax but need to save and save_post
	 */ 
	elseif ( $selected_instance && $selected_post ) {
		
	}
	
}

function cl_in_add_js() {
?>
	<script type="text/javascript" >
	jQuery(document).ready(function($) {
		jQuery("#cl_content_select").on('change', function() {
			//window.alert( this.value );
			if(this.value == 'ig-content-loader-instance') {
				//window.alert("if")
				var data = {
					'action': 'cl_in_blogs_dropdown'
				};
				jQuery.post(ajaxurl, data, function(response) {
					//alert('Got this from the server: ' + response);
					jQuery('#cl_metabox_extra').html(response);
					//alert(response);
				});
			} else {
				//window.alert("else")
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
				//alert(this.value);
				jQuery.post(ajaxurl, data, function(response) {
					//alert('Got this from the server: ' + response);
					jQuery('#cl_in_metabox_pages').html(response);
					//alert(response);
				});
			});
			jQuery("#cl_in_select_post_id").on('change', function() {
				var data = {
					'action': 'cl_in_pages_dropdown',
					'cl_in_post_language': '<?php echo ICL_LANGUAGE_CODE; ?>',
					'cl_in_blog_id': this.value
				};
				//alert(this.value);
				jQuery.post(ajaxurl, data, function(response) {
					window.alert("post id")
					//alert('Got this from the server: ' + response);
					//jQuery('#cl_in_metabox_pages').html(response);
					//alert(response);
				});
			});
		});
	});

	</script> <?php
}
add_action( 'cl_add_js', 'cl_in_add_js' );

function cl_in_blogs_dropdown() {
	global $wpdb;
	// get all blogs / instances (augsburg, regensburg, etc)
	$query = "SELECT blog_id FROM wp_blogs where blog_id > 1";
	$all_blogs = $wpdb->get_results($query);
	?>
	<div id="div_cl_in_metabox_instance">
	<p style="font-weight:bold;" id="cl_in_title">Bitte Kommune ausw&auml;hlen</p>
	<select style="width: 100%;" id="cl_in_select_blog_id" name="cl_in_select_blog_id">
		<option selected="selected" value="">Bitte w&auml;hlen</option>
		<?php
		foreach( $all_blogs as $blog ){
			
			$blog_name = get_blog_details( $blog->blog_id )->blogname;
			echo "<option value='".$blog->blog_id."'>$blog_name</option>";
		}
		?>
	</select>
	<p id="cl_in_metabox_pages"></p>
	</div>
	<?php
	//echo '<p id="cl-in-metabox">yay</p>';
	exit;
}
add_action( 'wp_ajax_cl_in_blogs_dropdown', 'cl_in_blogs_dropdown' );

function cl_in_pages_dropdown() {
	$blog_id = $_POST['cl_in_blog_id'];
	$language_code = $_POST['cl_in_post_language'];

	// query all objects in db with meta_key = ig-content-loader-base
	//$results = "SELECT post_title FROM ".$wpdb->base_prefix.$blog_id."_posts p LEFT JOIN ".$wpdb->base_prefix.$blog_id."_icl_translations t ON p.ID = t.element_id WHERE p.post_type='page' AND p.post_status='publish' AND t.language_code='$language_code'";

	//$result = $wpdb->get_results($results);
	$original_blog_id = get_current_blog_id(); 
	switch_to_blog( $blog_id ); 
	//echo wp_list_pages ();
	$pages = get_pages();
	echo '<select id="cl_in_select_post_id" name="cl_in_select_post_id">';
	foreach ($pages as $page) {
		echo "<option value=\"".$page->ID."\">".$page->post_title."</option>";
	}
	echo "</select>";
	//switch_to_blog( $original_blog_id ); 
	exit;
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
add_action( 'cl_save_meta_box', 'cl_in_save_meta_box' , 3 );

?>
