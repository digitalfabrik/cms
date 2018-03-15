<?php

class IntegreatMpdfAPI {

    public function __construct() {
        $this->custom_endpoint();
    }

    /**
     * Add custom endpoint
     */
    private function custom_endpoint() {
        add_action( 'rest_api_init', function () {
            register_rest_route( 'ig-mpdf/v1', '/(?P<instance>\d+)/(?P<page_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_pdf'),
            ) );
        } );
    }

	/**
	 * Get pdf or return error if the request is not valid
	 *
	 * @param $params array
	 * @return mixed pdf if everything is ok, WP_Error else
	 */
	public function get_pdf($params) {
		if (!get_blog_details($params['instance'])) {
			return new WP_Error('instance_not_found', 'There is no instance with the id ' . $params['instance'], ['status' => 404]);
		}
		switch_to_blog($params['instance']);
		$page = get_post($params['page_id']);
		if ($page === null || $page->post_type !== 'page') {
			return new WP_Error('page_not_found', 'There is no page with the id ' . $params['page_id'], ['status' => 404]);
		}
		$children_ids = new WP_Query([
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_parent' => $page->ID,
			'posts_per_page' => -1,
			'fields' => 'ids',
			'suppress_filters' => true
		]);
		$language = apply_filters('wpml_element_language_code', null, array('element_id' => $page->ID, 'element_type' => 'page'));
		$pdf = new IntegreatMpdf(array_merge([$page->ID], $children_ids->posts), $params['instance'], $language);
		return $pdf->get_pdf();
	}

}
