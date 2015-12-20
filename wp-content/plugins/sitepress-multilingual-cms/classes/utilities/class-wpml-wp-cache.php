<?php

class WPML_WP_Cache {

	private $group;
	
	public function __construct( $group = '' ) {
		$this->group = $group;
	}
	
	public function get( $key, &$found ) {
		$value = wp_cache_get( $key, $this->group );
		if ( is_array( $value) && array_key_exists( 'data', $value ) ) {
			// we know that we have set something in the cache.
			$found = true;
			return $value[ 'data' ];
		} else {
			$found = false;
			return $value;
		}
	}
	
	public function set( $key, $value, $expire = 0 ) {
		
		// Save $value in an array. We need to do this because W3TC 0.9.4.1 doesn't
		// set the $found value when fetching data.
		
		wp_cache_set( $key, array( 'data' => $value ), $this->group, $expire );
	}
}
