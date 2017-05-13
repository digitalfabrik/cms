<?php
/**
 * Plugin Name: Disable Permalink Edit
 * Description: Disallow editing of permalinks
 * Version: 1.0
 * Author: Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

add_filter('get_sample_permalink_html', 'ig_disable_permalink_edit_button', 10,4);

function ig_disable_permalink_edit_button($return, $id, $new_title, $new_slug){
	return preg_replace(
		'/<span id="edit-slug-buttons">.*<\/span>|<span id=\'view-post-btn\'>.*<\/span>/i', 
		'', 
		$return
	);
}

add_action('post_updated', 'ig_update_permalink', 10, 3);
function ig_update_permalink($postId, $after, $before) {
	if ($after->post_title != $before->post_title) {
		$after->post_name = ''; // Reset permalink
		wp_update_post($after);
	}
}
