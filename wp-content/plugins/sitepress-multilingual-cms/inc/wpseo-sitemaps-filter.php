<?php

/**
 * WP SEO by Yoast sitemap filter class
 *
 * @version 1.0.2
 */
class WPSEO_XML_Sitemaps_Filter {

	/**
	 * WPSEO_XML_Sitemaps_Filter constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( &$sitepress ) {
		$this->sitepress = &$sitepress;

		global $wpml_query_filter;

		add_filter( 'wpseo_posts_join', array( $wpml_query_filter, 'filter_single_type_join' ), 10, 2 );
		add_filter( 'wpseo_posts_where', array( $wpml_query_filter, 'filter_single_type_where' ), 10, 2 );
		add_filter( 'wpseo_typecount_join', array( $wpml_query_filter, 'filter_single_type_join' ), 10, 2 );
		add_filter( 'wpseo_typecount_where', array( $wpml_query_filter, 'filter_single_type_where' ), 10, 2 );
		add_filter( 'wpseo_enable_xml_sitemap_transient_caching', array( $this, 'transient_cache_filter' ), 10, 0 );
		add_action( 'wpseo_xmlsitemaps_config', array( $this, 'list_domains' ) );
	}

	public function list_domains() {
		if ( $this->is_per_domain() ) {

			echo '<h3>' . __( 'WPML', 'sitepress' ) . '</h3>';
			echo __( 'Sitemaps for each languages can be accessed here:', 'sitepress' );
			echo '<ol>';

			foreach ( $this->sitepress->get_ls_languages() as $lang ) {
				$url = $lang['url'] . 'sitemap_index.xml';
				echo '<li>';
				echo $lang['translated_name'] . ': ';
				echo' <a href="' . $url . '" target="_blank">' . $url . '</a>';
				echo '</li>';
			}
			echo '</ol>';
		}
	}

	public function is_per_domain() {
		return 2 === (int) $this->sitepress->get_setting( 'language_negotiation_type', false );
	}

	public function transient_cache_filter() {

		return false;
	}
}

global $sitepress;

$wpseo_xml_filter = new WPSEO_XML_Sitemaps_Filter( $sitepress );
