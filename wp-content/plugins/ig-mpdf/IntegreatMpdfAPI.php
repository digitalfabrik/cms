<?php

class IntegreatMpdfAPI {

    public function __construct() {
        $this->custom_endpoint();
    }

    /**
     * Add custom endpoint
     */
    private function custom_endpoint() {
		add_action('rest_api_init', function () {
			register_rest_route( 'ig-mpdf/v1', 'pdf', [
				'methods' => WP_REST_Server::READABLE,
				'callback' => [$this, 'get_pdf'],
				'args' => [
					'id' => [
						'required' => false,
						'validate_callback' => function($id) {
							$page = get_post($id);
							return $page !== null && $page->post_type === 'page';
						}
					],
					'url' => [
						'required' => false,
						'validate_callback' => function($url) {
							$page = get_post(url_to_postid($url));
							return $page !== null && $page->post_type === 'page';
						}
					],
				]
			]);
		});
    }

	/**
	 * Get pdf or return error if the request is not valid
	 *
	 * @param $request WP_REST_Request
	 * @return mixed pdf
	 * @throws \Mpdf\MpdfException
	 */
	public function get_pdf(WP_REST_Request $request) {
		$id = $request->get_param('id');
		$url = $request->get_param('url');
		if ($id !== null || $url !== null) {
			if ($id === null) {
				$id = url_to_postid($url);
			}
			$children_ids = new WP_Query([
				'post_type' => 'page',
				'post_status' => 'publish',
				'post_parent' => $id,
				'orderby' => 'menu_order post_title',
				'order' => 'ASC',
				'posts_per_page' => -1,
				'fields' => 'ids',
			]);
			$page_ids = array_merge([$id], $children_ids->posts);
		} else {
			$page_ids = (new WP_Query([
				'post_type' => 'page',
				'post_status' => 'publish',
				'orderby' => 'menu_order post_title',
				'order'   => 'ASC',
				'posts_per_page' => -1,
				'fields' => 'ids',
			]))->posts;
		}
		$pdf = new IntegreatMpdf($page_ids);
		return $pdf->get_pdf();
	}

}
