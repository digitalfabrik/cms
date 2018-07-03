<?php
/*
 * User Role Editor WordPress plugin
 * Class URE_Widgets_Show_Access - prohibit show of widget for roles
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Widgets_Show_Access {

    private $lib = null;    // reference to the code library object

    
    public function __construct() {
            
        $this->lib = URE_Lib_Pro::get_instance();
        
        if (is_admin()) {
            if (current_user_can('ure_widgets_show_access')) {
                new URE_Widgets_Show_View();
            }
        } else {
            add_filter('sidebars_widgets', array($this, 'block_widgets'));
        }
        
    }
    // end of __construct()

    
    private function show_for_selected_roles($roles) {
        $show = true;
        if (!is_user_logged_in()) {
            if (in_array(URE_Widgets_Show_Controller::NO_ROLE, $roles)) {
                $show = false;
            }
        } else {            
            foreach($roles as $role_id) {
                if (current_user_can($role_id)) {
                    $show = false;
                    break;
                }
            }
        }
        
        return $show;
    }
    // end of show_for_selected_roles()
    
    
    private function show_for_not_selected_roles($roles) {
                
        $show = true;
        if (!is_user_logged_in()) {
            if (!in_array(URE_Widgets_Show_Controller::NO_ROLE, $roles)) {
                $show = false;
            }
        } else {
            $current_user = wp_get_current_user();
            foreach($current_user->roles as $role_id) {
                if (!in_array($role_id, $roles)) {
                    $show = false;
                }
            }
        }
        
        return $show;
    }
    // end of show_for_not_selected_roles()
    
    
    
    /**
     * Block widgets from showing at front-end according to the settings made for every widget
     * 
     */
    public function block_widgets($sidebars_widgets) {
        if ($this->lib->is_super_admin()) {  //  do not block super admin user
            return $sidebars_widgets;
        }
        
        $data = get_option(URE_Widgets_Show_Controller::ACCESS_DATA_KEY, array());
        
        foreach($sidebars_widgets as $sidebar=>$widgets) {
            if ($sidebar=='wp_inactive_widgets') {
                continue;
            }
            if (empty($widgets)) {
                continue;
            }
            foreach($widgets as $key=>$widget_id) {                
                if (!isset($data[$widget_id])) {
                    continue;
                }
                if ($data[$widget_id]['access_model']==1) { // do not show for selected roles
                    $show = $this->show_for_selected_roles($data[$widget_id]['roles']);
                } else {    //  do not show for not selected roles
                    $show = $this->show_for_not_selected_roles($data[$widget_id]['roles']);                    
                }
                if (!$show) {
                    unset($sidebars_widgets[$sidebar][$key]);
                }
            }
        }
        return $sidebars_widgets;
    }
    // end of block_widgets()
                        
}
// end of URE_Widgets_Show_Access class