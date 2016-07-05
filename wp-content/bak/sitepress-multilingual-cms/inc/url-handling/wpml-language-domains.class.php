<?php

class WPML_Language_Domains extends WPML_SP_User {

	public function validate_language_per_directory( $language_code ) {

		return $this->validate_domain_networking ( (string) filter_input ( INPUT_POST, 'url' ), $language_code );
	}

	public function validate_domain_networking( $posted_url, $code = false ) {
		$string_valid = $this->validate_url_string( $posted_url );

		if ( $string_valid === true ) {
			$url_glue = false === strpos( $posted_url, '?' ) ? '?' : '&';
			$url      = trailingslashit( $posted_url ) . ( $code ? '/' . $code . '/'
					: '' ) . $url_glue . '____icl_validate_domain=1';
			$client   = new WP_Http();
			$response = $client->request( $url, 'timeout=15' );
		}

		return isset( $response )
			   && ! is_wp_error( $response )
			   && ( $response[ 'response' ][ 'code' ] == '200' )
			   && ( ( $response[ 'body' ] === '<!--' . untrailingslashit( get_home_url() ) . '-->' )
					|| $response[ 'body' ] === '<!--' . untrailingslashit( get_site_url() ) . '-->' )
			? 1 : 0;
	}

	public function render_domains_options() {
		$default_language = $this->sitepress->get_default_language();
		$active_languages = $this->sitepress->get_active_languages();
		$output           = '<table class="language_domains">';
		foreach ( $active_languages as $lang ) {
			$home = get_site_url ();
			$suggested_url = $this->render_suggested_url ( $home, $lang );
			$output .= '<tr><td>' . $lang[ 'display_name' ] . '</td>';
			if ( $lang[ 'code' ] === $default_language ) {
				$output .= '<td id="icl_ln_home">' . $home . '</td><td>&nbsp;</td><td>&nbsp;</td>';
			} else {
				$output .= '<td>
									<input
										type="text"
										id="language_domain_' . $lang['code'] . '"
										name="language_domains[' . $lang['code'] . ']"
										value="' . $suggested_url . '"
										data-language="' . $lang['code'] . '"
										size="40"
				           />
				           </td>
				           <td id="icl_validation_result_' . $lang['code'] . '">
				           	<label>
				           		<input
				           			class="validate_language_domain"
				           			type="checkbox"
				           			name="validate_language_domains[]"
				           			value="' . $lang['code'] . '"
				           			checked="checked"
				           		/> ' . __( 'Validate on save', 'sitepress' ) . '</label>
				           </td>
				           <td>
				           	<span id="ajx_ld_' . $lang['code'] . '"></span>
				           </td>';
			}
			$output .= '</tr>';
		}

		return $output . '</table>';
	}

	private function render_suggested_url( $home, $lang ) {
		$url_parts        = parse_url( $home );
		$suggested_url    = $home;
		$language_domains = $this->sitepress->get_setting( 'language_domains', false );
		$default_language = $this->sitepress->get_default_language();
		if ( $lang['code'] !== $default_language ) {
			if ( isset( $language_domains[ $lang['code'] ] ) ) {
				$suggested_url = $language_domains[ $lang['code'] ];
			} elseif ( isset( $url_parts['scheme'] ) && isset( $url_parts['host'] ) ) {
				$exp           = explode( '.', $url_parts['host'] );
				$suggested_url = $url_parts['scheme'] . '://' . $lang['code'] . '.';
				array_shift( $exp );
				$suggested_url .= count( $exp ) < 2 ? $url_parts['host'] : join( '.', $exp );
				$suggested_url .= isset( $url_parts['path'] ) ? $url_parts['path'] : '';
			}
		}

		return $suggested_url;
	}

	private function validate_url_string( $url ) {
		$url_parts = parse_url ( $url );

		return isset( $url_parts[ 'scheme' ] ) && isset( $url_parts[ 'host' ] );
	}
}