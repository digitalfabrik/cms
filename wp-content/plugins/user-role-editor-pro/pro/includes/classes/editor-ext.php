<?php
/**
 * Role capabilities editor class - extended
 *
 * @package    User-Role-Editor-Pro
 * @subpackage Editor
 * @author     Vladimir Garagulya <support@role-editor.com>
 * @copyright  Copyright (c) 2010 - 2018, Vladimir Garagulia
 **/
class URE_Editor_Ext {


    /**
     * If existing user was not added to the current blog - add him
     * It's used in the Network wide user update, when obviously there would be blogs to which user was not added yet.
     * @global type $blog_id
     * @param int $user_id
     * @return bool
     */
    public static function add_user_to_current_blog( $user_id ) {
        global $blog_id;
        
        $lib = URE_Lib_Pro::get_instance();
        $multisite = $lib->get('multisite');
        if (!$multisite) {
            return false;
        }
        
        $result = true;
        if ( is_network_admin() ) {
            if ( !array_key_exists( $blog_id, get_blogs_of_user( $user_id ) ) ) {
                $result = add_existing_user_to_blog( array( 'user_id' => $user->ID, 'role' => 'subscriber' ) );
            }
        }

        return $result;
    }
    // end of add_user_to_current_blog(()
    
    
    /** Get user roles and capabilities from the main blog
     * 
     * @param int $user_id
     * @return boolean
     */
    private static function get_user_caps_from_main_blog( $user_id ) {
        global $wpdb;
        
        $meta_key = $wpdb->prefix.'capabilities';
        $query = $wpdb->prepare(
                    "SELECT meta_value
                        FROM {$wpdb->usermeta}
                        WHERE user_id=%d and meta_key=%s
                        LIMIT 0, 1",
                    array($user_id, $meta_key)
                            );
        $user_caps = $wpdb->get_var($query);
        if (empty($user_caps)) {
            return false;
        }
        
        return $user_caps;           
    }
    // end of get_user_caps_from_main_blog()
    
    
    private static function update_user_caps_for_blog( $blog_id, $user_id, $user_caps ) {
        global $wpdb;
        
        $meta_key = $wpdb->prefix.$blog_id.'_capabilities';
        $query = $wpdb->prepare(
                    "UPDATE {$wpdb->usermeta}
                        SET meta_value=%s
                        WHERE user_id=%d and meta_key=%s
                        LIMIT 1",
                    array($user_caps, $user_id, $meta_key)
                            );
        $result = $wpdb->query( $query );
        
        return $result;
    }
    // end of update_user_caps_for_blog()
    
    
    public static function network_update_user( $user_id ) {
                        
        $lib = URE_Lib_Pro::get_instance();
        $multisite = $lib->get('multisite');
        if (!$multisite) {
            return true;
        }
        
        $editor = URE_Editor::get_instance();
        $apply_to_all = $editor->get('apply_to_all');
        if (!$apply_to_all) {
            return true;
        }
        
        $user_caps = self::get_user_caps_from_main_blog($user_id);
        $user_blogs = get_blogs_of_user($user_id); // list of blogs, where user was registered
        $blog_ids = $lib->get_blog_ids();  // full list of blogs
        $main_blog_id = $lib->get( 'main_blog_id' );
        foreach($blog_ids as $blog_id) {
            if ($blog_id===$main_blog_id) {   // do not touch the main blog, it was updated already
                continue;
            }
            if (!array_key_exists($blog_id, $user_blogs)) {
                $result = add_user_to_blog($blog_id, $user_id, 'subscriber');
                if ($result!==true) {
                   return false;
                }
                do_action('added_existing_user', $user_id, $result);                
            }
            $result = self::update_user_caps_for_blog( $blog_id, $user_id, $user_caps );
            if ($result===false) {
                return false;
            }
        }
        
        return true;
    }
    // end of network_update_user()    

    
    /**
     * Update roles for all network using direct database access - quicker in several times
     * Really updates only when is executed from the Network admin
     * 
     * @global wpdb $wpdb
     * @return boolean
     */
    public static function network_replicate_addons_data() {
                
        // replicate addons access data from the main site to the whole network
        $replicator = new URE_Network_Addons_Data_Replicator();
        $replicator->replicate_for_all_network();
        
    }
    // end of network_replicate_addons_data()
    
}
// end of URE_Editor_Ext class
