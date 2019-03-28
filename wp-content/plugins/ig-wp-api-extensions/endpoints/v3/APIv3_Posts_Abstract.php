<?php

/*
 * Retrieve all posts (or only the changed posts if the hashes of the stored posts are being sent as post-value)
 */
abstract class APIv3_Posts_Abstract extends APIv3_Base_Abstract {

	protected $current_language;

	public function __construct() {
		parent::__construct();
		$this->method = 'GET, POST';
		add_filter('excerpt_more', function($link) { return ''; });
		global $sitepress;
		$this->current_language = $GLOBALS['sitepress']->get_current_language();
	}

	public function get_posts(WP_REST_Request $request) {
		if (is_post_type_hierarchical(static::POST_TYPE)) {
			$posts = $this->get_posts_recursive();
		} else {
			$posts  = (new WP_Query([
				'post_type' => static::POST_TYPE,
				'post_status' => 'publish',
				'orderby' => 'menu_order post_title',
				'order'   => 'ASC',
				'posts_per_page' => -1,
			]))->posts;
		}
		return $this->get_changed_posts($request, array_map([$this, 'prepare'], $posts));
	}

	private function get_posts_recursive($id = 0) {
		$direct_children = (new WP_Query([
			'post_type' => static::POST_TYPE,
			'post_status' => 'publish',
			'post_parent' => $id,
			'orderby' => 'menu_order post_title',
			'order' => 'ASC',
			'posts_per_page' => -1
		]))->posts;
		$post = ($id == 0 ? [] : [get_post($id)]);
		if (empty($direct_children)) {
			return $post;
		} else {
			return array_reduce(
				array_map(
					[
						$this,
						'get_posts_recursive'
					],
					array_map(
						function ($child) {
							return $child->ID;
						},
						$direct_children
					)
				),
				function ($all_children, $grand_children) {
					return array_merge($all_children, $grand_children);
				},
				$post
			);
		}
	}

