<?php
/*
 * Replicate addons data from the main site for all WordPress multisite network
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * 
*/

class URE_Network_Addons_Data_Replicator {

    private $lib = null;
    private $addons = null;
    private $source_blog = 0;
    
    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        
    }
    // end of __construct()

    
    private static function save_data_to_subsite($options_table_name, $addon) {
        global $wpdb;
        
        $access_data_key = $addon->access_data_key;
        $data = $addon->data;
        
        $query1 = $wpdb->prepare(
                "SELECT option_id FROM {$options_table_name} WHERE option_name=%s limit 0,1",
                array($access_data_key)
                        );
        $option_id = $wpdb->get_var($query1);
        if ($option_id>0) {
            $query = $wpdb->prepare(
                        "UPDATE {$options_table_name}
                            set option_value=%s
                            WHERE option_id=%d 
                            LIMIT 1",
                        array($data, $option_id)
                                );
        } else {
            $query = $wpdb->prepare(
                        "INSERT INTO {$options_table_name}
                            SET option_name=%s,
                                option_value=%s",
                        array($access_data_key, $data)
                                );
        }
        $wpdb->query($query);
        if ($wpdb->last_error) {
            return false;
        }
        
        return true;
    }
    // end of save_data_to_subsite()

    /**
     * Get add-on data from current blog
     * Source blog should be set as a current one in order this function works correctly
     * @param string $access_data_key - add-on unique access data key
     * @return string
     */
    private static function get_addon_data( $access_data_key ) {
    
        $data = get_option( $access_data_key );
        $serialized_data = serialize( $data );
        
        return $serialized_data;
        
    }
    // end of get_addon_data()
    
    
    private function blog_exist( $blog_id ) {
    
        $id = (int) $blog_id;
        if ( $id==0 ) {
            return false;
        }
        
        $blogs_list = $this->lib->get_blog_ids();
        $result = in_array( $id, $blogs_list );
        
        return $result;
    }
    // end of source_blog_exist()
    
    
    private function what_to_replicate() {        
        global $wpdb;
        
        $old_blog = $wpdb->blogid;
        $main_blog = $this->lib->get( 'main_blog_id' );
        // Main blog is a source by default. Return selected subsite ID to change a data source
        $this->source_blog = (int) apply_filters( 'ure_get_addons_source_blog', $main_blog );
        if ( $this->source_blog!=$old_blog ) {
            if ( !$this->blog_exist( $this->source_blog ) ) {
                $this->source_blog = $main_blog;
            }            
            switch_to_blog( $this->source_blog );
        }
        
        $addons_manager = URE_Addons_Manager::get_instance();
        $all_addons = $addons_manager->get_active();
        $this->addons = array();
        foreach( $all_addons as $addon ) {
            $replicator_id = URE_Addons_Manager::get_replicator_id( $addon->id );
            $replicate = filter_input( INPUT_POST, $replicator_id, FILTER_SANITIZE_NUMBER_INT );
            if ( $replicate ) {
                $addon->data = self::get_addon_data( $addon->access_data_key );
                $this->addons[$addon->id] = $addon;
            }
        }
        
        if ( $this->source_blog!=$old_blog ) {
            $this->lib->restore_after_blog_switching( $old_blog );
        }
    }
    // end of what_to_replicate()
    
    
    public function replicate_for_all_network() {
        global $wpdb;
        
        $this->what_to_replicate();  
        if ( empty( $this->addons ) ) { // addons list may be not empty at the Network admin only
            return;
        }
        
        $main_blog = $this->lib->get( 'main_blog_id' );
        $blog_ids = $this->lib->get_blog_ids();
        foreach ( $blog_ids as $blog_id ) {
            if ( $blog_id==$main_blog || $blog_id==$this->source_blog ) {
                // Do not copy data to main blog and itself
                continue;
            }
            $prefix = $wpdb->get_blog_prefix( $blog_id );
            $options_table_name = $prefix . 'options';            
            foreach( $this->addons as $addon ) {
                $result = self::save_data_to_subsite( $options_table_name, $addon );
                if ( !$result ) {
                    return false;
                }
            }
        }   
        
        // Use this action to hook a code to execute after add-ons data were updated at all subsites of the multisite network        
        do_action('ure_after_network_addons_update');
        
        return true;        
    }
    // end of replicate_for_all_network()
    
    
    /**
     * Load addons data to copy into a new created blog
     * Usage: It's called from User_Role_Editor::duplicate_roles_for_new_blog() via 'ure_get_addons_data_for_new_blog' filter
     * Source blog should be set as a current one in order this function works correctly
     * @param array $addons_list
     * @return array
     */
    public static function get_for_new_blog( $addons_list ) {
        global $wpdb;
        
        $old_blog = $wpdb->blogid;
        $lib = URE_Lib_Pro::get_instance();
        $main_blog = $lib->get( 'main_blog_id' );
        // Main blog is a source by default. Return selected subsite ID to change a data source
        $source_blog = (int) apply_filters( 'ure_get_addons_source_blog', $main_blog );
        if ( $source_blog!=$old_blog ) {
            if ( !$lib->blog_exist( $source_blog ) ) {
                $source_blog = $main_blog;
            }            
            switch_to_blog( $source_blog );
        }
        
        $addons_manager = URE_Addons_Manager::get_instance();
        $all_addons = $addons_manager->get_all();
        $addons_list = array();
        foreach( $all_addons as $addon ) {
           if ( !isset( $addon->access_data_key ) )  {
               continue;
           }
           $addon->copy = false;
           $addons_list[$addon->id] =  $addon;
        }        
        $addons_list = apply_filters( 'ure_addons_to_copy_for_new_blog', $addons_list );  // What addons to copy
        
        // get addons data
        foreach( $addons_list as $addon_id=>$addon ) {
            if ( empty( $addon->copy ) ) {
                unset( $addons_list[$addon_id] );
                continue;
            }
            $addons_list[$addon_id]->data = self::get_addon_data( $addon->access_data_key );
        }
        
        if ( $source_blog!=$old_blog ) {
            $lib->restore_after_blog_switching( $old_blog );
        }
        
        return $addons_list;
    }
    // end of get_for_new_blog()
    
    
    /**
     * Copy addons data to a new created blog
     * Usage: It's called from User_Role_Editor::duplicate_roles_for_new_blog() via 'ure_set_addons_data_for_new_blog' filter
     * @global WPDB $wpdb
     * @param int $blog_id
     * @param array $addons_list
     */
    public static function set_for_new_blog( $blog_id, $addons_list ) {
        global $wpdb;
        
        $prefix = $wpdb->get_blog_prefix( $blog_id );
        $options_table_name = $prefix . 'options';            
        foreach( $addons_list as $addon ) {
            $result = self::save_data_to_subsite( $options_table_name, $addon );
            if (!$result) {
                break;
            }
        }
        
    }
    // end of set_for_new_blog()
            
}
// end of Ure_Network_Addons_Data_Replicator class
