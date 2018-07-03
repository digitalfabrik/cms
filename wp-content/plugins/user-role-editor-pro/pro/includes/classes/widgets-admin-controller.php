<?php

/*
 * User Role Editor WordPress plugin
 * Class URE_Widgets_Admin_Controller - data update/load for Widgets Admin Access add-on
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Widgets_Admin_Controller {

    private $lib = null;
    const ACCESS_DATA_KEY = 'ure_widgets_access_data';
    
    
    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        
        add_action('ure_process_user_request', array($this, 'update_data'));
        
    }
    // end of __construct()
    
        
    /**
     * Load widgets access data for role
     * @param string $role_id
     * @return array
     */
    public static function load_data_for_role($role_id) {
        
        $access_data = get_option(self::ACCESS_DATA_KEY);
        if (is_array($access_data) && array_key_exists($role_id, $access_data)) {
            $result = $access_data[$role_id];
            // update data structure to make it compatible with version 4.35+
            if (!isset($result['widgets'])) {
                $result = array(
                    'widgets' => $result,
                    'sidebars' => array());
            }
        } else {
            $result = array(
                'widgets' => array(),
                'sidebars' => array()
                );
        }
        
        return $result;
    }
    // end of load_data_for_role()
    
    
    public function load_data_for_user($user) {
    
        if (is_object($user)) {
            $id = $user->ID;
        } else if (is_int($user)) {
            $id = $user;
            $user = get_user_by('id', $user);
        } else {
            $user = get_user_by('login', $user);
            $id = $user->ID;
        }
        
        $blocked = get_user_meta($user->ID, self::ACCESS_DATA_KEY, true);
        if (!is_array($blocked)) {
            $blocked = array();
        }
        
        $access_data = get_option(self::ACCESS_DATA_KEY);
        if (empty($access_data)) {
            $access_data = array();
        }
        
        foreach ($user->roles as $role) {
            if (isset($access_data[$role])) {
                $blocked = array_merge($blocked, $access_data[$role]);
            }
        }
        
        // update data structure to make it compatible with version 4.35+
        if (!isset($blocked['widgets'])) {
            $blocked = array_unique ($blocked);
            $blocked = array(
                'widgets' => $blocked,
                'sidebars' => array()
                );
        }
        
        return $blocked;
    }
    // end of load_data_for_user()

    
    private function get_access_data_from_post() {
        
        $keys_to_skip = array('action', 'ure_nonce', '_wp_http_referer', 'ure_object_type', 'ure_object_name', 'user_role');
        $access_data = array(
            'widgets' => array(),
            'sidebars' => array()
            );
        foreach ($_POST as $key=>$value) {
            if (in_array($key, $keys_to_skip)) {
                continue;
            }
            if (substr($key, 0, 7)==='ure_sb-') {    // Sidebar
                $access_data['sidebars'][] = substr($key, 7);
            } else {
                $access_data['widgets'][] = $key;
            }
            
        }
        
        return $access_data;
    }
    // end of get_access_data_from_post()
        
    
    private function save_access_data_for_role($role_id) {
        
        $access_for_role = $this->get_access_data_from_post();
        $access_data = get_option(self::ACCESS_DATA_KEY);        
        if (!is_array($access_data)) {
            $access_data = array();
        }
        if (count($access_for_role)>0) {
            $access_data[$role_id] = $access_for_role;
        } else {
            unset($access_data[$role_id]);
        }
        update_option(self::ACCESS_DATA_KEY, $access_data);
        
    }
    // end of save_access_data_for_role()
    
    
    private function save_access_data_for_user($user_login) {
        
//$access_for_user = $this->get_access_data_from_post();
        // TODO ...
        
    }
    // end of save_menu_access_data_for_role()   
                    
    
    public function get_allowed_roles($user) {
        $allowed_roles = array();
        if (empty($user)) {   // request for Role Editor - work with currently selected role
            $current_role = filter_input(INPUT_POST, 'current_role', FILTER_SANITIZE_STRING);
            $allowed_roles[] = $current_role;
        } else {    // request from user capabilities editor - work with that user roles
            $allowed_roles = $user->roles;
        }
        
        return $allowed_roles;
    }
    // end of get_allowed_roles()
                    
    
    public function get_all_widgets() {
        global $wp_widget_factory;
	
        if (is_object($wp_widget_factory)) {
            return $wp_widget_factory->widgets;
        } else {
            return array();
        }
    }
    // end of get_all_widgets()
    
    
    public function get_all_sidebars() {
        global $wp_registered_sidebars;
	
        if (is_array($wp_registered_sidebars)) {
            return $wp_registered_sidebars;
        } else {
            return array();
        }
    }
    // end of get_all_sidebars()
    
    
    public function update_data() {
    
        if (!isset($_POST['action']) || $_POST['action']!=='ure_update_widgets_access') {
            return;
        }
        
        if (!current_user_can('ure_widgets_access')) {
            $this->lib->set_notification( esc_html__('URE: you do not have enough permissions to access this module.', 'user-role-editor') );
            return;
        }
        
        $ure_object_type = filter_input(INPUT_POST, 'ure_object_type', FILTER_SANITIZE_STRING);
        if ($ure_object_type!=='role' && $ure_object_type!=='user') {
            $this->lib->set_notification( esc_html__('URE: widgets access: Wrong object type. Data was not updated.', 'user-role-editor') );
            return;
        }
        $ure_object_name = filter_input(INPUT_POST, 'ure_object_name', FILTER_SANITIZE_STRING);
        if (empty($ure_object_name)) {
            $this->lib->set_notification( esc_html__('URE: widgets access: Empty object name. Data was not updated', 'user-role-editor') );
            return;
        }
                        
        if ($ure_object_type=='role') {
            $this->save_access_data_for_role($ure_object_name);
        } else {
            $this->save_access_data_for_user($ure_object_name);
        }
        
        $this->lib->set_notification( esc_html__('Widgets access: data was updated successfully', 'user-role-editor') );
    }
    // end of update_data()

        
}
// end of URE_Widgets_Admin class