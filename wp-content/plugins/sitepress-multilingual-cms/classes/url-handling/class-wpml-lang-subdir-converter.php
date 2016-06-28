<?php

class WPML_Lang_Subdir_Converter extends WPML_URL_Converter {

	/** @var string */
	private $dir_default;

	/** @var array copy of $sitepress->get_settings( 'urls' ) */
	private $urls_settings;

	/** @var string */
	private $root_url;

	/** @var array map of wpml codes to custom codes*/
	private $language_codes_map = array();
	private $language_codes_reverse_map = array();

	/**
	 * WPML_Lang_Subdir_Converter constructor.
	 *
	 * @param string $dir_default
	 * @param string $default_language
	 * @param array  $active_languages
	 */
	public function __construct(
		$dir_default,
		$default_language,
		$active_languages,
		$urls_settings
	) {
		parent::__construct( $default_language, $active_languages );
		$this->dir_default   = $dir_default;
		$this->urls_settings = $urls_settings;

		foreach ( $active_languages as $language ) {
			$this->language_codes_map[ $language ] = $language;
		}
		$this->language_codes_map = apply_filters( 'wpml_language_codes_map', $this->language_codes_map );
		foreach ( $this->language_codes_map as $wpml_code => $custom_code ) {
			$this->language_codes_reverse_map[ $custom_code ] = $wpml_code;
		}
	}

	protected function get_lang_from_url_string( $url ) {

		$url = wpml_strip_subdir_from_url ( $url );

		if ( strpos ( $url, 'http://' ) === 0 || strpos ( $url, 'https://' ) === 0 ) {
			$url_path = wpml_parse_url ( $url, PHP_URL_PATH );
		} else {
			$pathparts = array_filter ( explode ( '/', $url ) );
			if ( count ( $pathparts ) > 1 ) {
				unset( $pathparts[ 0 ] );
				$url_path = implode ( '/', $pathparts );
			} else {
				$url_path = $url;
			}
		}

		$fragments = array_filter ( (array) explode ( "/", $url_path ) );
		$lang      = array_shift ( $fragments );

		$lang_get_parts = explode( '?', $lang );
		$lang           = $lang_get_parts[ 0 ];

		$lang           = isset( $this->language_codes_reverse_map[ $lang ] ) ? $this->language_codes_reverse_map[ $lang ] : $lang;

		return $lang && in_array ( $lang, $this->active_languages )
			? $lang : ( $this->dir_default ? null : $this->default_language );
	}

	protected function validate_language( $language, $url ) {
		if ( !( $language === null && $this->dir_default && !$this->is_url_admin ( $url ) ) ) {
			$language = parent::validate_language ( $language, $url );
		}

		return $language;
	}

	protected function convert_url_string( $source_url, $code ) {
		if ( ! $this->is_root_url( $source_url ) ) {
			$source_url        = strpos( $source_url, '?' ) === false ? trailingslashit( $source_url ) : $source_url;
			$source_url        = strpos( $source_url, '?' ) !== false && strpos( $source_url, '/?' ) === false
				? str_replace( '?', '/?', $source_url ) : $source_url;
			$absolute_home_url = trailingslashit( preg_replace( '#^(http|https)://#', '', $this->get_abs_home() ) );
			$code              = ! $this->dir_default && $code === $this->default_language ? '' : $code;
			$current_language  = $this->get_lang_from_url_string( $source_url );
			$current_language  = ! $this->dir_default && $current_language === $this->default_language ? '' : $current_language;
			$absolute_home_url = strpos( $source_url, $absolute_home_url ) === false ? trailingslashit( get_option( 'home' ) ) : $absolute_home_url;

			$code             = isset( $this->language_codes_map[ $code ] ) ? $this->language_codes_map[ $code ] : $code;
			$current_language = isset( $this->language_codes_map[ $current_language ] ) ? $this->language_codes_map[ $current_language ] : $current_language;
			
			$source_url        = str_replace(
				trailingslashit( $absolute_home_url . $current_language ),
				$code ? ( $absolute_home_url . $code . '/' ) : trailingslashit( $absolute_home_url ),
				$source_url
			);
			$source_url        = str_replace( '/' . $code . '//', '/' . $code . '/', $source_url );
		}

		return untrailingslashit( $source_url );
	}

	/**
	 * Will return true if root URL or child of root URL
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	private function is_root_url( $url ) {
		$ret  = false;

		if ( isset( $this->urls_settings['root_page'] ) && isset( $this->urls_settings['show_on_root'] )
		     && ! empty( $this->urls_settings['directory_for_default_language'] )
		     && ( $this->urls_settings['show_on_root'] === 'page' )
		) {

			if ( ! isset( $this->root_url ) ) {
				$root_post = get_post( $this->urls_settings['root_page'] );

				if ( $root_post ) {
					$this->root_url = trailingslashit( $this->get_abs_home() ) . $root_post->post_name;
					$this->root_url = trailingslashit( $this->root_url );
				} else {
					$this->root_url = false;
				}
			}

			$ret = strpos( trailingslashit( $url ), $this->root_url ) === 0 ? true : false;
		}

		return $ret;
	}
}