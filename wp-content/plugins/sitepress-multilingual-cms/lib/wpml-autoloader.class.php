<?php

class WPML_Autoloader {
	private static $accepted_prefixes = array( 'WPML' );

	public static function autoload( $class ) {
		if ( self::is_accepted_class( $class ) ) {
			$file = self::get_file_from_class_name( $class );
			if ( is_file( $file ) ) {
				/** @noinspection PhpIncludeInspection */
				require_once $file;
			}
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

	private static function get_file_from_class_name( $class ) {
		return dirname( __FILE__ ) . '/../class-' . strtolower( str_replace( array( '_', "\0" ), array( '-', '' ), $class ) . '.php' );
	}

	public static function register( $prepend = false ) {
		if ( version_compare( phpversion(), '5.3.0', '>=' ) ) {
			spl_autoload_register( array( new self, 'autoload' ), true, $prepend );
		} else {
			spl_autoload_register( array( new self, 'autoload' ) );
		}
	}
}
WPML_Autoloader::register();