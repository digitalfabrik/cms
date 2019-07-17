<?php

abstract class APIv3_Feedback_Abstract extends APIv3_Base_Abstract {

	public function __construct() {
		parent::__construct();
		$this->method = 'POST';
		$this->callback = 'put_feedback';
		$this->args = [
			'comment' => [
				'validate_callback' => function($comment) {
					return is_string($comment);
				}
			],
			'category' => [
				'validate_callback' => function($category) {
					return is_string($category);
				}
			],
			'rating' => [
				'validate_callback' => function($rating) {
					return in_array($rating, ['up', 'down']);
				}
			],
		];
	}

	public function put_feedback(WP_REST_Request $request) {
		$text = $request->get_param('comment');
		$rating = $request->get_param('rating');
		/*
		 * Sanitize text
		 */
		if ($text !== null) {
			$text = htmlentities($text);
		}
		/*
		 * Throw an error if both comment and rating are missing
		 */
		if ($text === null && $rating === null) {
			return new WP_Error('rest_missing_param', 'Either the comment or the rating parameter is required', ['status' => 400]);
		}
		/*
		 * If type is post, get the correct post id
		 */
		if (static::TYPE === 'post') {
			$id = $request->get_param('id');
			$permalink = $request->get_param('permalink');
			/*
			 * Throw an error if both id and permalink are missing
			 */
			if ($id === null && $permalink === null) {
				return new WP_Error('rest_missing_param', 'Either the id or the permalink parameter is required', ['status' => 400]);
			}
			if ($id === null) {
				$id = url_to_postid(ltrim($permalink, '/'));
			}
			$language = apply_filters('wpml_post_language_details', null, $id)['language_code'];
			$id = apply_filters('wpml_object_id', $id, 'any', true, 'de');
		} else {
			$language = apply_filters('wpml_current_language', null);
			$id = null;
		}
		/*
		 * Generate query dependent on meta keys
		 */
		if (defined('static::META')) {
			$query = [
				'post_id' => $id,
				'meta_query' => [
					'relation' => 'AND',
					[
						'key' => 'type',
						'value' => static::TYPE
					],
					[
						'key' => static::META,
						'value' => $request->get_param(static::META)
					]
				]
			];
		} else {
			$query = [
				'post_id' => $id,
				'meta_key' => 'type',
				'meta_value' => static::TYPE
			];
		}
		/*
		 * Fetch all comments to the given post incl. the comment meta
		 */
		$comments = (new WP_Comment_Query($query))->comments;
		$num_comments = count($comments);
		if ($num_comments === 0) {
			/*
			 * If there are no comments for the given post, we add a new comment incl. comment meta
			 */
			$comment_id = wp_insert_comment([
				'comment_post_ID' => $id,
				'comment_content' => '',
				'comment_author_IP' => '',
				'comment_approved' => 0,
			]);
			/*
			 * Return error if something went wrong
			 */
			if (!$comment_id) {
				return new WP_Error('internal_server_error', 'Feedback could not be submitted.', ['status' => 500]);
			}
			add_comment_meta($comment_id, 'type', static::TYPE);
			$content = [];
			$ratings = [];
			if ($text !== null) {
				$content[] = [
					'date' => date('d.m.Y H:i:s'),
					'language' => $language,
					'category' => $request->get_param('category'),
					'text' => $text,
					'rating' => $rating
				];
			} else {
				/*
				 * If text + rating are sent both, we don't count the rating but use it only for the comment.
				 */
				$ratings[] = [
					'date' => date('d.m.Y H:i:s'),
					'language' => $language,
					'rating' => $rating
				];
			}
			add_comment_meta($comment_id, 'content', json_encode($content, JSON_UNESCAPED_UNICODE));
			add_comment_meta($comment_id, 'ratings', json_encode($ratings, JSON_UNESCAPED_UNICODE));
			if (defined('static::META')) {
				add_comment_meta($comment_id, static::META, $request->get_param(static::META));
			}
		} elseif ($num_comments === 1) {
			/*
			 * If there exists exactly one comment for the given post, we update the comment meta
			 */
			if ($text !== null) {
				$content = json_decode(get_comment_meta($comments[0]->comment_ID, 'content', true));
				$content[] = [
					'date' => date('d.m.Y H:i:s'),
					'language' => $language,
					'category' => $request->get_param('category'),
					'text' => $text,
					'rating' => $rating
				];
				update_comment_meta($comments[0]->comment_ID, 'content', json_encode($content, JSON_UNESCAPED_UNICODE));
				/*
				 * If comment was approved before, unapprove it to mark it red in admin view
				 */
				wp_update_comment([
					'comment_ID' => $comments[0]->comment_ID,
					'comment_approved' => 0,
				]);
			} else {
				/*
				 * If text + rating are sent both, we don't count the rating but use it only for the comment.
				 */
				$ratings = json_decode(get_comment_meta($comments[0]->comment_ID, 'ratings', true));
				$ratings[] = [
					'date' => date('d.m.Y H:i:s'),
					'language' => $language,
					'rating' => $rating
				];
				update_comment_meta($comments[0]->comment_ID, 'ratings', json_encode($ratings, JSON_UNESCAPED_UNICODE));
			}
		} else {
			/*
			 * Throw an error if more than one comment exists
			 */
			return new WP_Error('rest_invalid_post', 'An internal error occured. Please contact the server administrator.', ['status' => 500]);
		}
		/*
		 * Return success message if no error was returned until now
		 */
		return [
			'code' =>'rest_comment_created',
			'message' => 'Feedback successfully submitted',
			'data' => [
				'status' => 201,
			]
		];
	}

}
