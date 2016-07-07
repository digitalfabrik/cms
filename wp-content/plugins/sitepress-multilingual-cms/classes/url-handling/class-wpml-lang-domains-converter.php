<?php

class WPML_Lang_Domains_Converter extends WPML_URL_Converter {

	/** @var WPML_XDomain_Data_Parser $wpml_xdomain_parser */
	public $wpml_xdomain_parser;
	/** @var string[] $domains */
	protected $domains = array();
	/** @var WPML_WP_API $wpml_wp_api */
	private $wpml_wp_api;

	/**
	 * @param array       $domains
	 * @param string      $default_language
	 * @param array       $active_languages
	 * @param WPML_WP_API $wpml_wp_api
	 */
	public function __construct(
		$domains,
		$default_language,
		$active_languages,
		&$wpml_wp_api
	) {
		parent::__construct( $default_language, $active_languages );
		$this->wpml_wp_api = &$wpml_wp_api;
		$this->domains     = preg_replace( '#^(http(?:s?))://#', '',
			array_map( 'trailingslashit', $domains ) );
		if ( isset( $this->domains[ $this->default_language ] ) ) {
			unset( $this->domains[ $this->default_language ] );
		}
		add_filter( 'login_url', array( $this, 'convert_url' ) );
		add_filter( 'logout_url', array( $this, 'convert_url' ) );
		add_filter( 'admin_url', array( $this, 'admin_url_filter' ), 1, 2 );
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
			'${1}' . preg_replace(
				array('#^(http(?:s?))://#', '#(\w/).+$#'),
				array('', '$1'),
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

	public function admin_url_filter( $url, $path ) {
		if ( ( strpos( $url, 'http://' ) === 0
		       || strpos( $url, 'https://' ) === 0 )
		     && 'admin-ajax.php' === $path && $this->wpml_wp_api->is_front_end()
		) {
			global $sitepress;

			$url = $this->convert_url( $url,
				$sitepress->get_current_language() );
		}

		return $url;
	}
}