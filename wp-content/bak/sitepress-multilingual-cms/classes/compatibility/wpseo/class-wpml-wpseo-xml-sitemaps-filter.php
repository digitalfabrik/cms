<?php

/**
 * WP SEO by Yoast sitemap filter class
 *
 * @version 1.0.2
 */
class WPML_WPSEO_XML_Sitemaps_Filter extends WPML_SP_User {

	/**
	 * WPML_URL_Converter object.
	 *
	 * @var WPML_URL_Converter
	 */
	private $wpml_url_converter;

	/**
	 * WPSEO_XML_Sitemaps_Filter constructor.
	 *
	 * @param SitePress $sitepress
	 * @param object    $wpml_url_converter
	 */
	public function __construct( &$sitepress, &$wpml_url_converter ) {
		$this->sitepress = &$sitepress;
		$this->wpml_url_converter = &$wpml_url_converter;

		global $wpml_query_filter;

		if ( $this->is_per_domain() ) {
			add_filter( 'wpml_get_home_url', array( $this, 'get_home_url_filter' ), 10, 1 );
			add_filter( 'wpseo_posts_join', array( $wpml_query_filter, 'filter_single_type_join' ), 10, 2 );
			add_filter( 'wpseo_posts_where', array( $wpml_query_filter, 'filter_single_type_where' ), 10, 2 );
			add_filter( 'wpseo_typecount_join', array( $wpml_query_filter, 'filter_single_type_join' ), 10, 2 );
			add_filter( 'wpseo_typecount_where', array( $wpml_query_filter, 'filter_single_type_where' ), 10, 2 );
		} else {
			add_filter( 'wpseo_sitemap_page_content', array( $this, 'add_languages_to_sitemap' ) );
			// Remove posts under hidden language.
			add_filter( 'wpseo_xml_sitemap_post_url', array( $this, 'exclude_hidden_language_posts' ), 10, 2 );
		}

		add_filter( 'wpseo_enable_xml_sitemap_transient_caching', array( $this, 'transient_cache_filter' ), 10, 0 );
		add_filter( 'wpseo_build_sitemap_post_type', array( $this, 'wpseo_build_sitemap_post_type_filter' ) );
		add_action( 'wpseo_xmlsitemaps_config', array( $this, 'list_domains' ) );
	}

	/**
	 * Add home page urls for languages to sitemap.
	 * Do this only if configuration language per domain option is not used.
	 */
	public function add_languages_to_sitemap() {
		$output = '';
		$default_lang = $this->sitepress->get_default_language();
		$active_langs = $this->sitepress->get_active_languages();
		unset( $active_langs[ $default_lang ] );

		foreach ( $active_langs as $lang_code => $lang_data ) {
			$output .= $this->sitemap_url_filter( $this->wpml_url_converter->convert_url( home_url(), $lang_code ) );
		}
		return $output;
	}

	/**
	 * Update home_url for language per-domain configuration to return correct URL in sitemap.
	 */
	public function get_home_url_filter( $home_url ) {
		return $this->wpml_url_converter->convert_url( $home_url, $this->sitepress->get_current_language() );
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
		return false;
	}

	public function wpseo_build_sitemap_post_type_filter( $type ) {
		global $sitepress_settings;
		// Before to build the sitemap and as we are on front-end
		// just make sure the links won't be translated
		// The setting should not be updated in DB
		$sitepress_settings['auto_adjust_ids'] = 0;

		if ( !$this->is_per_domain() && !$this->has_root_page() ) {
			remove_filter( 'terms_clauses', array( $this->sitepress, 'terms_clauses' ), 10 );
		}

		return $type;
	}

	private function has_root_page() {
		return (bool) $this->sitepress->get_root_page_utils()->get_root_page_id();
	}

	/**
	 * Exclude posts under hidden language.
	 *
	 * @param  string $url   Post URL.
	 * @param  object $post  Object with some post information.
	 *
	 * @return string
	 */
	public function exclude_hidden_language_posts( $url, $post ) {
		// Check that at least ID is set in post object.
		if ( ! isset( $post->ID ) ) {
			return $url;
		}

		// Get list of hidden languages.
		$hidden_languages = $this->sitepress->get_setting( 'hidden_languages', array() );

		// If there are no hidden languages return original URL.
		if ( empty( $hidden_languages ) ) {
			return $url;
		}

		// Get language information for post.
		$language_info = $this->sitepress->post_translations()->get_element_lang_code( $post->ID );

		// If language code is one of the hidden languages return empty string to skip the post.
		if ( in_array( $language_info, $hidden_languages ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * Convert URL to sitemap entry format.
	 *
	 * @param string $url URl to prepare for sitemap.
	 *
	 * @return string
	 */
	public function sitemap_url_filter( $url ) {
		$url = htmlspecialchars( $url );

		$output = "\t<url>\n";
		$output .= "\t\t<loc>" . $url . "</loc>\n";
		$output .= '';
		$output .= "\t\t<changefreq>daily</changefreq>\n";
		$output .= "\t\t<priority>1.0</priority>\n";
		$output .= "\t</url>\n";

		return $output;
	}
}
