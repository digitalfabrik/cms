<?php

/**
 * Class WPML_Frontend_Request
 *
 * @package    wpml-core
 * @subpackage wpml-requests
 */
class WPML_Frontend_Request extends WPML_Request {

	public function get_requested_lang() {
		$lang = $this->get_request_uri_lang();

		return $lang;
	}

	protected function get_cookie_name() {

		return '_icl_current_language';
	}
}