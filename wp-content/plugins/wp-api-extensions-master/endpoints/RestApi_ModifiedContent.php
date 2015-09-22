<?php

/**
 * Retrieve only content that has been modified since a given datetime
 */
class RestApi_ModifiedContent {
	const URL = 'modified_content';
	private $datetime_format = 'Y-m-d G:i:s';

	private $baseUrl;

	public function __construct($pluginBaseUrl) {
		$this->baseUrl = $pluginBaseUrl . '/' . self::URL;
	}


	public function register_routes() {
		register_rest_route($this->baseUrl, '/posts_and_pages/(?P<last_modified_gmt>.*)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_modified_posts_and_pages'),
		));
	}

	public function get_modified_posts_and_pages($data) {
		$last_modified_gmt = $data['last_modified_gmt'];
		if (!$this->validate_datetime($last_modified_gmt)) {
			return new WP_Error("wp-api-modified-content_datetime_invalid",
				"Invalid datetime '$last_modified_gmt' - expected format is $this->datetime_format",
				array('status' => 400));
		}

		$query_args = array(
			'post_type' => array('post', 'page'),
			'date_query' => array(
				'column' => 'post_modified_gmt',
				'after' => $last_modified_gmt,
			),
			'posts_per_page' => -1 /* show all */,
		);
		$query = new WP_Query();
		$query_result = $query->query($query_args);

		$result = array();
		foreach ($query_result as $item) {
			$result[] = $this->prepare_item($item);
		}
		return $result;
	}

	private function prepare_item($item) {
		return [
			'id' => $item->ID,
			'title' => $item->post_title,
			'type' => $item->post_type,
			'modified_gmt' => $item->post_modified_gmt,
			'excerpt' => $item->post_content ?: wp_trim_excerpt($item->post_content),
			'content' => $item->post_content,
			'parent' => $item->post_parent
		];
	}

	private function validate_datetime($arg) {
		return DateTime::createFromFormat($this->datetime_format, $arg) !== false;
	}
}
