<?php
/**
 * Plugin Name: Integreat Page Revisions
 * Description: Set a specific page revision for delivery via API
 * Version: 1.0
 * Author: Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 * Text Domain: ig-revisions
 */

/**
 * Meta Box display Callback.
 *
 * @param WP_Post $post Current post object.
 */
function ig_revisions_metabox( $post ) {

	wp_nonce_field( basename( __FILE__ ), 'prfx_nonce' );

	$args = array(
		'post_parent' => $post->ID,
		'post_type'   => 'revision', 
		'numberposts' => -1,
		'post_status' => 'any'
	);
	$children = get_children( $args );
	
	$revision_id = get_post_meta( $post->ID, 'ig_revision_id', true );

	$options = "<option value='-1'" . (!$revision_id ? " selected" : "") . ">None</option>";
	
	foreach($children as $child) {
		$options .= "<option value='" . $child['ID'] . "'" . ($child['ID']==$revision_ids ? " selected" : "" ) . ">" . $child['post_date'] . "</option>";
	}
	
	$cl_metabox_extra = apply_filters( 'cl_metabox_extra', $cl_metabox_extra, $option_value, $post->ID );
	cl_meta_box_html( $options, $radio_value, $cl_metabox_extra );
}
add_action('add_meta_boxes', 'ig_revisions_metabox');


function ig_revisions_metabox_html ( $options, $radio_value, $cl_metabox_extra = '' ) {
	global $post;
?>
	<!-- Dropdown-select for foreign contents -->
		<label>	<?php __( 'Set Revision', 'ig-content-loader-base' )?>	</label>
		<select name="cl_content_select" id="cl_content_select" style="width:100%; margin-top:10px; margin-bottom:10px">
			<!-- build select items from filtered plugin list and preselect saved item, if there was any -->
			<?php echo $options; ?>
		</select>
	</p>

	<!-- Radio-button: Insert foreign content before or after page and preselect saved item, if there was any -->
	<p id="cl_metabox_position">
		<span style="font-weight:600" class="cl-row-title"><?php __( 'Insert content', 'ig-content-loader-base' )?></span>
		<div class="cl-row-content">
			<label for="meta-radio-one" style="display: block;box-sizing: border-box; margin-bottom: 8px;">
				<input type="radio" name="meta-radio" id="insert-pre-radio" value="anfang" <?php checked( $radio_value, 'anfang' ); ?>>
				<?php echo __( 'At beginning', 'ig-content-loader-base' )?>
			</label>
			<label for="meta-radio-two">
				<input type="radio" name="meta-radio" id="insert-suf-radio" value="ende" <?php checked( $radio_value, 'ende' ); ?>>
				<?php echo __( 'At end', 'ig-content-loader-base' )?>
			</label>
		</div>
	</p>

	<div id="cl_metabox_extra"><?php echo $cl_metabox_extra; ?></div>
	<?php  
}

/**
* Save Meta Box contents (content dropdown + append before or after radiogroup) in post_meta database
*
* @param int $post_id Post ID
*/
function ig_revisions_metabox_save ($post_id) {

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
  
	//if there is no plugin selected but there is one in the db, remove meta value from wp_postmeta and deactive content-loader plugin
	elseif ( '' == $meta_value && $old_meta_value ) {
		delete_post_meta( $post_id, $meta_key, $meta_value );
		global $wpdb;
		$insert = "DELETE FROM ".$wpdb->base_prefix.get_current_blog_id()."_posts WHERE post_type = 'cl_html' AND post_parent = '$post_id'";
		$wpdb->query($insert);
		cl_update_parent_modified_date( $post_id, get_current_blog_id() );
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

	do_action( 'cl_save_meta_box', $post_id, $old_meta_value, $meta_value );
}
add_action('save_post', 'cl_save_meta_box');
add_action('edit_post', 'cl_save_meta_box');
add_action('publish_post', 'cl_save_meta_box');
add_action('edit_page_form', 'cl_save_meta_box');

