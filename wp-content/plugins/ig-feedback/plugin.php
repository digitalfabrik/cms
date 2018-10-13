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
			$ratings = array_filter(json_decode(get_comment_meta($comment_id, 'ratings', true)), function ($rating) {
				/*
				 * Skip rating if the language filter is on and the language does not match
				 */
				if (isset($_GET['ig-feedback-language']) && $_GET['ig-feedback-language'] !== 'all' && $_GET['ig-feedback-language'] !== $rating->language) {
					return false;
				}
				/*
				 * Skip rating if the date filter is on and the date does not match
				 */
				if (isset($_GET['ig-feedback-month']) && $_GET['ig-feedback-month'] !== 'all' && date('m.Y', strtotime('-' . $_GET['ig-feedback-month'] . ' month')) !== date('m.Y', strtotime($rating->date))) {
					return false;
				}
				/*
				 * Else remain rating in ratings array
				 */
				return true;
			});
			$ratings_up = count(array_filter($ratings, function ($rating) {
				return $rating->rating === 'up';
			}));
			if ($ratings_up > 0) {
				echo '<span class="dashicons dashicons-thumbs-up"></span> ' . $ratings_up . '<br>';
			}
			$ratings_down = count(array_filter($ratings, function ($rating) {
				return $rating->rating === 'down';
			}));
			if ($ratings_down > 0) {
				echo '<span class="dashicons dashicons-thumbs-down"></span> ' . $ratings_down . '<br>';
			}
			break;
		case 'ig_content':
			$content = array_filter(json_decode(get_comment_meta($comment_id, 'content', true)), function ($item) {
				/*
				 * Skip comment if the category filter is on and the category does not match
				 */
				if (isset($_GET['ig-feedback-category']) && $_GET['ig-feedback-category'] !== 'all' && $_GET['ig-feedback-category'] !== $item->category) {
					return false;
				}
				/*
				 * Skip comment if the type filter is on and the category does not match
				 */
				if (isset($_GET['comment_type']) && $_GET['comment_type'] !== '' && $_GET['comment_type'] !== $item->rating) {
					return false;
				}
				/*
				 * Skip comment if the language filter is on and the language does not match
				 */
				if (isset($_GET['ig-feedback-language']) && $_GET['ig-feedback-language'] !== 'all' && $_GET['ig-feedback-language'] !== $item->language) {
					return false;
				}
				/*
				 * Skip comment if the date filter is on and the date does not match
				 */
				if (isset($_GET['ig-feedback-month']) && $_GET['ig-feedback-month'] !== 'all' && date('m.Y', strtotime('-' . $_GET['ig-feedback-month'] . ' month')) !== date('m.Y', strtotime($item->date))) {
					return false;
				}
				/*
				 * Else remain comment in content array
				 */
				return true;
			});
			if (count($content) > 0) {
				echo '<a href="#" class="ig-feedback-spoiler-show" data-ig-feedback-comment-id="' . $comment_id . '"><span class="label gray"><span class="dashicons dashicons-arrow-down-alt2"></span> Ausklappen</span></a>';
				echo '<a href="#" class="ig-feedback-spoiler-hide" data-ig-feedback-comment-id="' . $comment_id . '"><span class="label gray"><span class="dashicons dashicons-arrow-up-alt2"></span>Einklappen</span></a>';
				echo '<div class="ig-feedback-spoiler-content" data-ig-feedback-comment-id="' . $comment_id . '">';
				foreach ($content as $item) {
					echo '<hr><span class="label blue"><span class="dashicons dashicons-clock"></span> ' . $item->date . '</span>';
					if (isset($item->rating)) {
						if ($item->rating === 'up') {
							echo '<span class="label green"><span class="dashicons dashicons-thumbs-up"></span> Lob</span>';
							if ($item->category) {
								echo '<span class="label green"><span class="dashicons dashicons-info"></span> ' . $item->category . '</span>';
							}
						} elseif ($item->rating === 'down') {
							echo '<span class="label red"><span class="dashicons dashicons-thumbs-down"></span> Kritik</span>';
							if ($item->category) {
								echo '<span class="label orange"><span class="dashicons dashicons-warning"></span> ' . $item->category . '</span>';
							}
						}
					} else {
						if ($item->category) {
							echo '<span class="label blue"><span class="dashicons dashicons-info"></span> ' . $item->category . '</span>';
						}
					}
					if ($item->language !== 'de') {
						echo '<span class="label yellow"><span class="dashicons dashicons-translation"></span> Übersetzung ' . apply_filters('wpml_translated_language_name', null, $item->language, 'de') . '</span>';
					}
					echo '<br><br><span class="dashicons dashicons-format-quote"></span> <span class="ig-comment">' . $item->text . '</span><br><br>';
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

/*
 * Custom category filter
 */

add_filter('admin_comment_types_dropdown', function ($comment_types) {
	return [
		'up' => 'Lob',
		'down' => 'Kritik'
	];
});
add_action('restrict_manage_comments', function() {
	/*
	 * SQL Query to determine which categories and which languages are existent in the comments
	 */
	global $wpdb;
	$comment_objects = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->prefix}commentmeta WHERE meta_key = 'content'", OBJECT );
	$categories = [];
	$languages = [];
	foreach ($comment_objects as $comment_object) {
		foreach (json_decode($comment_object->meta_value) as $comment) {
			if (isset($comment->category) && !in_array($comment->category, $categories)) {
				$categories[] = $comment->category;
			}
			if (isset($comment->language) && !in_array($comment->language, $languages)) {
				$languages[] = $comment->language;
			}
		}
	}
	/*
	 * Feedback Category Filter
	 */
	echo '<select name="ig-feedback-category"><option value="all">Alle Kategorien</option>';
	foreach ($categories as $category) {
		echo '<option value="' . $category . '"' . (isset($_GET['ig-feedback-category']) && $_GET['ig-feedback-category'] === $category ? ' selected' : '') . '>' . $category . '</option>';
	}
	echo '</select>';
	/*
	 * Feedback Language Filter
	 */
	echo '<select name="ig-feedback-language"><option value="all">Alle Sprachen</option>';
	foreach ($languages as $language) {
		echo '<option value="' . $language . '"' . (isset($_GET['ig-feedback-language']) && $_GET['ig-feedback-language'] === $language ? ' selected' : '') . '>' . apply_filters('wpml_translated_language_name', null, $language, 'de') . '</option>';
	}
	echo '</select>';
	/*
	 * Feedback Date Filter
	 */
	echo '<select name="ig-feedback-month"><option value="all">Kompletter Zeitraum</option>';
	for ($month = 0; $month < 12; $month++) {
		echo '<option value="' . $month . '"' . (isset($_GET['ig-feedback-month']) && $_GET['ig-feedback-month'] === strval($month) ? ' selected' : '') . '>' . __(date('F', strtotime('-' . $month . ' month'))) . date(' Y', strtotime('-' . $month . ' month')) . '</option>';
	}
	echo '</select>';
});
add_filter('pre_get_comments', function ($query) {
	global $pagenow;
	if (is_admin() && $pagenow=='edit-comments.php'){
		$meta_value_content = '';
		if (isset( $_GET['ig-feedback-month'] ) && $_GET['ig-feedback-month'] !== 'all') {
			$regex_day = '(0[1-9]|[1-2][0-9]|3[0-1]).';
			$regex_time = ' ([0-1][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]';
			$meta_value_content .= '{"date":"' . $regex_day . date('m.Y', strtotime('-' . $_GET['ig-feedback-month'] . ' month')) . $regex_time . '",';
		}
		if (isset( $_GET['ig-feedback-language'] ) && $_GET['ig-feedback-language'] !== 'all') {
			$meta_value_content .= '[^}]*"language":"' . $_GET['ig-feedback-language'] . '",';
		}
		/*
		 * Ratings have the same structure for date and language attributes, hence we can just copy the regex
		 */
		$meta_value_ratings = $meta_value_content;
		if (isset( $_GET['ig-feedback-category'] ) && $_GET['ig-feedback-category'] !== 'all') {
			$meta_value_content .= '[^}]*"category":"' . $_GET['ig-feedback-category'] . '","text":"';
			/*
			 * If category filter is on, we don't want any rating results.
			 */
			$meta_value_ratings = 'this string does not exist';
		}
		if (isset( $_GET['comment_type'] ) && $_GET['comment_type'] !== '') {
			$meta_value_content .= '[^}]*"rating":"' . $_GET['comment_type'] . '"}';
			/*
			 * If type filter is on, we don't want any rating results.
			 */
			$meta_value_ratings = 'this string does not exist';
		}
		$query->query_vars['meta_query'] = [
			'relation' => 'OR',
			[
				'key' => 'content',
				'value' => $meta_value_content,
				'compare' => 'REGEXP'
			],
			[
				'key' => 'ratings',
				'value' => $meta_value_ratings,
				'compare' => 'REGEXP'
			]
		];
		/*
		 * We have to remove the type filter, because we misuse the comment_type filter for comment categories
		 */
		$query->query_vars['type'] = '';
	}
});
