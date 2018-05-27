<?php
/**
 * An interface for the dbem_data option stored in wp_options as a serialized array. 
 * This option can hold various information which can be stored in one record rather than individual records in wp_options.
 * The functions in this class deal directly with that dbem_data option as if it was the wp_options table itself, and therefore
 * have similarities to the get_option and update_option functions. 
 * @since 5.8.2.0
 *
 */
class EM_Options {
	
	/**
	 * Get a specific setting form the EM options array. If no value is set, an empty array is provided by default.
	 * @param string $option_name
	 * @param mixed $default the default value to return
	 * @param boolean $site if set to true it'll retrieve a site option in MultiSite instead
	 * @return mixed
	 */
	public static function get( $option_name, $default = array(), $site = false ){
		$data = $site ? get_site_option('dbem_data') : get_option('dbem_data');
		if( !empty($data[$option_name]) ){
			return $data[$option_name];
		}else{
			return $default;
		}
	}
	
	/**
	 * Set a value in the EM options array. Returns result of storage, which may be false if no changes are made.
	 * @param string $option_name
	 * @param mixed $option_value
	 * @param boolean $site if set to true it'll retrieve a site option in MultiSite instead
	 * @return boolean
	 */
	public static function set( $option_name, $option_value, $site = false ){
		$data = $site ? get_site_option('dbem_data') : get_option('dbem_data');
		$data[$option_name] = $option_value;
		return $site ? update_site_option('dbem_data', $data) : update_option('dbem_data', $data);
	}
	
	/**
	 * Adds a value to an specific key in the EM options array, and assumes the option name is an array.
	 * Returns true on success or false saving failed or if no changes made.
	 * @param string $option_name
	 * @param string $option_key
	 * @param mixed $option_value
	 * @param boolean $site
	 * @return boolean
	 */
	public static function add( $option_name, $option_key, $option_value, $site = false ){
		$data = $site ? get_site_option('dbem_data') : get_option('dbem_data');
		if( empty($data[$option_name]) ){
			$data[$option_name] = array( $option_key => $option_value );
		}else{
			$data[$option_name][$option_key] = $option_value;
		}
		return $site ? update_site_option('dbem_data', $data) : update_option('dbem_data', $data);
	}
	
	/**
	 * Removes an item from an array in the EM options array, it assumes the supplied option name is an array.
	 *
	 * @param string $option_name
	 * @param string $option_key
	 * @param string $site
	 * @return boolean
	 */
	public static function remove( $option_name, $option_key, $site = false ){
		$data = $site ? get_site_option('dbem_data') : get_option('dbem_data');
		if( !empty($data[$option_name][$option_key]) ){
			unset($data[$option_name][$option_key]);
			if( empty($data[$option_name]) ) unset($data[$option_name]);
			return $site ? update_site_option('dbem_data', $data) : update_option('dbem_data', $data);
		}
		return false;
	}
	
	/**
	 * @see EM_Options::get()
	 */
	public static function site_get( $option_name, $default = array() ){
		return self::get( $option_name, $default, true );
	}
	
	/**
	 * @see EM_Options::set()
	 */
	public static function site_set( $option_name, $option_value ){
		return self::set( $option_name, $option_value, true );
	}
	
	/**
	 * @see EM_Options::add()
	 */
	public static function site_add( $option_name, $option_key, $option_value ){
		return self::add( $option_name, $option_key, $option_value, true );
	}
	
	/**
	 * @see EM_Options::remove()
	 */
	public static function site_remove( $option_name, $option_key ){
		return self::remove( $option_name, $option_key, true );
	}
}