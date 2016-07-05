<?php

/**
 * Class WPML_Frontend_Request
 *
 * @package    wpml-core
 * @subpackage wpml-requests
 */
class WPML_Frontend_Request extends WPML_Request {

	/** @var  string $pagenow */
	private $pagenow;

	public function __construct( &$url_converter, $active_languages, $default_language, &$cookie, &$pagenow ) {
		parent::__construct( $url_converter, $active_languages, $default_language, $cookie );
		$this->pagenow = &$pagenow;
	}

	public function get_requested_lang() {

		return $this->pagenow === 'wp-comments-post.php'
			? $this->get_cookie_lang() : $this->get_request_uri_lang();
	}

	protected function get_cookie_name() {

		return '_icl_current_language';
	}
}