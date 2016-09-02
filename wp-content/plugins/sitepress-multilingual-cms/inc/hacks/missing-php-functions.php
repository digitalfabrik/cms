<?php

if(!function_exists('_cleanup_header_comment')){
    function _cleanup_header_comment($str) {
        return trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $str));
    } 
}

if(!defined('E_DEPRECATED')){ define('E_DEPRECATED', 8192); }

if(!function_exists('esc_textarea')):
    
    function esc_textarea( $text ) {
        $safe_text = esc_html( $text );
        return apply_filters( 'esc_textarea', $safe_text, $text );
    }    
    
endif;

if ( ! function_exists( 'wpml_is_ajax' ) ) {
	/**
	 * wpml_is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @since  3.1.5
	 *         
	 * @return bool
	 */
	function wpml_is_ajax() {
		if ( defined( 'DOING_AJAX' ) ) {
			return true;
		}

		return ( isset( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && wpml_mb_strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) == 'xmlhttprequest' ) ? true : false;
	}
}

if ( ! function_exists( 'is_ajax' ) ) {
	/**
	 * is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @deprecated Deprecated since 3.1.5
	 *
	 * @return bool
	 */
	function is_ajax() {

		// Deprecation notice will be added in a next release
//		_deprecated_function( "WPML " . __FUNCTION__, "3.1.5", "wpml_is_ajax" );

		if ( defined( 'DOING_AJAX' ) ) {
			return true;
		}

		return ( isset( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && wpml_mb_strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) == 'xmlhttprequest' ) ? true : false;
	}
}


/**
 * This file is part of the array_column library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) 2013 Ben Ramsey <http://benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

if (!function_exists('array_column')) {

    /**
     * Returns the values from a single column of the input array, identified by
     * the $columnKey.
     *
     * Optionally, you may provide an $indexKey to index the values in the returned
     * array by the values from the $indexKey column in the input array.
     *
     * @param array $input A multi-dimensional array (record set) from which to pull
     *                     a column of values.
     * @param mixed $columnKey The column of values to return. This value may be the
     *                         integer key of the column you wish to retrieve, or it
     *                         may be the string key name for an associative array.
     * @param mixed $indexKey (Optional.) The column to use as the index/keys for
     *                        the returned array. This value may be the integer key
     *                        of the column, or it may be the string key name.
     * @return array
     */
    function array_column($input = null, $columnKey = null, $indexKey = null)
    {
        // Using func_get_args() in order to check for proper number of
        // parameters and trigger errors exactly as the built-in array_column()
        // does in PHP 5.5.
        $argc = func_num_args();
        $params = func_get_args();

        if ($argc < 2) {
            trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
            return null;
        }

        if (!is_array($params[0])) {
            trigger_error('array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given', E_USER_WARNING);
            return null;
        }

        if (!is_int($params[1])
            && !is_float($params[1])
            && !is_string($params[1])
            && $params[1] !== null
            && !(is_object($params[1]) && method_exists($params[1], '__toString'))
        ) {
            trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        if (isset($params[2])
            && !is_int($params[2])
            && !is_float($params[2])
            && !is_string($params[2])
            && !(is_object($params[2]) && method_exists($params[2], '__toString'))
        ) {
            trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        $paramsInput = $params[0];
        $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;

        $paramsIndexKey = null;
        if (isset($params[2])) {
            if (is_float($params[2]) || is_int($params[2])) {
                $paramsIndexKey = (int) $params[2];
            } else {
                $paramsIndexKey = (string) $params[2];
            }
        }

        $resultArray = array();

        foreach ($paramsInput as $row) {

            $key = $value = null;
            $keySet = $valueSet = false;

            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                $keySet = true;
                $key = (string) $row[$paramsIndexKey];
            }

            if ($paramsColumnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                $valueSet = true;
                $value = $row[$paramsColumnKey];
            }

            if ($valueSet) {
                if ($keySet) {
                    $resultArray[$key] = $value;
                } else {
                    $resultArray[] = $value;
                }
            }

        }

        return $resultArray;
    }

}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		/*
		 * json_encode() has had extra params added over the years.
		 * $options was added in 5.3, and $depth in 5.5.
		 * We need to make sure we call it with the correct arguments.
		 */
		if ( version_compare( PHP_VERSION, '5.5', '>=' ) ) {
			$args = array( $data, $options, $depth );
		} elseif ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
			$args = array( $data, $options );
		} else {
			$args = array( $data );
		}

		$json = call_user_func_array( 'json_encode', $args );

		// If json_encode() was successful, no need to do more sanity checking.
		// ... unless we're in an old version of PHP, and json_encode() returned
		// a string containing 'null'. Then we need to do more sanity checking.
		if ( false !== $json && ( version_compare( PHP_VERSION, '5.5', '>=' ) || false === strpos( $json, 'null' ) ) )  {
			return $json;
		}

		try {
			$args[0] = _wp_json_sanity_check( $data, $depth );
		} catch ( Exception $e ) {
			return false;
		}

		return call_user_func_array( 'json_encode', $args );
	}

	if ( ! function_exists( '_wp_json_sanity_check' ) ) {
		function _wp_json_sanity_check( $data, $depth ) {
			if ( $depth < 0 ) {
				throw new Exception( 'Reached depth limit' );
			}

			if ( is_array( $data ) ) {
				$output = array();
				foreach ( $data as $id => $el ) {
					// Don't forget to sanitize the ID!
					if ( is_string( $id ) ) {
						$clean_id = _wp_json_convert_string( $id );
					} else {
						$clean_id = $id;
					}

					// Check the element type, so that we're only recursing if we really have to.
					if ( is_array( $el ) || is_object( $el ) ) {
						$output[ $clean_id ] = _wp_json_sanity_check( $el, $depth - 1 );
					} elseif ( is_string( $el ) ) {
						$output[ $clean_id ] = _wp_json_convert_string( $el );
					} else {
						$output[ $clean_id ] = $el;
					}
				}
			} elseif ( is_object( $data ) ) {
				$output = new stdClass;
				foreach ( $data as $id => $el ) {
					if ( is_string( $id ) ) {
						$clean_id = _wp_json_convert_string( $id );
					} else {
						$clean_id = $id;
					}

					if ( is_array( $el ) || is_object( $el ) ) {
						$output->$clean_id = _wp_json_sanity_check( $el, $depth - 1 );
					} elseif ( is_string( $el ) ) {
						$output->$clean_id = _wp_json_convert_string( $el );
					} else {
						$output->$clean_id = $el;
					}
				}
			} elseif ( is_string( $data ) ) {
				return _wp_json_convert_string( $data );
			} else {
				return $data;
			}

			return $output;
		}
	}

	if(!function_exists('_wp_json_convert_string')) {
		function _wp_json_convert_string( $string ) {
			static $use_mb = null;
			if ( is_null( $use_mb ) ) {
				$use_mb = function_exists( 'mb_convert_encoding' );
			}

			if ( $use_mb ) {
				$encoding = mb_detect_encoding( $string, mb_detect_order(), true );
				if ( $encoding ) {
					return mb_convert_encoding( $string, 'UTF-8', $encoding );
				} else {
					return mb_convert_encoding( $string, 'UTF-8', 'UTF-8' );
				}
			} else {
				return wp_check_invalid_utf8( $string, true );
			}
		}
	}
}