	protected function prepare(WP_Post $post) {
		$GLOBALS['post'] = $post; // define global $post to prevent wordpress notices caused by events-manager
		$post = apply_filters('wp_api_extensions_pre_post', $post);
		setup_postdata($post);
		$content = $this->prepare_content($post);
		$output_post = [
			'id' => $post->ID,
			'url' => get_permalink($post),
			'path' => wp_make_link_relative(get_permalink($post)),
			'title' => $post->post_title,
			'modified_gmt' => $post->post_modified_gmt,
			'verified_by' => ( get_post_meta( $post->ID, 'ig_ps_activate', true ) == "on" ? get_post_meta( $post->ID, 'ig_ps_organisation', true ) : null),
			'upvotes' => ( get_post_meta( $post->ID, 'ig_ps_activate', true ) == "on" ? get_upvotes( $post->ID) : null),
			'excerpt' => $this->prepare_excerpt($post),
			'content' => $content,
			'parent' => [
				'id' => $post->post_parent,
				'url' => ($post->post_parent !== 0 ? get_permalink($post->post_parent) : null),
				'path' => ($post->post_parent !== 0 ? wp_make_link_relative(get_permalink($post->post_parent)) : null)
			],
			'order' => $post->menu_order,
			'available_languages' => $this->get_available_languages($post),
			'thumbnail' => has_post_thumbnail($post->ID) ? wp_get_attachment_image_src(get_post_thumbnail_id($post->ID))[0] : null,
		];
		$output_post = apply_filters('wp_api_extensions_output_post', $output_post);
		$output_post['hash'] = md5(json_encode($output_post));
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
					'path' => wp_make_link_relative(get_permalink($id)),
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
				return new WP_Error('rest_no_data', 'No JSON-data was found', ['status' => 400]);
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
			if (!$local_posts_sanitized = $this->sanitize_posts($local_posts)) {
				return new WP_Error('rest_bad_data', 'The JSON-data does not match the expected format (see documentation for reference)', ['status' => 400]);
			}
			$key = array_keys($local_posts_sanitized[0])[0]; // Remember the first post's key
			$deleted_posts = [];
			if ($key == 'path') {
				/*
				 * If the key is 'path', we try the following:
				 * - Find a post id which belongs to the path
				 *   -> If no id is found, we mark the path as deleted
				 * - Find the current path of the found post
				 *   -> If the paths do not match, we mark the outdated path as deleted (the new one will be in the changed_posts automatically)
				 * - If the id is found and the paths match, we include the post in the diff calculation
				 */
				$local_posts_sanitized = array_reduce($local_posts_sanitized, function($return_array, $local_post) use (&$deleted_posts) {
					$id = url_to_postid($local_post['path']);
					if (!$id || wp_make_link_relative(get_permalink($id)) !== $local_post['path']) {
						$deleted_posts[] = [
							'path' => $local_post['path']
						];
					} else {
						$return_array[] = [
							'id' => $id,
							'hash' => $local_post['hash']
						];
					}
					return $return_array;
				}, []);
			}
			/*
			 * Filter all posts by the keys 'id' and 'hash' and serialize them to enable string-comparison of its elements.
			 */
			$local_posts_serialized = $this->serialize_posts($local_posts_sanitized);
			$all_posts_serialized = $this->serialize_posts($this->filter_posts($all_posts, ['id', 'hash']));
			/*
			 * Now we can determine which posts have been changed by taking all elements from $all_posts and remove the $local_posts.
			 * As we want to return all information of the changed posts (and not only id and hash),
			 * we intersect the keys of all results with the keys of the changed elements.
			 * As the keys of the resulting array are not relevant, we remove them with array_values().
			 */
			$changed_posts_keys = array_flip(array_keys(array_diff($all_posts_serialized, $local_posts_serialized)));
			$changed_posts = array_values(array_intersect_key($all_posts, $changed_posts_keys));
			if ($key == 'id') {
				/*
				 * To identify the deleted posts, we consider only the ids of all posts, serialize them and return all $local_posts without $all_posts.
				 * As the keys of the resulting array are not relevant, we remove them with array_values().
				 * In the end, we have to deserialize the deleted posts to restore the json logic.
				 */
				$local_posts_ids = $this->serialize_posts($this->filter_posts($local_posts_sanitized, ['id']));
				$all_posts_ids = $this->serialize_posts($this->filter_posts($all_posts, ['id']));
				$deleted_posts = $this->deserialize_posts(array_values(array_diff($local_posts_ids, $all_posts_ids)));
			}
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
	 * Sort, cast and filter the keys of every $post contained in $posts
	 */
	private function sanitize_posts($posts) {
		if (!is_array($posts)) { // If no array was given, we can't do anything
			return false;
		}
		if (isset($posts[0]['id'])) { // Check if the first element has either 'id' or 'path' key
			$key = 'id';
		} elseif (isset($posts[0]['path'])) {
			$key = 'path';
		} else {
			return false;
		}
		$identifiers = [];
		$posts_sanitized = [];
		foreach ($posts as $post) {
			if (!isset($post[$key]) || !isset($post['hash'])) {
				return false;
			}
			if ($key == 'id' && !is_numeric($post['id'])) { // If key is id, check if it is of type int
				return false;
			} elseif ($key == 'path' && !is_string($post['path']) && $post['path'] !== "") { // If key is path, check if it is of type string and not empty
				return false;
			}
			if (in_array($post[$key], $identifiers)) { // Check if identifier is unique
				continue;
			}
			$identifiers[] = $post[$key];
			if ($key == 'id') {
				$posts_sanitized[] = [
					'id' => (int) $post['id'],
					'hash' => (string) $post['hash']
				];
			} elseif ($key == 'path') {
				$posts_sanitized[] = [
					'path' => $post['path'],
					'hash' => (string) $post['hash']
				];
			}
		}
		return $posts_sanitized;
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
	protected function filter_posts($posts, $keys) {
		return array_map(function ($post) use ($keys) {
			return array_intersect_key($post, array_flip($keys));
		}, $posts);
	}

}