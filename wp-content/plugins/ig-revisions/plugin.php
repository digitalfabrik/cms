<?php
/**
 * Plugin Name: Integreat Page Revisions
 * Description: Set a specific page revision for delivery via API
 * Version: 1.0
 * Author: Integreat Team / Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 * Text Domain: ig-revisions
 */

function ig_revisions_metabox( $post ) {
	$children = wp_get_post_revisions( $_GET['post'] );
	$revision_id = get_post_meta( $_GET['post'], 'ig_revision_id', true );
	$options = "<option value='-1'" . (!$revision_id ? " selected" : "") . ">Current</option>";
	
	foreach($children as $child) {
		$options .= "<option value='" . $child->ID . "'" . ($child->ID==$revision_id ? " selected" : "" ) . ">" . $child->post_date . "</option>";
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
add_action( 'add_meta_boxes', 'ig_revisions_metabox' );

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
}
add_action('save_post', 'ig_revisions_metabox_save');
add_action('edit_post', 'ig_revisions_metabox_save');
add_action('publish_post', 'ig_revisions_metabox_save');
add_action('edit_page_form', 'ig_revisions_metabox_save');

