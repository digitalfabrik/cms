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

add_action('plugins_loaded', function() {
	load_plugin_textdomain('ig-revisions', false, basename(dirname(__FILE__ )));
	// if user cannot publish pages, change the button text
	add_filter('gettext', function ($translation, $text) {
		if (!current_user_can('publish_pages')) {
			if ($text == 'Update') {
				return __('Submit for Review');
			}
		}
		return $translation;
	}, 10, 2);
});

function ig_revisions_notice() {
	if (isset($_GET['post'])) {
		$revision_id = get_post_meta($_GET['post'], 'ig_revision_id', true);
		if (is_numeric($revision_id) && $revision_id >= 0) {
			$revision = wp_get_post_revision($revision_id);
			if (current_user_can('publish_pages')) {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>' . __('Note: The current revision is not published, but the revision from', 'ig-revisions') . ' ' . $revision->post_date . '.<br>' . sprintf(__('You can restore the revision from %s on <a href=%s>this page</a>', 'ig-revisions'), $revision->post_date, get_edit_post_link($revision_id)) . '.<br>' . __('You can publish this revision by publishing this page', 'ig-revisions') . '.</strong></p></div>';
			} else {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>' . __('Note: The current revision is not published, but the revision from', 'ig-revisions') . ' ' . $revision->post_date . '.<br>' . __('Only a manager can publish this revision', 'ig-revisions') . '.</strong></p></div>';
			}
		}
	}
}
add_action('admin_notices', 'ig_revisions_notice');

function ig_revisions_page_update($post_id, $new_post) {
	if (current_user_can('publish_pages')) {
		// if user can publish pages, change ig revision id to -1 (always current version)
		update_post_meta($post_id, 'ig_revision_id', '-1');
	} else {
		// if user cannot publish pages, change ig revision id to previous state
		$post = get_post($post_id);
		// if title or content has changed, a new revision was created
		if ($post->post_title !== $new_post['post_title'] || $post->post_content !== $new_post['post_content']) {
			$revision_id = get_post_meta($post_id, 'ig_revision_id', true);
			// only update revisions id if it is not already set to a previous revision
			if(!is_numeric($revision_id) || $revision_id === '-1') {
				$revisions = wp_get_post_revisions($post_id);
				$current_revision = reset($revisions); // get first element of array (keys do not begin with 0)
				update_post_meta($post_id, 'ig_revision_id', $current_revision->ID);
			}
		}
	}
}
add_action('pre_post_update', 'ig_revisions_page_update', 10, 2);

function update_post_with_revision($post) {
	$revision_id = get_post_meta($post['id'], 'ig_revision_id', true);
	if(is_numeric($revision_id) && $revision_id >= 0) {
		$revision_post = wp_get_post_revision($revision_id);
		$output_post = [
			'title' => $revision_post->post_title,
			'excerpt' => $revision_post->post_excerpt ?: wp_trim_words($revision_post->post_content),
			'content' => wpautop($revision_post->post_content),
		];
	} else {
		$output_post = [];
	}
	return array_merge($post, $output_post);
}
add_filter('wp_api_extensions_output_post', 'update_post_with_revision');

/**
 * Append revision status for tree view plugin. Hooks into
 * custom Integreat hook.
 *
 * @param array $status array of status labels
 * @param integer $post_id ID of the post item
 * @return array
 */
function ig_revisions_tree_view_status($status, $post_id) {
	$revision_id = get_post_meta($post_id, 'ig_revision_id', true);
	if (is_numeric($revision_id) && $revision_id >= 0) {
		$status[] = __('Revision', 'ig-revisions');
	}
	return $status;
}
add_filter('ig-cms-tree-view-status',  'ig_revisions_tree_view_status', 10, 2);
