<?php

class IntegreatMpdfAPI {
    private $pages;
    private $instance;

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
                'callback' => array($this, 'custom_endpoint_callback'),
            ) );
        } );
    }

    /**
     * Custom endpoint callback
     *
     * @param $params array
     * @return string pdf link
     */
    public function custom_endpoint_callback($params) {
        $this->instance = $params['instance'];
        return $this->get_pdf_link($params['page_id']);
    }

    /**
     * Get pdf link and handle generation
     *
     * @param $page
     * @return string: pdf link
     */
    private function get_pdf_link($page) {
        switch_to_blog($this->instance);
        $this->assemble_pages($page);
        $language = apply_filters('wpml_element_language_code', null, array('element_id' => $page, 'element_type' => 'page' ));
        $pdf = new IntegreatMpdf($this->pages, $this->instance, $language);
        return $pdf->get_pdf();
    }

    /**
     * Assemble pages by finding given pages children
     *
     * @param $page_id
     */
    private function assemble_pages($page_id) {
        $wp_query = new WP_Query();
        $all_pages = $wp_query->query(array('post_type' => 'page', 'posts_per_page' => '-1'));
        $children = array();
        $results = get_page_children($page_id, $all_pages);
        foreach($results as &$result) {
            $children[] = $result->ID;
        }
        $this->pages = array_merge(array(intval($page_id)), $children);
    }

}