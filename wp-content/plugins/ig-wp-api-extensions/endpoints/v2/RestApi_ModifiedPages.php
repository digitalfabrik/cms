<?php

/**
 * Retrieve only pages that have been modified since a given datetime
 */
class RestApi_ModifiedPagesV2 extends RestApi_ModifiedContentV2 {
	protected function get_subpath() {
		return '/pages/';
	}

	protected function get_posts_type() {
		ig_api_page_tracking( 'Integreat API '.ICL_LANGUAGE_CODE );
		return 'page';
	}
}
