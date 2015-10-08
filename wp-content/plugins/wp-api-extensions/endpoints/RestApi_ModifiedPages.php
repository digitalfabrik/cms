<?php

require_once __DIR__ . '/RestApi_ModifiedContent.php';
require_once __DIR__ . '/helper/WpmlHelper.php';

/**
 * Retrieve only pages that have been modified since a given datetime
 */
class RestApi_ModifiedPages extends RestApi_ModifiedContent {
	protected function get_subpath() {
		return '/pages/';
	}

	protected function get_posts_type() {
		return 'page';
	}
}
