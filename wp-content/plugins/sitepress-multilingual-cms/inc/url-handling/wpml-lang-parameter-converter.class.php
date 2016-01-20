<?php

class WPML_Lang_Parameter_Converter extends WPML_URL_Converter {

	public function __construct( $default_language, $hidden_languages, &$wpml_wp_api ) {
		parent::__construct( $default_language, $hidden_languages, $wpml_wp_api );
		add_filter( 'request', array( $this, 'request_filter' ) );
		add_filter( 'get_pagenum_link', array( $this, 'paginated_url_filter' ) );
		add_filter( 'wp_link_pages_link', array( $this, 'paginated_link_filter' ) );
	}

	function request_filter( $request ) {
		if ( !defined( 'WP_ADMIN' ) && isset( $request[ 'lang' ] ) ) {
			// Count the parameters that have settings and remove our 'lang ' setting it's the only one.
			// This is required so that home page detection works for other languages.
			$count = 0;
			foreach ( $request as $data ) {
				if ( $data !== '' ) {
					$count += 1;
				}
			}
			if ( $count == 1 ) {
				unset( $request[ 'lang' ] );
			}
		}

		return $request;
	}

	/**
	 * Filters the pagination links on taxonomy archives to properly have the language parameter after the URI.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	function paginated_url_filter( $url ) {
		$parts     = explode( '?', $url );
		$last_part = count( $parts ) > 2 ? array_pop( $parts ) : "";
		$url       = join( '?', $parts );
		$url       = preg_replace( '#(.+?)(/\?|\?)(.*?)(/.+?[/$|$])$#', '$1$4$5?$3', $url );
		$url       = preg_replace( '#(\?.+)(%2F|\/)$#', '$1', $url );

		return $url . ( $last_part !== "" && strpos( $url, '?' . $last_part ) === false ? '&' . $last_part : '' );
	}

	/**
	 * Filters the pagination links on paginated posts and pages, acting on the links html
	 * output containing the anchor tag the link is a property of.
	 *
	 * @param string $link_html
	 *
	 * @return string
	 *
	 * @hook wp_link_pages_link
	 */
	function paginated_link_filter( $link_html ) {

		return preg_replace('#"([^"].+?)(/\?|\?)(.+)(/\d/)"#', '"$1$4?$3"', $link_html);
	}

	protected function get_lang_from_url_string( $url ) {

		return $this->lang_by_param ( $url, false );
	}

	protected function convert_url_string( $source_url, $lang_code ) {
		$old_lang_code = $this->get_lang_from_url_string ( $source_url );
		$lang_code     = (bool) $lang_code === false ? $this->default_language : $lang_code;
		$lang_code     = $lang_code === $this->default_language ? "" : $lang_code;
		if ( (bool) $old_lang_code !== false ) {
			$replace = $lang_code === "" ? "" : '?lang=' . $lang_code;
			$source_url     = str_replace ( '?lang=' . $old_lang_code, $replace, $source_url );
			$replace = str_replace ( '?', '&', $replace );
			$source_url     = str_replace ( '&lang=' . $old_lang_code, $replace, $source_url );
			$source_url     = strpos($source_url, '?') === false ? $source_url . '?lang=' . $lang_code : $source_url;
		}

		if ( strpos ( $source_url, 'lang=' . $lang_code ) === false ) {
			$source_url .= ( strpos ( $source_url, '?' ) === false ? '?' : '&' ) . 'lang=' . $lang_code;
		}

		$source_url = str_replace ( '?lang=&', '?', $source_url );
		$source_url = str_replace ( '&lang=&', '&', $source_url );
		$source_url = str_replace ( '&lang=/', '', trailingslashit ( $source_url ) );
		$source_url = str_replace ( '?lang=/', '', $source_url );
		$source_url = str_replace ( '//?', '/?', $source_url );

		return untrailingslashit ( $source_url );
	}
}