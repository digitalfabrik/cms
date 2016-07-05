<?php

require ICL_PLUGIN_PATH . '/inc/url-handling/wpml-xdomain-data-parser.class.php';

class WPML_Lang_Domains_Converter extends WPML_URL_Converter {

	/** @var string[] $domains */
	protected $domains = array();
	/** @var WPML_XDomain_Data_Parser $wpml_xdomain_parser */
	public $wpml_xdomain_parser;

	/**
	 * @param array $domains
	 * @param string $default_language
	 * @param array $hidden_languages
	 */
	public function __construct( $domains, $default_language, $hidden_languages, &$wpml_wp_api ) {
		parent::__construct( $default_language, $hidden_languages, $wpml_wp_api );
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

	private function sanitize_and_parse_url( $url ) {
		if ( strpos( $url, "://" ) === false && substr( $url, 0, 1 ) != "/" ) {
			$url = "http://" . $url;
		}

		return parse_url( untrailingslashit( $url ) );
	}

	private function unparse_url( $parsed_url ) {
		$scheme   = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : '';
		$host     = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
		$port     = isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : '';
		$user     = isset( $parsed_url['user'] ) ? $parsed_url['user'] : '';
		$pass     = isset( $parsed_url['pass'] ) ? ':' . $parsed_url['pass'] : '';
		$pass     = ( $user || $pass ) ? "$pass@" : '';
		$path     = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';
		$query    = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';
		$fragment = isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '';

		return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
	}

	protected function convert_url_string( $source_url, $lang ) {
		$original_source_url = untrailingslashit( $source_url );
		if ( is_admin() && $this->is_url_admin( $original_source_url ) ) {
			return $original_source_url;
		}

		$absolute_home_url = $this->get_abs_home();
		$domain_url        = trailingslashit( isset( $this->domains[ $lang ] ) ? $this->domains[ $lang ] : $absolute_home_url );

		$original_source_url_parsed = $this->sanitize_and_parse_url( $original_source_url );

		$domain_url_parsed = $this->sanitize_and_parse_url( $domain_url );

		if ( isset( $original_source_url_parsed['scheme'] ) && $domain_url_parsed['scheme'] != $original_source_url_parsed['scheme'] ) {
			$domain_url_parsed['scheme'] = $original_source_url_parsed['scheme'];
		}

		$converted_url_parsed = array_merge( $original_source_url_parsed, $domain_url_parsed );

		if ( isset( $original_source_url_parsed['path'] ) && isset( $domain_url_parsed['path'] ) && $original_source_url_parsed['path'] !== $domain_url_parsed['path'] ) {
			$converted_url_parsed['path'] = $domain_url_parsed['path'] . $original_source_url_parsed['path'];
		}

		if ( strpos( $original_source_url, "://" ) === false && isset( $converted_url_parsed['scheme'] ) ) {
			unset( $converted_url_parsed['scheme'] );
		}

		return untrailingslashit( $this->unparse_url( $converted_url_parsed ) );
	}

	public function get_admin_ajax_url( $url ) {

		if ( $this->wpml_wp_api->is_front_end() ) {
			global $sitepress;

			$url = $this->convert_url( $url, $sitepress->get_current_language() );
		} else {
			$url = parent::get_admin_ajax_url( $url );
		}

		return $url;
	}
}