if ( ! function_exists( 'array_replace_recursive' ) ) {
	function array_replace_recursive( $array, $array1 ) {
		// handle the arguments, merge one by one
		$args  = func_get_args();
		$array = $args[ 0 ];
		if ( ! is_array( $array ) ) {
			return $array;
		}
		$args_count = count( $args );
		for ( $i = 1; $i < $args_count; $i ++ ) {
			if ( is_array( $args[ $i ] ) ) {
				$array = array_replace_recursive_recurse( $array, $args[ $i ] );
			}
		}

		return $array;
	}

	function array_replace_recursive_recurse( $array, $array1 ) {
		foreach ( $array1 as $key => $value ) {
			// create new key in $array, if it is empty or not an array
			if ( ! isset( $array[ $key ] ) || ( isset( $array[ $key ] ) && ! is_array( $array[ $key ] ) ) ) {
				$array[ $key ] = array();
			}

			// overwrite the value in the base array
			if ( is_array( $value ) ) {
				$value = array_replace_recursive_recurse( $array[ $key ], $value );
			}
			$array[ $key ] = $value;
		}

		return $array;
	}
}

if ( ! function_exists( 'http_build_url' ) ) {
	/**
	 * http_build_url
	 * Stand alone version of http_build_url (http://php.net/manual/en/function.http-build-url.php)
	 * Based on buggy and inefficient version I found at http://www.mediafire.com/?zjry3tynkg5 by tycoonmaster[at]gmail[dot]com
	 * @author    Chris Nasr (chris[at]fuelforthefire[dot]ca)
	 * @copyright Fuel for the Fire
	 * @package   http
	 * @version   0.1
	 * @created   2012-07-26
	 */

	// Define constants
	define( 'HTTP_URL_REPLACE', 0x0001 );    // Replace every part of the first URL when there's one of the second URL
	define( 'HTTP_URL_JOIN_PATH', 0x0002 );    // Join relative paths
	define( 'HTTP_URL_JOIN_QUERY', 0x0004 );    // Join query strings
	define( 'HTTP_URL_STRIP_USER', 0x0008 );    // Strip any user authentication information
	define( 'HTTP_URL_STRIP_PASS', 0x0010 );    // Strip any password authentication information
	define( 'HTTP_URL_STRIP_PORT', 0x0020 );    // Strip explicit port numbers
	define( 'HTTP_URL_STRIP_PATH', 0x0040 );    // Strip complete path
	define( 'HTTP_URL_STRIP_QUERY', 0x0080 );    // Strip query string
	define( 'HTTP_URL_STRIP_FRAGMENT', 0x0100 );    // Strip any fragments (#identifier)

	// Combination constants
	define( 'HTTP_URL_STRIP_AUTH', HTTP_URL_STRIP_USER | HTTP_URL_STRIP_PASS );
	define( 'HTTP_URL_STRIP_ALL', HTTP_URL_STRIP_AUTH | HTTP_URL_STRIP_PORT | HTTP_URL_STRIP_QUERY | HTTP_URL_STRIP_FRAGMENT );

	/**
	 * HTTP Build URL
	 * Combines arrays in the form of parse_url() into a new string based on specific options
	 * @name               http_build_url
	 *
	 * @param string|array $url      The existing URL as a string or result from parse_url
	 * @param string|array $parts    Same as $url
	 * @param int          $flags    URLs are combined based on these
	 * @param array        &$new_url If set, filled with array version of new url
	 *
	 * @return string
	 */
	function http_build_url(/*string|array*/
		$url, /*string|array*/
		$parts = array(), /*int*/
		$flags = HTTP_URL_REPLACE, /*array*/
		&$new_url = false
	) {
		// If the $url is a string
		if ( is_string( $url ) ) {
			$url = parse_url( $url );
		}

		// If the $parts is a string
		if ( is_string( $parts ) ) {
			$parts = parse_url( $parts );
		}

		// Scheme and Host are always replaced
		if ( isset( $parts['scheme'] ) ) {
			$url['scheme'] = $parts['scheme'];
		}
		if ( isset( $parts['host'] ) ) {
			$url['host'] = $parts['host'];
		}

		// (If applicable) Replace the original URL with it's new parts
		if ( HTTP_URL_REPLACE & $flags ) {
			// Go through each possible key
			foreach ( array( 'user', 'pass', 'port', 'path', 'query', 'fragment' ) as $key ) {
				// If it's set in $parts, replace it in $url
				if ( isset( $parts[ $key ] ) ) {
					$url[ $key ] = $parts[ $key ];
				}
			}
		} else {
			// Join the original URL path with the new path
			if ( isset( $parts['path'] ) && ( HTTP_URL_JOIN_PATH & $flags ) ) {
				if ( isset( $url['path'] ) && $url['path'] != '' ) {
					// If the URL doesn't start with a slash, we need to merge
					if ( $url['path'][0] !== '/' ) {
						// If the path ends with a slash, store as is
						if ( '/' === $parts['path'][ strlen( $parts['path'] ) - 1 ] ) {
							$sBasePath = $parts['path'];
						} // Else trim off the file
						else {
							// Get just the base directory
							$sBasePath = dirname( $parts['path'] );
						}

						// If it's empty
						if ( '' == $sBasePath ) {
							$sBasePath = '/';
						}

						// Add the two together
						$url['path'] = $sBasePath . $url['path'];

						// Free memory
						unset( $sBasePath );
					}

					if ( false !== strpos( $url['path'], './' ) ) {
						// Remove any '../' and their directories
						while ( preg_match( '/\w+\/\.\.\//', $url['path'] ) ) {
							$url['path'] = preg_replace( '/\w+\/\.\.\//', '', $url['path'] );
						}

						// Remove any './'
						$url['path'] = str_replace( './', '', $url['path'] );
					}
				} else {
					$url['path'] = $parts['path'];
				}
			}

			// Join the original query string with the new query string
			if ( isset( $parts['query'] ) && ( HTTP_URL_JOIN_QUERY & $flags ) ) {
				if ( isset( $url['query'] ) ) {
					$url['query'] .= '&' . $parts['query'];
				} else {
					$url['query'] = $parts['query'];
				}
			}
		}

		// Strips all the applicable sections of the URL
		if ( HTTP_URL_STRIP_USER & $flags ) {
			unset( $url['user'] );
		}
		if ( HTTP_URL_STRIP_PASS & $flags ) {
			unset( $url['pass'] );
		}
		if ( HTTP_URL_STRIP_PORT & $flags ) {
			unset( $url['port'] );
		}
		if ( HTTP_URL_STRIP_PATH & $flags ) {
			unset( $url['path'] );
		}
		if ( HTTP_URL_STRIP_QUERY & $flags ) {
			unset( $url['query'] );
		}
		if ( HTTP_URL_STRIP_FRAGMENT & $flags ) {
			unset( $url['fragment'] );
		}

		// Store the new associative array in $new_url
		$new_url = $url;

		// Combine the new elements into a string and return it
		return ( ( isset( $url['scheme'] ) ) ? $url['scheme'] . '://' : '' )
		       . ( ( isset( $url['user'] ) ) ? $url['user'] . ( ( isset( $url['pass'] ) ) ? ':' . $url['pass'] : '' ) . '@' : '' )
		       . ( ( isset( $url['host'] ) ) ? $url['host'] : '' )
		       . ( ( isset( $url['port'] ) ) ? ':' . $url['port'] : '' )
		       . ( ( isset( $url['path'] ) ) ? $url['path'] : '' )
		       . ( ( isset( $url['query'] ) ) ? '?' . $url['query'] : '' )
		       . ( ( isset( $url['fragment'] ) ) ? '#' . $url['fragment'] : '' );
	}
}