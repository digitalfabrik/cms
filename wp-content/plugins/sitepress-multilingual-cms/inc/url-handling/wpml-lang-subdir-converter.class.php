<?php

class WPML_Lang_Subdir_Converter extends WPML_URL_Converter {

	private $dir_default;

	public function __construct( $dir_default, $default_language, $hidden_languages ) {
		parent::__construct ( $default_language, $hidden_languages );
		$this->dir_default = $dir_default;
	}

	protected function get_lang_from_url_string( $url ) {

		$url = wpml_strip_subdir_from_url ( $url );

		if ( strpos ( $url, 'http://' ) === 0 || strpos ( $url, 'https://' ) === 0 ) {
			$url_path = parse_url ( $url, PHP_URL_PATH );
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

		return $lang && in_array ( $lang, $this->active_languages )
			? $lang : ( $this->dir_default ? null : $this->default_language );
	}

	protected function validate_language( $language, $url ) {
		if ( !( $language === null && $this->dir_default && !$this->is_url_admin ( $url ) ) ) {
			$language = parent::validate_language ( $language, $url );
		}

		return $language;
	}

	protected function convert_url_string( $url, $code ) {
		$url               = strpos( $url, '?' ) === false ? trailingslashit( $url ) : $url;
		$url               = strpos( $url, '?' ) !== false && strpos( $url, '/?' ) === false
			? str_replace( '?', '/?', $url ) : $url;
		$absolute_home_url = trailingslashit( preg_replace( '#^(http|https)://#', '', $this->get_abs_home() ) );
		$code              = ! $this->dir_default && $code === $this->default_language ? '' : $code;
		$current_language  = $this->get_lang_from_url_string( $url );
		$current_language  = ! $this->dir_default && $current_language === $this->default_language ? '' : $current_language;
		$absolute_home_url = strpos( $url, $absolute_home_url ) === false ? trailingslashit( get_option( 'home' ) ) : $absolute_home_url;
		$url               = str_replace(
			trailingslashit( $absolute_home_url . $current_language ),
			$code ? ( $absolute_home_url . $code . '/' ) : trailingslashit( $absolute_home_url ),
			$url
		);
		$url = str_replace( '/' . $code . '//', '/' . $code . '/', $url );

		return untrailingslashit( $url );
	}
}