<?php
/**
 * Plugin Name: Integreat Feedback
 * Description: Adds a custom post type for feedback
 * Version: 1.0
 * Author: Integreat Team / Timo Ludwig
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

/*
 * Add a custom rating column in the comments table behind the comment
 */
add_filter('manage_edit-comments_columns', function ($columns){
	return array_slice($columns, 0, 3, true) + ['ig_comment_rating' => 'Bewertung'] + array_slice($columns, 3, null, true);
});

/*
 * Define the custom rating column by checking the rating meta and fill the stars accordingly
 */
add_action('manage_comments_custom_column', function ($column, $comment_id) {
	if ($column == 'ig_comment_rating') {
		$rating = get_comment_meta($comment_id, 'rating', true);
		if ($rating !== '' && $rating !== null) {
			wp_star_rating(['rating' => $rating]);
		}
	}
}, 10, 2 );

/*
 * Adjust the size of the custom rating column
 */
add_action('admin_head', function () {
	echo '<style>#ig_comment_rating {width: 120px;}</style>';
});

/*
 * Add a filter to show information about comments which have no actual post, e.g. extras and searches
 */
add_filter('ig_feedback_response', function ($comment) {
	if ($comment->comment_post_ID) {
		return;
	}
	$extra = get_comment_meta($comment->comment_ID, 'extra', true);
	$query = get_comment_meta($comment->comment_ID, 'query', true);
	$url = get_comment_meta($comment->comment_ID, 'url', true);
	if ($extra !== '' && $extra !== null) {
		echo esc_html($extra) . '<div class="response-links"><a href="' . $url . '" target="_BLANK" class="comments-view-item-link">Extra ansehen</a></div>';
	} elseif ($query !== '' && $query !== null) {
		echo 'Suche nach "' . esc_html($query) . '"' . '<div class="response-links"><a href="' . $url . '" target="_BLANK" class="comments-view-item-link">Suchergebnisse ansehen</a></div>';
	}
});