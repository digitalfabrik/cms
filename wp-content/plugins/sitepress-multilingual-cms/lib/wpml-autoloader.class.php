<?php

class WPML_AutoLoader {
	private static $accepted_prefixes;
	private static $classes_base_folder;
	private static $include_root;
	private static $known_classes;

	public static function add_known_class( $class, $file ) {
		self::$known_classes[ $class ] = $file;
	}

	public static function autoload( $class ) {
		$file = self::get_file( $class );

		if ( $file ) {
			/** @noinspection PhpIncludeInspection */
			require_once $file;
		} else {
			self::log( 'Class `' . $class . '` not found.' );
		}
	}

	/**
	 * @param $class
	 *
	 * @return mixed|null|string
	 */
	protected static function get_file( $class ) {
		$file = null;
		if ( self::is_accepted_class( $class ) ) {
			$file = self::get_file_from_known_classes( $class );
			if ( null == $file ) {
				$file = self::get_file_from_class_name( $class );
			}
		}

		return $file;
	}

	private static function log( $message ) {
		if ( defined( 'WPML_AUTO_LOADER_LOG' ) && WPML_AUTO_LOADER_LOG ) {
			error_log( '[' . get_called_class() . '] ' . $message );
		}
	}

	private static function is_accepted_class( $class ) {
		foreach ( self::$accepted_prefixes as $accepted_prefix ) {
			if ( 0 === strpos( $class, $accepted_prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $class
	 *
	 * @return string|null
	 */
	protected static function get_file_from_known_classes( $class ) {
		$file = null;
		if ( isset( self::$known_classes[ $class ] ) && is_file( self::$known_classes[ $class ] ) ) {
			$file = self::$known_classes[ $class ];
		}

		return $file;
	}

	/**
	 * @param string $class
	 *
	 * @return string|null
	 */
	private static function get_file_from_class_name( $class ) {
		$file      = null;
		$file_name = 'class-' . strtolower( str_replace( array( '_', "\0" ), array( '-', '' ), $class ) . '.php' );

		if ( self::$include_root ) {
			$base_path          = self::get_base_path( false );
			$possible_full_path = $base_path . $file_name;
			if ( is_file( $possible_full_path ) ) {
				$file = $possible_full_path;
			}
		}

		if ( ! $file ) {
			$possible_file = self::get_file_from_path( $file_name, '/', true );
			if ( is_file( $possible_file ) ) {
				$file = $possible_file;
			}
		}

		return $file;
	}

	/**
	 * @param string|bool $with_base_folder
	 * @param string      $path
	 *
	 * @return string
	 */
	private static function get_base_path( $with_base_folder = true, $path = '' ) {
		$base_path = dirname( dirname( __FILE__ ) ) . '/';
		if ( $with_base_folder ) {
			$base_path .= self::$classes_base_folder;
		}
		if ( $path ) {
			$base_path .= $path;
		}

		return $base_path;
	}

	private static function get_file_from_path( $file_name, $path = '', $deep = false ) {
		$file               = null;
		$base_path          = self::get_base_path( true, $path );
		$possible_full_path = $base_path . '/' . $file_name;
		if ( is_file( $possible_full_path ) ) {
			$file = $possible_full_path;
		} else {
			$sub_folders = glob( $base_path . '/*', GLOB_ONLYDIR );
			if ( $sub_folders ) {
				foreach ( $sub_folders as $sub_folder_path ) {
					$sub_folder = substr( $sub_folder_path, strlen( $base_path ) );
					$found_file = self::get_file_from_path( $file_name, $sub_folder, $deep );
					if ( null != $found_file ) {
						$file = $found_file;
						break;
					}
				}
			}
		}

		return $file;
	}

	public static function register( $include_root = false, $prepend = false ) {
		self::$accepted_prefixes   = array( 'WPML' );
		self::$classes_base_folder = 'classes';
		self::$known_classes       = array();
		self::$include_root        = $include_root;

		if ( version_compare( phpversion(), '5.3.0', '>=' ) ) {
			spl_autoload_register( array( new self, 'autoload' ), true, $prepend );
		} else {
			spl_autoload_register( array( new self, 'autoload' ) );
		}
	}
}

WPML_AutoLoader::register();
