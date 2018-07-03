<?php
/**
 * Project: User Role Editor plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+
 * 
 * Work with MySQL named lock to provide mutex emulation
 * Code was built on the base off the WPMutex class from the Broken Link Checker plugin (Janis Elsts).
 * 
 */	
class URE_Mutex {
	/**
	 * Get an exclusive named lock.
	 * 
	 * @param string $name 
	 * @param integer $timeout 
	 * @param bool $network_wide 
	 * @return bool 
	 */
	static public function get($name, $timeout = 0, $network_wide = false){
    global $wpdb;
    
    if (!$network_wide) {
        $name = self::get_private_name($name);
    }
    
    $state = $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, %d)', $name, $timeout));
    
    return $state == 1;
	}	
 // end of get()
	
 
	/**
	 * Release a named lock.
	 * 
	 * @param string $name 
	 * @param bool $network_wide 
	 * @return bool
	 */
	static public function release($name, $network_wide = false){
    global $wpdb;
    
    if (!$network_wide) {
        $name = self::get_private_name($name);
    }		
    
    $released = $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $name));
    
    return $released == 1;
	}
 // end of release()
 
	
	/**
	 * Given a generic lock name, create a new one that's unique to the current blog.
	 * 
	 * @access private
	 * 
	 * @param string $name
	 * @return string
	 */
	static private function get_private_name($name) {
    global $current_blog;
    
    if ( function_exists('is_multisite') && is_multisite() && isset($current_blog->blog_id) ){
        $name .= '-blog-' . $current_blog->blog_id;
    }
    
    return $name;
	}
 // end of get_private_name()
 
}
// end of URE_Mutex class