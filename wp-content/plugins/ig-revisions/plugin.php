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

 /**
  * On the edit post page, change the "Save" / "Publish" button into
  * "Submit for Review" if the user does not have the permission to
  * publish pages.
  */
add_action('plugins_loaded', function() {
	load_plugin_textdomain( 'ig-revisions', false, basename( dirname(__FILE__ ) ) );
	// if user cannot publish pages, change the button text
	add_filter( 'gettext', function ($translation, $text) {
		if (!current_user_can('publish_pages') ) {
			if ( $text == 'Update') {
				return __( 'Submit for Review' );
			}
		}
		return $translation;
	}, 10, 2);
});

/**
 * Show notice on post edit screen. This depends on the users
 * permissions.
 */
function ig_revisions_notice( ) {
	if ( isset( $_GET['post'] ) ) {
		$revision_id = get_post_meta( $_GET['post'], 'ig_revision_id', true );
		if ( is_numeric( $revision_id ) && $revision_id >= 0 ) {
			$revision = wp_get_post_revision($revision_id);
			if ( current_user_can( 'publish_pages' ) ) {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>' .
				__( 'Note: The current revision is not published, but the revision from', 'ig-revisions' ) .
				' ' . $revision->post_date . '.<br>' . sprintf(
					__( 'You can restore the revision from %s on <a href=%s>this page</a>', 'ig-revisions' ),
					$revision->post_date, get_edit_post_link( $revision_id )
				) .
				'.<br>' . __( 'You can publish this revision by publishing this page', 'ig-revisions' ) .
				'.</strong></p></div>';
			} else {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>' .
				__( 'Note: The current revision is not published, but the revision from', 'ig-revisions' ) . ' ' .
				$revision->post_date . '.<br>' . __( 'Only a manager can publish this revision', 'ig-revisions' ) .
				'.</strong></p></div>';
			}
		}
	}
}
add_action( 'admin_notices', 'ig_revisions_notice' );

/**
 * When a post is saved, update the revision data. If the
 * user has admin rights, the current version should be
 * published. If the user has reduced privileges, then a
 * revision should be automatically set.
 *
 * @param integer $post_id
 * @param array $new_post data of new post submitted by author
 */
function ig_revisions_page_update( $post_id, $new_post ) {
	$permission = apply_filters( 'ig_allow_publishing', False );
	if ( current_user_can( 'publish_pages' ) OR $permission ) {
		// if user can publish pages, change ig revision id to -1 (always current version)
		update_post_meta( $post_id, 'ig_revision_id', '-1' );
	} else {
		// if user cannot publish pages, change ig revision id to previous state
		$post = get_post( $post_id );
		// if title or content has changed, a new revision was created
		if ( $post->post_title !== $new_post['post_title'] || $post->post_content !== $new_post['post_content'] ) {
			$revision_id = get_post_meta( $post_id, 'ig_revision_id', true );
			// only update revisions id if it is not already set to a previous revision
			if( !is_numeric( $revision_id ) || $revision_id === '-1' ) {
				$revisions = wp_get_post_revisions( $post_id );
				// get first element of array (keys do not begin with 0)
				$current_revision = current( $revisions );
				while ( wp_is_post_autosave( $current_revision ) ) {
					$current_revision = next( $revisions );
				}
				update_post_meta( $post_id, 'ig_revision_id', $current_revision->ID );
			}
		}
	}
}
add_action( 'pre_post_update', 'ig_revisions_page_update', 10, 2 );

/**
 * Modify a post when it is delivered to the user. If a
 * revision is set for a post, overwrite title, excerpt
 * and content with data from the older, user defined
 * version. This function should be hooked into the REST
 * API.
 *
 * @param array $post array containing post data
 * @return array updated post data
 */
function update_post_with_revision( $post ) {
	$revision_id = get_post_meta( $post['id'], 'ig_revision_id', true );
	if(is_numeric($revision_id) && $revision_id >= 0) {
		$revision_post = wp_get_post_revision($revision_id);
		$output_post = [
			'title' => $revision_post->post_title,
			'excerpt' => $revision_post->post_excerpt ?: wp_trim_words( $revision_post->post_content ),
			'content' => wpautop( $revision_post->post_content ),
		];
	} else {
		$output_post = [];
	}
	return array_merge( $post, $output_post );
}
add_filter( 'wp_api_extensions_output_post', 'update_post_with_revision' );

/**
 * Append revision status for tree view plugin. Hooks into
 * custom Integreat hook.
 *
 * @param array $status array of status labels
 * @param integer $post_id ID of the post item
 * @return array
 */
function ig_revisions_tree_view_status( $status, $post_id ) {
	$revision_id = get_post_meta( $post_id, 'ig_revision_id', true );
	if ( is_numeric( $revision_id ) && $revision_id >= 0 ) {
		$status[] = __('Revision', 'ig-revisions');
	}
	return $status;
}
add_filter( 'ig-cms-tree-view-status',  'ig_revisions_tree_view_status', 10, 2 );

/**
 * Appends a custom status in the tree view plugin for posts with an empty content. Hooks into
 * custom Integreat hook.
 *
 * @param array $status array of status labels
 * @param integer $post_id ID of the post item
 * @return array
 */
function ig_tree_view_empty_content( $status, $post_id ) {
	
	if ( get_post($post_id)->post_content == '' && !get_post_meta( $post_id, 'ig-attach-content-page', true) && !count( get_posts( array('post_parent' => $post_id ) ) ) ){
		$status[] = __('Empty Page', 'ig-empty-pages');
	}
	return $status;
}
add_filter( 'ig-cms-tree-view-status',  'ig_tree_view_empty_content', 10, 2 );