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

function ig_revisions_metabox( $post ) {

	//wp_nonce_field( basename( __FILE__ ), 'prfx_nonce' );

	$args = array(
		'post_parent' => $post->ID,
		'post_type'   => 'revision', 
		'numberposts' => -1,
		'post_status' => 'any'
	);
	$children = wp_get_post_revisions( $post_id, $args );
	
	$revision_id = get_post_meta( $post->ID, 'ig_revision_id', true );
	var_dump($post);
	var_dump($revision_id);
	die();

	$options = "<option value='-1'" . (!$revision_id ? " selected" : "") . ">None</option>";
	
	foreach($children as $child) {
		$options .= "<option value='" . $child->ID . "'" . ($child->ID==$revision_ids ? " selected" : "" ) . ">" . $child->post_date . "</option>";
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

function ig_revisions_metabox_html ( $post, $callback_args ) {
?>
		<label><?php __( 'Set Revision', 'ig_revisions' ) ?></label>
		<select name="ig_revision_id" id="ig_revision_id" style="width:100%; margin-top:10px; margin-bottom:10px">
			<?php echo $callback_args['args']; ?>
		</select>
	</p>
	<?php  
}

function ig_revisions_metabox_save ( $post_id ) {
	$meta_key = 'ig_revision_id';
	$meta_value = ( isset( $_POST['ig_revision_id'] ) ? $_POST['ig_revision_id'] : '' );
	update_post_meta( $post_id, $meta_key, $meta_value );
	var_dump($post_id);
	var_dump($meta_key);
	var_dump($meta_value);
	var_dump("bla");
	die();
}
add_action('save_post', 'ig_revisions_metabox_save');
add_action('edit_post', 'ig_revisions_metabox_save');
add_action('publish_post', 'ig_revisions_metabox_save');
add_action('edit_page_form', 'ig_revisions_metabox_save');

