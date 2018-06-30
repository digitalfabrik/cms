<?php
/**
 * Plugin Name: Integreat Page Revisions
 * Description: Set a specific page revision for delivery via API
 * Version: 1.0
 * Author: Integreat Team / Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 * Text Domain: ig-revisions
 * Domain Path: /
 */

function ig_revisions_metabox( $post ) {
	$children = wp_get_post_revisions( $_GET['post'] );
	$revision_id = get_post_meta( $_GET['post'], 'ig_revision_id', true );
	$options = "<option value='-1'" . (!$revision_id ? " selected" : "") . ">". __('Always publish most recent revision', 'ig-revisions') ."</option>";
	
	foreach($children as $child) {
		$options .= "<option value='" . $child->ID . "'" . ($child->ID==$revision_id ? " selected" : "" ) . ">" . $child->post_date . "</option>";
	}
	add_meta_box(
		'ig_revisions',
		__('Published revision', 'ig-revisions'),
		'ig_revisions_metabox_html',
		'page',
		'advanced',
		'default',
		$options
	);
}
add_action( 'add_meta_boxes', 'ig_revisions_metabox' );
add_action( 'plugins_loaded', function() {
	load_plugin_textdomain('ig-revisions', false, basename(dirname(__FILE__)) . '/lang/');
});

function ig_revisions_metabox_html ( $post, $callback_args ) {
?>
		<label><?php __( 'Published revision', 'ig-revisions' ) ?></label>
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

function update_post_with_revision( $post ) {
	$revision_id = get_post_meta( $post['id'], 'ig_revision_id', true );
	if(is_numeric($revision_id) && $revision_id >= 0) {
		$revision_post = wp_get_post_revision( $revision_id );
		$output_post = [
			'title' => $revision_post->post_title,
			'excerpt' => $revision_post->post_excerpt ?: wp_trim_words($revision_post->post_content),
			'content' => wpautop($revision_post->post_content),
		];
	} else {
		$output_post = [];
	}
	return array_merge( $post, $output_post );
}
add_filter( 'wp_api_extensions_output_post', 'update_post_with_revision', 10, 1 );