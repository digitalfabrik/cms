<?php

class WPML_Cookie {

	public function set_cookie( $name, $value, $expires, $path, $domain ) {
		setcookie( $name, $value, $expires, $path, $domain );
	}
	
	public function get_cookie( $name ) {
		if ( isset( $_COOKIE[ $name ] ) ) {
			return $_COOKIE[ $name ];
		} else {
			return '';
		}
	}

	/**
	 * simple wrapper for \headers_sent
	 *
	 * @return bool
	 */
	public function headers_sent() {

		return headers_sent();
	}
}
