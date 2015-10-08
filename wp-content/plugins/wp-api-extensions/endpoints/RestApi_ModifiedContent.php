<?php

require_once __DIR__ . '/RestApi_ExtensionBase.php';
require_once __DIR__ . '/helper/WpmlHelper.php';

/**
 * Retrieve only content that has been modified since a given datetime
 */
abstract class RestApi_ModifiedContent extends RestApi_ExtensionBase {
	const URL = 'modified_content';
	/**
	 * Match empty p html tags spanning the whole string.
	 *
	 * Examples matched:
	 *
	 *     <p></p>
	 *     <p></p><p></p>
	 *     <p>  </p>
	 *     <p></p>  <p></p>
	 *     \n
	 *     <p></p>\n<p></p>
	 *     <p></p>\n  <p></p>
	 *     <p></p>\r\n  <p></p>\r\n
	 *     <p>&nbsp; &nbsp;</p>\n
	 *     &nbsp; &nbsp;\n
	 *     <p>\n</p>
	 */
	const EMPTY_P_PATTERN = '#^((<p>(\s|&nbsp;|<br\s*/\s*>|[\\\n\\\r\s])*</p>)|([\\\n\\\r\s]|&nbsp;|<br\s*/\s*>))*$#';
	/** The return value for a content that is considered empty */
	const EMPTY_CONTENT = "";
	const EXCERPT_LINEBREAK_INDICATOR = " ";

	private $datetime_input_format = DateTime::ATOM;
	private $datetime_query_format = DateTime::ATOM;
	private $datetime_zone_gmt;

	public function __construct($namespace) {
		parent::__construct($namespace, self::URL);
		$this->datetime_zone_gmt = new DateTimeZone('GMT');
		$this->disable_permanent_deletion();
		$this->wpml_helper = new WpmlHelper();
	}

	public function register_routes() {
		$args = $this->get_route_args();

		parent::register_route($this->get_subpath(), [
			'callback' => [$this, 'get_modified_content'],
			'args' => $args
		]);
	}

	private function get_route_args() {
		return [
			'since' => [
				'required' => true,
				'validate_callback' => [$this, 'validate_datetime']
			]
		];
	}

	protected abstract function get_subpath();

	protected abstract function get_posts_type();

	public function validate_datetime($arg) {
		return $this->make_datetime($arg) !== false;
	}

	private function make_datetime($arg) {
		return DateTime::createFromFormat($this->datetime_input_format, $arg);
	}

	public function get_modified_content(WP_REST_Request $request) {
		return $this->get_modified_posts_by_type($this->get_posts_type(), $request);
	}

	private function get_modified_posts_by_type($type, WP_REST_Request $request) {
		global $wpdb;
		$querystr = $this->build_query_string($type, $request);
		$query_result = $wpdb->get_results($querystr, OBJECT);

		$result = [];
		foreach ($query_result as $post) {
			$result[] = $this->prepare_item($post);
		}
		return $result;
	}

	/**
	 * @param $post_type
	 * @param WP_REST_Request $request
	 * @return string
	 */
	protected function build_query_string($post_type, WP_REST_Request $request) {
		return
			$this->build_query_select() . " " .
			$this->build_query_from() . " " .
			$this->build_query_where($post_type, $request) . " " .
			$this->build_query_aggregate();
	}

	protected function build_query_select() {
		return "SELECT posts.ID, posts.post_title, posts.post_type, posts.post_status, posts.post_modified_gmt,
					posts.post_excerpt, posts.post_content, posts.post_parent, posts.menu_order,
					users.user_login, usermeta_firstname.meta_value as author_firstname, usermeta_lastname.meta_value as author_lastname";
	}

	protected function build_query_from() {
		global $wpdb;
		return "FROM $wpdb->posts posts
				JOIN $wpdb->users users ON users.ID = posts.post_author
				JOIN $wpdb->usermeta usermeta_firstname ON usermeta_firstname.user_id = users.ID AND usermeta_firstname.meta_key = 'first_name'
				JOIN $wpdb->usermeta usermeta_lastname ON usermeta_lastname.user_id = users.ID AND usermeta_lastname.meta_key = 'last_name'";
	}

	protected function build_query_where($type, WP_REST_Request $request) {
		$since = $request->get_param('since');
		$last_modified_gmt = $this
			->make_datetime($since)
			->setTimezone($this->datetime_zone_gmt)
			->format($this->datetime_query_format);
		return "WHERE post_type = '$type'
				AND post_modified_gmt >= '$last_modified_gmt'
				AND post_status IN ('publish', 'trash')";
	}

	protected function build_query_aggregate() {
		return "ORDER BY menu_order ASC, post_title ASC";
	}

	protected function prepare_item($post) {
		setup_postdata($post);
		$content = $this->prepare_content($post);
		return [
			'id' => $post->ID,
			'title' => $post->post_title,
			'type' => $post->post_type,
			'status' => $post->post_status,
			'modified_gmt' => $post->post_modified_gmt,
			'excerpt' => $content === self::EMPTY_CONTENT ? self::EMPTY_CONTENT : $this->prepare_excerpt($post),
			'content' => $content,
			'parent' => $post->post_parent,
			'order' => $post->menu_order,
			'available_languages' => $this->wpml_helper->get_available_languages($post->ID, $post->post_type),
			'thumbnail' => $this->prepare_thumbnail($post),
			'author' => $this->prepare_author($post)
		];
	}

	protected function prepare_content($post) {
		$content = $post->post_content;
		$match_result = preg_match(self::EMPTY_P_PATTERN, $content);
		if ($match_result === false) {
			throw new RuntimeException("preg_match on content indicated error status (pattern='" . self::EMPTY_P_PATTERN . "', content='$content')");
		}
		if ($match_result) {
			return self::EMPTY_CONTENT;
		}
		// replace all newlines with surrounding p tags
		return "<p>" . str_replace(["\r\n", "\r", "\n"], "</p><p>", $content) . "</p>";
	}

	protected function prepare_excerpt($post) {
		$excerpt = $post->post_excerpt ?:
			apply_filters('the_excerpt', apply_filters('get_the_excerpt', $post->post_excerpt));
		$excerpt = str_replace(["</p>", "\r\n", "\n", "\r", "<p>"],
			[self::EXCERPT_LINEBREAK_INDICATOR, self::EXCERPT_LINEBREAK_INDICATOR, self::EXCERPT_LINEBREAK_INDICATOR, "", ""],
			$excerpt);
		return trim($excerpt);
	}

	protected function prepare_thumbnail($post) {
		if (!has_post_thumbnail($post->ID)) {
			return null;
		}
		$image_src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID));
		return $image_src[0];
	}

	protected function prepare_author($post) {
		return [
			'login' => $post->user_login,
			'first_name' => $post->author_firstname,
			'last_name' => $post->author_lastname
		];
	}

	private function disable_permanent_deletion() {
		add_action('delete_post', [$this, 'restrict_post_deletion'], 10, 0);
//		global $wp_roles;
//		foreach($wp_roles->role_objects as $role) {
//			echo "$role->name: ";
//			print_r($role->capabilities);
//			$role->remove_cap('delete_pages');
//			$role->remove_cap('delete_others_pages');
//			$role->remove_cap('delete_published_pages');
//			$role->remove_cap('delete_posts');
//			$role->remove_cap('delete_others_posts');
//			$role->remove_cap('delete_published_posts');
//			echo "$role->name: ";
//			print_r($role->capabilities);
//			echo "Removed capabilities from $role->name\n";
//		}
	}

	public function restrict_post_deletion() {
		echo "You are not authorized to delete this page.";
		exit;

	}
}
