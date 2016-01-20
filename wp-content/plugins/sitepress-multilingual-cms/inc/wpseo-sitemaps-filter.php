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

		add_filter( 'wpml_get_home_url', array( $this, 'get_home_url_filter' ), 10, 5 );
		if ( $this->is_per_domain() ) {
			add_filter( 'wpseo_posts_join', array( $wpml_query_filter, 'filter_single_type_join' ), 10, 2 );
			add_filter( 'wpseo_posts_where', array( $wpml_query_filter, 'filter_single_type_where' ), 10, 2 );
			add_filter( 'wpseo_typecount_join', array( $wpml_query_filter, 'filter_single_type_join' ), 10, 2 );
			add_filter( 'wpseo_typecount_where', array( $wpml_query_filter, 'filter_single_type_where' ), 10, 2 );
		}

		add_filter( 'wpseo_enable_xml_sitemap_transient_caching', array( $this, 'transient_cache_filter' ), 10, 0 );
		add_action( 'wpseo_xmlsitemaps_config', array( $this, 'list_domains' ) );
	}

	public function get_home_url_filter( $home_url, $url, $path, $orig_scheme, $blog_id ) {
		if ( $this->is_per_domain() ) {
			global $wpml_url_converter;
			if ( ! isset( $wpml_url_converter ) ) {
				load_essential_globals();
			}

			$home_url = untrailingslashit($home_url);

			$home_url_parsed = parse_url( $home_url );

			$home_url_parsed['path'] = isset( $home_url_parsed['path'] ) ? '/' . untrailingslashit( ltrim( $home_url_parsed['path'], '/' ) ) : '';
			$path                    = $path && is_string( $path ) ? '/' . untrailingslashit( ltrim( $path, '/' ) ) : '';
			if ( $path && ( ! $home_url_parsed['path'] || $home_url_parsed['path'] != $path ) ) {
				$home_url .= $path;
			}

			$home_url = $wpml_url_converter->convert_url( $home_url, $this->sitepress->get_current_language() );
		}

		return $home_url;
	}

	public function list_domains() {
		if ( $this->is_per_domain() || $this->has_root_page() ) {

			echo '<h3>' . __( 'WPML', 'sitepress' ) . '</h3>';
			echo __( 'Sitemaps for each languages can be accessed here:', 'sitepress' );
			echo '<table class="wpml-sitemap-translations" style="margin-left: 1em; margin-top: 1em;">';

			$base_style = "style=\"
			background-image:url('%s');
			background-repeat: no-repeat;
			background-position: 2px center;
			background-size: 16px;
			padding-left: 20px;
			width: 100%%;
			\"
			";

			foreach ( $this->sitepress->get_ls_languages() as $lang ) {
				$url = $lang['url'] . 'sitemap_index.xml';
				echo '<tr>';
				echo '<td>';
				echo '<a ';
				echo 'href="' . $url . '" ';
				echo 'target="_blank" ';
				echo 'class="button-secondary" ';
				echo sprintf( $base_style, $lang['country_flag_url'] );
				echo '>';
				echo $lang['translated_name'];
				echo '</a>';
				echo '</td>';
				echo '</tr>';
			}
			echo '</table>';
		}
	}

	public function is_per_domain() {
		return 2 === (int) $this->sitepress->get_setting( 'language_negotiation_type', false );
	}

	public function transient_cache_filter() {
		global $sitepress_settings;

		// Before to build the sitemap and as we are on front-end
		// just make sure the links won't be translated
		$sitepress_settings['auto_adjust_ids'] = 0;

		return false;
	}

	private function has_root_page() {
		return $this->sitepress->get_root_page_utils()->get_root_page_id();
	}
}

global $sitepress;

$wpseo_xml_filter = new WPSEO_XML_Sitemaps_Filter( $sitepress );
