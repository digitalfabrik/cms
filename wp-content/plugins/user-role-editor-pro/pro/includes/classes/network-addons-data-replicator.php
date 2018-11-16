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
     * Get addon data from the main blog
     * Main blog should be set as a current one in order this function works correctly
     * @param string $access_data_key - addon unique access data key
     * @return string
     */
    private static function get_data_from_main_blog($access_data_key) {
    
        $data = get_option($access_data_key);
        $serialized_data = serialize($data);
        
        return $serialized_data;
        
    }
    // end of get_data_from_main_blog()
    
    
    private function what_to_replicate() {
        $addons_manager = URE_Addons_Manager::get_instance();
        $all_addons = $addons_manager->get_active();
        $this->addons = array();
        foreach($all_addons as $addon) {
            $replicator_id = URE_Addons_Manager::get_replicator_id($addon->id);
            $replicate = filter_input(INPUT_POST, $replicator_id, FILTER_SANITIZE_NUMBER_INT);
            if ($replicate) {
                $addon->data = self::get_data_from_main_blog($addon->access_data_key);
                $this->addons[$addon->id] = $addon;
            }
        }
                
    }
    // end of what_to_replicate()
    
    
    public function replicate_for_all_network() {
        global $wpdb;
        
        $this->what_to_replicate();                                                        
        
        $blog_ids = $this->lib->get('blog_ids');
        foreach ($blog_ids as $blog_id) {
            $prefix = $wpdb->get_blog_prefix($blog_id);
            $options_table_name = $prefix . 'options';            
            foreach($this->addons as $addon) {
                $result = self::save_data_to_subsite($options_table_name, $addon);
                if (!$result) {
                    return false;
                }
            }
        }   
        
        return true;        
    }
    // end of replicate_for_all_network()
    
    
    /**
     * Load addons data to copy into a new created blog
     * Usage: It's called from User_Role_Editor::duplicate_roles_for_new_blog() via 'ure_get_addons_data_for_new_blog' filter
     * Main blog should be set as a current one in order this function works correctly
     * @param array $addons_list
     * @return array
     */
    public static function get_for_new_blog($addons_list) {
        
        $addons_manager = URE_Addons_Manager::get_instance();
        $all_addons = $addons_manager->get_active();
        $addons_list = array();
        foreach($all_addons as $addon) {            
           if (!isset($addon->access_data_key))  {
               continue;
           }
           $addon->copy = false;
           $addons_list[$addon->id] =  $addon;
        }        
        $addons_list = apply_filters('ure_addons_to_copy_for_new_blog', $addons_list);  // What addons to copy
        
        // get addons data
        foreach($addons_list as $addon_id=>$addon) {
            if (empty($addon->copy)) {
                unset($addons_list[$addon_id]);
                continue;
            }
            $addons_list[$addon_id]->data = self::get_data_from_main_blog($addon->access_data_key);
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
    public static function set_for_new_blog($blog_id, $addons_list) {
        global $wpdb;
        
        $prefix = $wpdb->get_blog_prefix($blog_id);
        $options_table_name = $prefix . 'options';            
        foreach($addons_list as $addon) {
            $result = self::save_data_to_subsite($options_table_name, $addon);
            if (!$result) {
                break;
            }
        }
        
    }
    // end of set_for_new_blog()
        
    
}
// end of Ure_Network_Addons_Data_Replicator class