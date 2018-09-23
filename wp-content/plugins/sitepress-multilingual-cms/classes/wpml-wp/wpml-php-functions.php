<?php

/**
 * @deprecated Most of the methods in this class are deprecated.
 *             You can work around these deprecations by following one or more of the following suggestions:
 *             - Use [`\WP_Mock`](https://github.com/10up/wp_mock)
 *             - Use [`\Mockery¡](http://docs.mockery.io/en/latest/)
 *             - Write tests which should run with different scenario using the [`@requires` annotation](https://phpunit.readthedocs.io/en/7.3/incomplete-and-skipped-tests.html#incomplete-and-skipped-tests-requires-tables-api)
 *             - [Run tests in separate processes](https://phpunit.de/manual/6.5/en/appendixes.annotations.html#appendixes.annotations.runTestsInSeparateProcesses)
 *             - [Run specific tests in separate processes](https://phpunit.de/manual/6.5/en/appendixes.annotations.html#appendixes.annotations.runInSeparateProcess)
 *
 * Wrapper class for basic PHP functions
 */
class WPML_PHP_Functions {

	/**
	 * @deprecated @see \WPML_PHP_Functions
	 *
	 * Wrapper around PHP constant defined
	 *
	 * @param string $constant_name
	 *
	 * @return bool
	 */
	public function defined( $constant_name ) {
		return defined( $constant_name );
	}

	/**
	 * @deprecated @see \WPML_PHP_Functions
	 * Wrapper around PHP constant lookup
	 *
	 * @param string $constant_name
	 *
	 * @return string|int
	 */
	public function constant( $constant_name ) {
		return $this->defined( $constant_name ) ? constant( $constant_name ) : null;
	}

	/**
	 * @deprecated @see \WPML_PHP_Functions
	 * @param string $function_name The function name, as a string.
	 *
	 * @return bool true if <i>function_name</i> exists and is a function, false otherwise.
	 * This function will return false for constructs, such as <b>include_once</b> and <b>echo</b>.
	 * @return bool
	 */
	public function function_exists( $function_name ) {
		return function_exists( $function_name );
	}

	/**
	 * @deprecated @see \WPML_PHP_Functions
	 * @param string $class_name The class name. The name is matched in a case-insensitive manner.
	 * @param bool   $autoload   [optional] Whether or not to call &link.autoload; by default.
	 *
	 * @return bool true if <i>class_name</i> is a defined class, false otherwise.
	 * @return bool
	 */
	public function class_exists( $class_name, $autoload = true ) {
		return class_exists( $class_name, $autoload );
	}

	/**
	 * @deprecated @see \WPML_PHP_Functions
	 * @param string $name The extension name
	 *
	 * @return bool true if the extension identified by <i>name</i> is loaded, false otherwise.
	 */
	public function extension_loaded( $name ) {
		return extension_loaded( $name );
	}

	/**
	 * @deprecated @see \WPML_PHP_Functions
	 * @param $string
	 *
	 * @return string
	 */
	public function mb_strtolower( $string ) {
		if ( function_exists( 'mb_strtolower' ) ) {
			return mb_strtolower( $string );
		}

		return strtolower( $string );
	}

	/**
	 * @deprecated @see \WPML_PHP_Functions
	 * Wrapper for \phpversion()
	 *
	 * * @param string $extension (optional)
	 *
	 * @return string
	 */
	public function phpversion( $extension = null ) {
		if ( defined( 'PHP_VERSION' ) ) {
			return PHP_VERSION;
		} else {
			return phpversion( $extension );
		}
	}

	/**
	 * @deprecated @see \WPML_PHP_Functions
	 * Compares two "PHP-standardized" version number strings
	 * @see \WPML_WP_API::version_compare
	 *
	 * @param string $version1
	 * @param string $version2
	 * @param null   $operator
	 *
	 * @return mixed
	 */
	public function version_compare( $version1, $version2, $operator = null ) {
		return version_compare( $version1, $version2, $operator );
	}

	/**
	 * @param array $array
	 * @param int   $sort_flags
	 *
	 * @return array
	 */
	public function array_unique( $array, $sort_flags = SORT_REGULAR ) {
		if ( version_compare( $this->phpversion(), '5.2.9', '>=' ) ) {
			return array_unique( $array, $sort_flags );
		} else {
			return $this->array_unique_fallback( $array, true );
		}
	}

	/**
	 * @param $array
	 * @param $keep_key_assoc
	 *
	 * @return array
	 */
	private function array_unique_fallback( $array, $keep_key_assoc ) {
		$duplicate_keys = array();
		$tmp            = array();

		foreach ( $array as $key => $val ) {
			// convert objects to arrays, in_array() does not support objects
			if ( is_object( $val ) ) {
				$val = (array) $val;
			}

			if ( ! in_array( $val, $tmp ) ) {
				$tmp[] = $val;
			} else {
				$duplicate_keys[] = $key;
			}
		}

		foreach ( $duplicate_keys as $key ) {
			unset( $array[ $key ] );
		}

		return $keep_key_assoc ? $array : array_values( $array );
	}

	/**
	 * @deprecated @see \WPML_PHP_Functions
	 * @param string $message
	 * @param int    $message_type
	 * @param string $destination
	 * @param string $extra_headers
	 *
	 * @return bool
	 */
	public function error_log( $message, $message_type = null, $destination = null, $extra_headers = null ) {
		return error_log( $message, $message_type, $destination, $extra_headers );
	}
}
