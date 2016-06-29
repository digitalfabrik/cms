<?php
/**
 * @param string $input
 * @param string $default_if_invalid
 *
 * @return string
 */
function wpml_sanitize_hex_color( $input, $default_if_invalid = '' ) {
	$input  = sanitize_text_field( $input );
	$result = $input;
	if ( ! is_string( $input ) || ! wpml_is_valid_hex_color( $input ) ) {
		$result = $default_if_invalid;
	}

	return $result;
}

function wpml_sanitize_hex_color_array( $input, $default_if_invalid = '', $bypass_non_strings = true, $recursive = false ) {
	$result = $input;
	if ( is_array( $input ) ) {
		$result = array();
		foreach ( $input as $key => $value ) {
			if ( is_array( $value ) && $recursive ) {
				$result[ $key ] = wpml_sanitize_hex_color_array( $value, $default_if_invalid, $recursive );
			} elseif ( is_string( $value ) ) {
				$result[ $key ] = wpml_sanitize_hex_color( $value, $default_if_invalid );
			} elseif ( $bypass_non_strings ) {
				$result[ $key ] = $value;
			}
		}
	}

	return $result;
}

/**
 * @param string $input
 *
 * @return bool
 */
function wpml_is_valid_hex_color( $input ) {
	return preg_match( '/' . wpml_get_valid_hex_color_pattern() . '/i', $input );
}

function wpml_get_valid_hex_color_pattern() {
	return '(^#[a-fA-F0-9]{6}$)|(^#[a-fA-F0-9]{3}$)';
}