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
		if ( 'attachment' == $child->post_type ) {
			$options .= "<option value='" . $child->ID . "'" . ($child->ID==$revision_ids ? " selected" : "" ) . ">" . $child->post_date . "</option>";
		}
	}
	add_meta_box(
		'ig_revisions',
		__('Set Revision', 'ig_revisions'),
		'ig_revisions_metabox_html',
		null,
		'advanced',
		'default',
		$options
	);
}
add_action('add_meta_boxes', 'ig_revisions_metabox');


function wporg_add_custom_box()
{
    $screens = ['page', 'wporg_cpt'];
    foreach ($screens as $screen) {
        add_meta_box(
            'wporg_box_id',           // Unique ID
            'Custom Meta Box Title',  // Box title
            'wporg_custom_box_html',  // Content callback, must be of type callable
            $screen                   // Post type
        );
    }
}
add_action('add_meta_boxes', 'wporg_add_custom_box');

function wporg_custom_box_html($post)
{
    ?>
    <label for="wporg_field">Description for this field</label>
    <select name="wporg_field" id="wporg_field" class="postbox">
        <option value="">Select something...</option>
        <option value="something">Something</option>
        <option value="else">Else</option>
    </select>
    <?php
}

function ig_revisions_metabox_html ( $post, $options ) {
	var_dump($options);
	die();
?>
		<label><?php __( 'Set Revision', 'ig_revisions' ) ?></label>
		<select name="ig_set_revision" id="ig_set_revision" style="width:100%; margin-top:10px; margin-bottom:10px">
			<?php echo $options; ?>
		</select>
	</p>
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

