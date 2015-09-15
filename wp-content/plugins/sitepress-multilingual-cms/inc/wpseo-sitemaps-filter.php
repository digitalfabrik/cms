<?php

/**
 * WP SEO by Yoast sitemap filter class
 *
 * @version 1.0.2
 */
class WPSEO_XML_Sitemaps_Filter {

	public function __construct() {

		global $wpml_query_filter;

		add_filter( 'wpseo_posts_join', array( $wpml_query_filter, 'filter_single_type_join' ), 10, 2 );
		add_filter( 'wpseo_posts_where', array( $wpml_query_filter, 'filter_single_type_where' ), 10, 2 );
		add_filter( 'wpseo_typecount_join', array( $wpml_query_filter, 'filter_single_type_join' ), 10, 2 );
		add_filter( 'wpseo_typecount_where', array( $wpml_query_filter, 'filter_single_type_where' ), 10, 2 );
		add_filter( 'wpseo_enable_xml_sitemap_transient_caching', array( $this, 'transient_cache_filter' ), 10, 0 );
	}

	public function transient_cache_filter() {

		return false;
	}
}

$wpseo_xml_filter = new WPSEO_XML_Sitemaps_Filter();
