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
 * Allow duplicate comments (for ratings without comment message)
 */
add_filter('duplicate_comment_id', '__return_false');

/*
 * Disable translations for comments
 */
add_action( 'plugins_loaded', function () {
	remove_filter('comments_clauses', [$GLOBALS['sitepress'], 'comments_clauses'], 10);
} );

/*
 * Add custom columns to the comments table
 */
add_filter('manage_edit-comments_columns', function ($columns){
	return [
		'cb' => '<input type="checkbox" />',
		'ig_response' => 'Feedback zu',
		'ig_rating' => 'Bewertung',
		'ig_content' => 'Kommentare',
	];
});

/*
 * Define the custom rating column by checking the rating meta and fill the stars accordingly
 */
add_action('manage_comments_custom_column', function ($column, $comment_id) {
	switch ($column) {
		case 'ig_response':
			$type = get_comment_meta($comment_id, 'type', true);
			switch ($type) {
				case 'post':
					$post = get_post(get_comment($comment_id)->comment_post_ID);
					echo $post->post_title . '<br>
						<a href="' . get_permalink($post) . '">Diese Seite ansehen</a><br>
						<a href="' . get_edit_post_link($post->ID) . '">Diese Seite bearbeiten</a>';
					break;
				case 'categories':
					echo 'Verfügbare Kategorien';
					break;
				case 'cities':
					echo 'Verfügbare Städte';
					break;
				case 'events':
					echo 'Verfügbare Veranstaltungen';
					break;
				case 'extra':
					$alias = get_comment_meta($comment_id, 'alias', true);
					if (class_exists('IntegreatSettingsPlugin') && $extra = IntegreatExtra::get_extra_by_alias($alias)) {
						echo 'Extra "' . $extra->name . '"';
					} else {
						echo 'Extra "' . $alias . '"';
					}
					break;
				case 'extras':
					echo 'Verfügbare Extras';
					break;
				case 'search':
					echo 'Suchergebnisse zu "' . get_comment_meta($comment_id, 'query', true) . '"';
					break;
			}
			break;
		case 'ig_rating':
			$rating_up = get_comment_meta($comment_id, 'rating_up', true);
			$rating_down = get_comment_meta($comment_id, 'rating_down', true);
			if ($rating_up > 0) {
				echo '<span class="dashicons dashicons-thumbs-up"></span> ' . $rating_up . '<br>';
			}
			if ($rating_down > 0) {
				echo '<span class="dashicons dashicons-thumbs-down"></span> ' . $rating_down . '<br>';
			}
			break;
		case 'ig_content':
			$content = json_decode(get_comment_meta($comment_id, 'content', true));
			if (count($content) > 0) {
				echo '<a href="#" class="ig-feedback-spoiler-show" data-ig-feedback-comment-id="' . $comment_id . '">Ausklappen</a>';
				echo '<a href="#" class="ig-feedback-spoiler-hide" data-ig-feedback-comment-id="' . $comment_id . '">Einklappen</a>';
				echo '<div class="ig-feedback-spoiler-content" data-ig-feedback-comment-id="' . $comment_id . '">';
				foreach ($content as $item) {
					echo '<hr><i>' . $item->date . '</i>';
					if ($item->language !== 'de') {
						echo ' - <i>Kommentar zu übersetztem Inhalt (' . $item->language . ')</i>';
					}
					echo '<br>';
					if ($item->category) {
						echo '<b>' . $item->category . ':</b> ';
					}
					echo $item->text;
				}
				echo '</div>';
			}
			break;
	}
}, 10, 2 );

/*
 * Include custom javascript and css
 */
add_action( 'admin_enqueue_scripts', function () {
	wp_enqueue_script('ig_feedback_script', plugin_dir_url(__FILE__) . 'script.js', ['jquery']);
	wp_enqueue_style('ig_feedback_stylesheet', plugin_dir_url(__FILE__) . 'stylesheet.css');
});