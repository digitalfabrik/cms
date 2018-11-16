<?php

/*
 * User Role Editor WordPress plugin
 * Content view access management controller
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Content_View_Restrictions_Controller {

    const ACCESS_DATA_KEY = 'ure_posts_view_access_data';
    
    
    /**
     * Load access data for role
     * @param string $role_id
     * @return array
     */
    public static function load_data_for_role($role_id) {
        
        $access_data = get_option(self::ACCESS_DATA_KEY);
        if (is_array($access_data) && array_key_exists($role_id, $access_data)) {
            $result =  $access_data[$role_id];
            if (!isset($result['access_model'])) {
                $result['data'] = $result;
                $result['access_model'] = 1;
                $result['access_error_action'] = 1;
            }
        } else {
            $result = array(
                'access_model'=>1,
                'access_error_action'=>1,
                'data'=>array());
        }
        
        return $result;
    }
    // end of load_access_data_for_role()
    
    
    private static function init_blocked_data($default=0) {
        $blocked = array(
                'access_model'=>$default, 
                'access_error_action'=>$default, 
                'data'=>array());
        
        return $blocked;
    }
    // end of init_blocked_data()
    
    private static function data_merge($target, $source, $object_id) {
        
        if (!isset($source[$object_id])) {
            return $target;
        }
        
        if (!isset($target[$object_id])) {
            $target[$object_id] = $source[$object_id];
        } else {
            $target[$object_id] = array_merge($target[$object_id], $source[$object_id]);
        }
        
        return $target;
    }
    // end of data_merge()
    
    
    private static function merge_blocked_with_roles_data($user, $blocked) {
        
        if (!is_array($blocked)) {
            $blocked = self::init_blocked_data(0);
        }
        if (!is_array($user->roles) || count($user->roles)==0) {
            return $blocked;
        }
        
        $access_data = get_option(self::ACCESS_DATA_KEY);
        if (empty($access_data)) {
            $access_data = array();
        }
        foreach ($user->roles as $role) {
            if (isset($access_data[$role])) {
                if (!isset($access_data[$role]['access_model'])) { // for backward compatibility
                    $access_model = 1;   // Use default (block selected) access model
                    $data = $access_data[$role];
                } else {
                    $access_model = $access_data[$role]['access_model'];
                    $data = $access_data[$role]['data'];
                }
                if (!isset($access_data[$role]['access_error_action'])) {
                    $access_error_action = 1;
                } else {
                    $access_error_action = $access_data[$role]['access_error_action'];
                }
                if (empty($blocked['access_model'])) {  
                    $blocked['access_model'] = $access_model;    // take the 1st found role's access model as the main one                    
                }
                if (empty($blocked['access_error_action'])) {  
                    $blocked['access_error_action'] = $access_error_action;    // take the 1st found role's access error action as the main one                    
                }
                // take into account data with the same access model only as the 1st one found
                if ($access_model==$blocked['access_model']) {                    
                    $blocked['data'] = self::data_merge($blocked['data'], $data, 'posts');
                    if (isset($data['authors'])) {
                        $blocked['data'] = self::data_merge($blocked['data'], $data, 'authors');
                    }
                    $blocked['data'] = self::data_merge($blocked['data'], $data, 'terms');
                    $blocked['data'] = self::data_merge($blocked['data'], $data, 'page_templates');
                    if (isset($data['own_data_only']) && $data['own_data_only']==1) {
                            $blocked['data']['own_data_only'] = 1;
                    }                    
                }
            }
        }
        
        return $blocked;
    }
    // end of merge_blocked_with_roles_data()
            
    
    public static function load_access_data_for_user($user) {        
        $lib = URE_Lib_Pro::get_instance();
        $user = $lib->get_user($user);
        if (empty($user)) {
            $blocked = self::init_blocked_data(1);
            return $blocked;
        }    
        
        $blocked = get_user_meta($user->ID, self::ACCESS_DATA_KEY, true);                                                      
        $blocked = self::merge_blocked_with_roles_data($user, $blocked);        

        if (empty($blocked['access_model'])) {
            $blocked['access_model'] = 1; // use default value
        }
        if (!isset($blocked['access_error_action']) || empty($blocked['access_error_action'])) {
            $blocked['access_error_action'] = 1; // use default value
        }        
        
        return $blocked;
    }
    // end of load_access_data_for_user()
    

    static private function get_keys_to_skip() {
        $keys_to_skip = array(
            'action', 
            'ure_nonce', 
            '_wp_http_referer', 
            'ure_object_type', 
            'ure_object_name', 
            'user_role', 
            'ure_access_model',
            'ure_posts_list',
            'ure_posts_authors_list',
            'ure_own_data_only');
        
        return $keys_to_skip;
    }
    // end of get_keys_to_skip()
    
    
    static private function get_access_model() {
        $result = filter_input(INPUT_POST, 'ure_access_model', FILTER_VALIDATE_INT);
        if ($result!=1 && $result!=2) { // got invalid value
            $result = 1;  // use default value
        }
        
        return $result;
    }
    // end of get_access_model()
    
    
    static private function get_access_error_action() {
        $result = filter_input(INPUT_POST, 'ure_post_access_error_action', FILTER_VALIDATE_INT);
        if ($result!=1 && $result!=2) { // got invalid value
            $result = 1;  // use "return 404 HTTP error" as a default value
        }
        
        return $result;
    }
    // end of get_access_error_action()
    
    
    static private function get_terms() {
        $keys_to_skip = self::get_keys_to_skip();
        $terms = array();
        foreach (array_keys($_POST) as $key) {
            if (in_array($key, $keys_to_skip)) {
                continue;
            }
            $value = filter_var($key, FILTER_SANITIZE_STRING);
            $values = explode('_', $value);
            if ($values[0]!=='cat') {
                continue;
            }
            $term_id = (int) $values[1];
            if ($term_id>0) {
                $terms[] = $term_id;
            }
        }
        
        return $terms;
    }
    // end of get_terms()
    
    
    // prepare page templates list for internal usage
    static private function prepare_page_templates() {
        $all_templates = get_page_templates();
        if (count($all_templates)==0) {
            return array();
        }
        $templates = array();
        foreach(array_keys($all_templates) as $key) {
            $post_var = str_replace(array('.','/','_'), '-', $all_templates[$key]);
            $templates[$post_var] = $all_templates[$key];
        }
        
        return $templates;
    }
    // end of prepare_page_templates()
    
    
    /**
     * Extract page templates ID list from the POST array
     * @return array
     */
    static private function get_page_templates_from_post() {
        $templates = self::prepare_page_templates();
        if (empty($templates)) { // theme does not support page templates
            return;
        }
        
        $keys_to_skip = self::get_keys_to_skip();
        $items = array();
        foreach (array_keys($_POST) as $key) {
            if (in_array($key, $keys_to_skip)) {
                continue;
            }
            $value = filter_var($key, FILTER_SANITIZE_STRING);
            $values = explode('_', $value);
            if ($values[0]!=='templ') {
                continue;
            }
            $key = $values[1];
            if (isset($templates[$key])) {
                $items[] = $templates[$key];
            }
            
        }
        
        return $items;
    }
    // end of get_page_templates_from_post()


    static private function get_posts() {
        $posts = array();
        $posts_list_str = filter_input(INPUT_POST, 'ure_posts_list', FILTER_SANITIZE_STRING);
        if (!empty($posts_list_str)) {
            $posts = URE_Utils::filter_int_array_from_str($posts_list_str);
        }
        
        return $posts;
    }
    // end of get_posts()
    
    
    static private function get_authors() {
        $authors = array();
        $authors_list_str = filter_input(INPUT_POST, 'ure_posts_authors_list', FILTER_SANITIZE_STRING);
        if (!empty($authors_list_str)) {
            $authors = URE_Utils::filter_int_array_from_str($authors_list_str);
        }
        
        return $authors;
    }
    // end of get_authors()
    
    
    static private function get_own_data_only() {
        $value = 0;
        if (!empty($_POST['ure_own_data_only'])) {
            $value = 1;
        }
        
        return $value;
    }
    // end of get_own_data_only()
    
    
    static private function get_access_data_from_post() {

        $access_model = self::get_access_model();        
        $access_error_action = self::get_access_error_action();        
        $posts = self::get_posts();
        $authors = self::get_authors();
        $own_data_only = self::get_own_data_only();
        $terms = self::get_terms();
        $page_templates = self::get_page_templates_from_post();
        $access_data = array(
            'access_model'=>$access_model, 
            'access_error_action'=>$access_error_action,
            'data'=>array(                
                'posts'=>$posts, 
                'authors'=>$authors,
                'own_data_only'=>$own_data_only,
                'terms'=>$terms,
                'page_templates'=>$page_templates
                )
            );                                                        
        
        return $access_data;
    }
    // end of get_access_data_from_post()
    
    
    public static function save_access_data_for_role($role_id) {        
        $access_data = get_option(self::ACCESS_DATA_KEY);        
        if (!is_array($access_data)) {
            $access_data = array();
        }
        $data = self::get_access_data_from_post();
        if (count($data)>0) {
            $access_data[$role_id] = $data;
        } else {
            unset($access_data[$role_id]);
        }
        update_option(self::ACCESS_DATA_KEY, $access_data);
    }
    // end of save_access_data_for_role()
    
    
    public function save_access_data_for_user($user_login) {
        
        // TODO ...
    }
    // end of save_menu_access_data_for_role()    
    
}
// end of URE_Content_View_Restrictions_Controller class