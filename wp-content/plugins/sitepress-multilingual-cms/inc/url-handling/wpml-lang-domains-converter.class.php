<?php

require ICL_PLUGIN_PATH . '/inc/url-handling/wpml-xdomain-data-parser.class.php';

class WPML_Lang_Domains_Converter extends WPML_URL_Converter {

	/** @var string[] $domains */
	protected $domains = array();
	/** @var WPML_XDomain_Data_Parser $wpml_xdomain_parser */
	public $wpml_xdomain_parser;

	/**
	 * @param string[] $domains
	 * @param string   $default_language
	 * @param string[] $hidden_languages
	 */
	public function __construct( $domains, $default_language, $hidden_languages ) {
		parent::__construct( $default_language, $hidden_languages );
		$this->domains = preg_replace( '#^(http(?:s?))://#', '', array_map( 'trailingslashit', $domains ) );
		if(isset($this->domains[$this->default_language])) {
			unset($this->domains[$this->default_language]);
		}
		add_filter( 'login_url', array( $this, 'convert_url' ) );
		add_filter( 'logout_url', array( $this, 'convert_url' ) );
		$this->wpml_xdomain_parser = new WPML_XDomain_Data_Parser();
	}

	protected function get_lang_from_url_string( $url ) {
		$url = preg_replace( '#^(http(?:s?))://#', '', $url );
		if ( strpos( $url, "?" ) ) {
			$parts = explode( "?", $url );
			$url   = $parts[0];
		}

		foreach ( $this->domains as $code => $domain ) {
			if ( strpos( trailingslashit( $url ), $domain ) === 0 ) {
				$lang = $code;
				break;
			}
		}

		return isset( $lang ) ? $lang : null;
	}

	protected function convert_url_string( $source_url, $lang ) {
		$original_source_url = untrailingslashit( $source_url );
		if ( is_admin() && $this->is_url_admin( $original_source_url ) ) {
			return $original_source_url;
		}
		$absolute_home_url = $this->get_abs_home();
		$converted_url     = preg_replace(
			'#^(https?://)?([^\/]*)\/?#',
			'$1' . preg_replace(
				'#^(http(?:s?))://#',
				'',
				trailingslashit(
					isset( $this->domains[ $lang ] ) ? $this->domains[ $lang ]
						: $absolute_home_url )
			),
			strpos( $original_source_url, '?' ) !== false
				? $original_source_url
				: trailingslashit( $original_source_url )
		);

		return untrailingslashit( $converted_url );
	}

	public function get_admin_ajax_url( $url ) {
		global $sitepress;

		return $this->convert_url( $url, $sitepress->get_current_language() );
	}
}