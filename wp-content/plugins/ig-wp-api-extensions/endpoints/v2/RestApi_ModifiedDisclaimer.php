<?php

/**
 * Retrieve only disclaimer posts that have been modified since a given datetime
 */
class RestApi_ModifiedDisclaimerV2 extends RestApi_ModifiedContentV2 {
	protected function get_subpath() {
		return '/disclaimer/';
	}

	protected function get_posts_type() {
		return 'disclaimer';
	}
}
