<?php
/*
 * Class: Access restrict to posts/pages user meta processor
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 */
class URE_Posts_Edit_Access_User_Meta {

    private $umk_restriction_type = '';   // user meta key for - allow or prohibit post edit by its ID
    private $umk_own_data_only = '';   // user meta key for - allow user to edit own data only
    private $umk_posts_list = '';    // user meta key for - post IDs list
    private $umk_post_types = '';   // user meta key for - post types    
    private $umk_post_authors_list = '';    // user meta key for - post IDs list
    private $umk_post_categories_list = '';  // user meta key for post categories list

    public function __construct() {
        global $wpdb;
        
        $this->umk_restriction_type = $wpdb->prefix .'ure_posts_restriction_type';
        $this->umk_own_data_only = $wpdb->prefix .'ure_own_data_only';
        $this->umk_posts_list = $wpdb->prefix .'ure_posts_list';
        $this->umk_post_types = $wpdb->prefix .'ure_post_types';        
        $this->umk_post_authors_list = $wpdb->prefix .'ure_authors_list'; 
        $this->umk_post_categories_list = $wpdb->prefix .'ure_categories_list';
        
    }
    // end of __construct()
    
    
    public function get_restriction_type($user_id) {
                        
        if (empty($user_id)) {
            $user_id = get_current_user_id();
        }        
        $restriction_type = get_user_meta($user_id, $this->umk_restriction_type, true);        
        
        return $restriction_type;
        
    }
    // end of get_restriction_type()
    
    
    public function set_restriction_type($user_id, $value) {
        
        update_user_meta($user_id, $this->umk_restriction_type, $value);
        
    }
    // end of set_restrictions_type()
    
    
    public function get_own_data_only($user_id) {
        
        if (empty($user_id)) {
            $user_id = get_current_user_id();
        }        
        $value = get_user_meta($user_id, $this->umk_own_data_only, true);
        if (empty($value)) {
            $value = 0;
        }
        
        return $value;
        
    }
    // end of get_own_data_only()
    
    
    public function set_own_data_only($user_id, $value) {
        
        update_user_meta($user_id, $this->umk_own_data_only, $value);
        
    }
    // end of set_own_data_only()
    
    
    public function get_posts_list($user_id) {
        
        $list = get_user_meta($user_id, $this->umk_posts_list, true);
        
        return $list;
    }
    // end of get_posts_list()
    
    
    public function set_posts_list($user_id, $value) {
        
        update_user_meta($user_id, $this->umk_posts_list, $value);
        
    }
    // end of set_posts_list()
    
    
    public function delete_posts_list($user_id) {
        
        delete_user_meta($user_id, $this->umk_posts_list);
        
    }
    // end of delete_posts_list()
    
    
    public function delete_post_types($user_id) {        
        
        delete_user_meta($user_id, $this->umk_post_types);
        
    }
    // end of delete_post_types()
    
    
    public function get_post_categories_list($user_id) {
        
        $list = get_user_meta($user_id, $this->umk_post_categories_list, true);
        
        return $list;
    }
    // end of get_post_categories_list()
    
    
    public function set_post_categories_list($user_id, $value) {
        
        update_user_meta($user_id, $this->umk_post_categories_list, $value);
        
    }
    // end of set_post_categories_list()
    
    
    public function delete_post_categories_list($user_id) {
        
        delete_user_meta($user_id, $this->umk_post_categories_list);
        
    }
    // end of delete_post_categories_list()
    
    
    public function get_post_authors_list($user_id) {
        
        $list = get_user_meta($user_id, $this->umk_post_authors_list, true);
        
        return $list;
    }
    // end of get_post_authors_list()
    
    
    public function set_post_authors_list($user_id, $value) {
        
        update_user_meta($user_id, $this->umk_post_authors_list, $value);
        
    }
    // end of set_post_categories_list()
    
    
    public function delete_post_authors_list($user_id) {
        
        delete_user_meta($user_id, $this->umk_post_authors_list);
        
    }
    // end of delete_post_categories_list()
    
}
// end of URE_Posts_Edit_Access_User_Meta class