<?php
/*
 * User Role Editor WordPress plugin
 * Class URE_Widgets_Show_Controller - data controller for Widgets Show Access add-on
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Widgets_Show_Controller {

    const ACCESS_DATA_KEY = 'ure_widgets_show_access_data';
    const NO_ROLE = 'no_role_for_this_site';
            

    // load data
    public static function load($widget_id='') {
        
        $template = array('widget_id'=>'', 'access_model'=>1, 'roles'=>array());        
        if (empty($widget_id)) {
            return $template;
        }
        $template['widget_id'] = $widget_id;
        
        $data = get_option(self::ACCESS_DATA_KEY);
        
        if (empty($data) || !isset($data[$widget_id])) {
            return $template;
        }
        
        $result = $data[$widget_id];
        
        return $result;        
    }
    // end of load()

    
    private static function get_roles_from_post() {
        global $wp_roles;
        
        $roles = array();
        foreach($_POST as $key=>$value) {
            $pos = strpos($key, 'ure_role_');
            if ($pos===false) {
                continue;
            }
            $role_id = substr($key, 9);
            if (isset($wp_roles->roles[$role_id]) || $role_id==self::NO_ROLE) {
                $roles[] = $role_id;
            }
        }
        
        return $roles;
    }
    // end of get_roles_from_posts()
    
    
    // save data
    public static function save() {
        $lib = URE_Lib::get_instance();
        if (!$lib->is_right_admin_path('widgets.php')) {
            return;
        }
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        if ($action!=='ure_update_widgets_show_access_data') {
            return;
        }
        if (empty($_POST['ure_nonce']) || !wp_verify_nonce($_POST['ure_nonce'], 'user-role-editor')) {
            wp_die('Wrong nonce value. Action prohibited', 'Access error', 403);
        }
        
        $widget_id = filter_input(INPUT_POST, 'ure_widget_id', FILTER_SANITIZE_STRING);
        if (empty($widget_id)) {
            wp_die('Wrong widget ID. Action prohibited', 'Access error', 403);
        }
        
        $access_model = filter_input(INPUT_POST, 'ure_access_model', FILTER_SANITIZE_NUMBER_INT);
        if (empty($access_model)) {
            $access_model = 1;  // Do not show for selected roles
        }
        
        $roles = self::get_roles_from_post();
                        
        $data = get_option(self::ACCESS_DATA_KEY, array());
        $data[$widget_id] = array('access_model'=>$access_model, 'roles'=>$roles);
        update_option(self::ACCESS_DATA_KEY, $data);
        
    }
    // end of save()
    
    
}
// end of class URE_Widgets_Show_Controller