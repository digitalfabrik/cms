<?php

/*
 * Retrieve all posts (or only the changed posts if the hashes of the stored posts are being sent as post-value)
 */
abstract class APIv3_Posts_Abstract extends APIv3_Base_Abstract {

	protected $current_language;

	public function __construct() {
		add_filter('excerpt_more', function($link) { return ''; });
		global $sitepress;
		$this->current_language = $sitepress->get_current_language();
	}

	public function register_routes($namespace) {
		parent::register_route($namespace, static::ROUTE, 'get_posts', 'GET,POST');
	}

	public function get_posts(WP_REST_Request $request) {
		$query = new WP_Query([
			'post_type' => static::POST_TYPE,
			'post_status' => 'publish',
			'orderby' => 'menu_order post_title',
			'order'   => 'ASC',
			'posts_per_page' => -1,
		]);
		$posts = [];
		foreach ($query->posts as $post) {
			$posts[] = $this->prepare($post);
		}
		return $this->get_changed_posts($request, $posts);
	}

	protected function prepare(WP_Post $post) {
		$GLOBALS['post'] = $post; // define global $post to prevent wordpress notices caused by events-manager
		$post = apply_filters('wp_api_extensions_pre_post', $post);
		setup_postdata($post);
		$content = $this->prepare_content($post);
		$output_post = [
			'id' => $post->ID,
			'url' => get_permalink($post->ID),
			'title' => $post->post_title,
			'modified_gmt' => $post->post_modified_gmt,
			'excerpt' => $this->prepare_excerpt($post),
			'content' => $content,
			'parent' => $post->post_parent,
			'order' => $post->menu_order,
			'available_languages' => $this->get_available_languages($post),
			'thumbnail' => has_post_thumbnail($post->ID) ? wp_get_attachment_image_src(get_post_thumbnail_id($post->ID))[0] : null,
		];
		$output_post = apply_filters('wp_api_extensions_output_post', $output_post);
		$output_post['hash'] = md5(json_encode($post));
		return $output_post;
	}

	private function prepare_content(WP_Post $post) {
		$children = get_pages( [ 'child_of' => $post->ID ] );
		if ($post->post_content === '' && count( $children ) === 0) {
			$post->post_content = 'empty';
		}
		return wpautop($post->post_content);
	}

	private function prepare_excerpt(WP_Post $post) {
		$excerpt = $post->post_excerpt ?: apply_filters('the_excerpt', apply_filters('get_the_excerpt', $post->post_excerpt));
		$excerpt = trim(str_replace(['</p>', "\r\n", "\n", "\r", '<p>'], '', $excerpt));
		return $excerpt;
	}

	private function get_available_languages(WP_Post $post) {
		global $sitepress;
		$languages = apply_filters('wpml_active_languages', null, '');
		$available_languages = [];
		foreach ($languages as $language) {
			if ($language['code'] == $this->current_language) {
				continue;
			}
			$id = apply_filters('wpml_object_id', $post->ID, $post->post_type, false, $language['code']);
			if ($id != null) {
				$sitepress->switch_lang($language['code'], true);
				$available_languages[$language['code']] = [
					'id' => $id,
					'url' => get_permalink($id),
				];
			}
		}
		$sitepress->switch_lang($this->current_language, true);
		return $available_languages;
	}

	protected function get_changed_posts(WP_REST_Request $request, Array $all_posts) {
		if ($request->get_method() == 'POST') {
			/*
			 * Get the post data of the request and throw an error if the data is empty or contains no valid json.
			 */
			if (!$local_posts = $request->get_json_params()) {
				return new WP_Error('rest_no_data', 'Keine JSON-Daten gefunden', ['status' => 400]);
			}
			/*
			 * Validate the json by checking whether its elements meet the following format:
			 *     Array => (
			 *         'id' => Integer,
			 *         'hash' => String
			 *     )
			 * If the check is not successful, try to bring the posts into this format and check again.
			 * If the validation still fails, throw an error.
			 */
			if (!$local_posts = $this->sanitize_posts($local_posts)) {
				return new WP_Error('rest_bad_data', 'Die JSON-Daten entsprechen nicht dem erwarteten Format', ['status' => 400]);
			}
			/*
			 * Filter all posts by the keys 'id' and 'hash' and serialize them to enable string-comparison of its elements.
			 */
			$local_posts_serialized = $this->serialize_posts($local_posts);
			$all_posts_serialized = $this->serialize_posts($this->filter_posts($all_posts, ['id', 'hash']));
			/*
			 * Now we can determine which posts have been changed by taking all elements from $all_posts and remove the $local_posts.
			 * As we want to return all information of the changed posts (and not only id and hash),
			 * we intersect the keys of all results with the keys of the changed elements.
			 * As the keys of the resulting array are not relevant, we remove them with array_values().
			 */
			$changed_posts_keys = array_flip(array_keys(array_diff($all_posts_serialized, $local_posts_serialized)));
			$changed_posts = array_values(array_intersect_key($all_posts, $changed_posts_keys));
			/*
			 * To identify the deleted posts, we consider only the ids of all posts, serialize them and return all $local_posts without $all_posts.
			 * As the keys of the resulting array are not relevant, we remove them with array_values().
			 * In the end, we have to deserialize the deleted posts to restore the json logic.
			 */
			$local_posts_ids = $this->serialize_posts($this->filter_posts($local_posts, ['id']));
			$all_posts_ids = $this->serialize_posts($this->filter_posts($all_posts, ['id']));
			$deleted_posts = $this->deserialize_posts(array_values(array_diff($local_posts_ids, $all_posts_ids)));
			/*
			 * Now we can return the deleted and changed posts:
			 */
			$all_posts = [
				'deleted' => $deleted_posts,
				'changed' => $changed_posts,
			];
		}
		return $all_posts;
	}

	/*
	 * Validate the $posts by checking whether its elements meet the following format:
	 *     Array => (
	 *         'id' => Integer,
	 *         'hash' => String
	 *     )
	 */
	private function validate_posts($posts) {
		foreach ($posts as $post) {
			if (!is_array($post) || array_keys($post) !== [ 'id', 'hash' ] || !is_int($post['id']) || !is_string($post['hash'])) {
				return false;
			}
		}
		return true;
	}

	/*
	 * Sort the keys of every $post contained in $posts in reverse order and filter it by the $keys.
	 */
	private function sanitize_posts($posts) {
		if (!$this->validate_posts($posts)) {
			$posts_sanitized = [];
			foreach ($posts as $post) {
				if (!is_array($post) || !array_key_exists('id', $post) || !array_key_exists('hash', $post)) {
					return false;
				} else {
					$posts_sanitized[] = [
						'id' => (int) $post['id'],
						'hash' => (string) $post['hash']
					];
				}
			}
			if (!$this->validate_posts($posts_sanitized)) {
				return false;
			} else {
				$posts = $posts_sanitized;
			}
		}
		return $posts;
	}

	/*
	 * Convert every $post-array contained in $posts to a string
	 */
	private function serialize_posts($posts) {
		return array_map(function ($post) {
			return json_encode($post);
		}, $posts);
	}

	/*
	 * Convert every $post-string contained in $posts to an array
	 */
	private function deserialize_posts($posts) {
		return array_map(function ($post) {
			return json_decode($post);
		}, $posts);
	}

	/*
	 * Filter all posts by removing all keys except $keys from every $post contained in $posts.
	 */
	private function filter_posts($posts, $keys) {
		return array_map(function ($post) use ($keys) {
			return array_intersect_key($post, array_flip($keys));
		}, $posts);
	}

}