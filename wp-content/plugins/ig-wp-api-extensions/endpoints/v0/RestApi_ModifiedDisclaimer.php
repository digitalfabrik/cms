<?php

require_once __DIR__ . '/RestApi_ModifiedContent.php';
require_once __DIR__ . '/helper/WpmlHelper.php';

/**
 * Retrieve only disclaimer posts that have been modified since a given datetime
 */
class RestApi_ModifiedDisclaimerV0 extends RestApi_ModifiedContentV0 {
	protected function get_subpath() {
		return '/disclaimer/';
	}

	protected function get_posts_type() {
		return 'disclaimer';
	}
}
