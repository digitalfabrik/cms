<?php

/*
 * User Role Editor WordPress plugin
 * Author: Vladimir Garagulya
 * Email: support@role-editor.com
 * License: GPLv2 or later
 */


/**
 * Process AJAX request from User Role Editor Pro
 */
class URE_Pro_Ajax_Processor extends URE_Ajax_Processor {
        
    
    protected function get_admin_menu() {
        require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu-view.php');
        
        $admin_menu_view = new URE_Admin_Menu_View();
        $answer = $admin_menu_view->get_html();
        
        return $answer;
    }
    // end of get_admin_menu()
    
    
    protected function get_nav_menus() {
        require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu-view.php');
        
        $view = new URE_Nav_Menus_Admin_View();
        $answer = $view->get_html();
        
        return $answer;
    }
    // end of get_nav_menus()
    
    
    protected function get_widgets_list() {
        $view = new URE_Widgets_Admin_View();
        $answer = $view->get_html();
        
        return $answer;
    }
    // end of get_widgets_list()
    
    
    protected function get_meta_boxes_list() {
        $meta_boxes = new URE_Meta_Boxes($this->lib);
        $answer = $meta_boxes->get_html();
        
        return $answer;
    }
    // end of get_widgets_list()
    

    protected function remove_from_meta_boxes_list() {        
        $answer = URE_Meta_Boxes_Access::remove_from_list();
        
        return $answer;
    }
    // end of get_widgets_list()

    
    protected function get_roles_list() {
        $other_roles = new URE_Other_Roles();
        $answer = $other_roles->get_html();
        
        return $answer;
    }
    // end of get_widgets_list()
    
    
    protected function get_posts_view_access_data_for_role() {
        $posts_view = new URE_Posts_View($this->lib);
        $answer = $posts_view->get_html();
        
        return $answer;
    }
    // end of get_posts_view_access_data_for_role()

    
    protected function set_users_edit_restrictions() {
        
        $controller = new URE_Posts_Edit_Access_Bulk_Action($this->lib);
        $answer = $controller->set_users_edit_restrictions();
        
        return $answer;
    }
    // end of set_users_edit_restrictions()
    
    
    protected function get_posts_edit_access_data_for_role() {
        $pear = new URE_Posts_Edit_Access_Role();
        $answer = $pear->get_html();
        
        return $answer;
    }
    // end of get_posts_edit_access_data_for_role()
    
    
    protected function get_show_access_data_for_widget() {
        
        $wsv = new URE_Widgets_Show_View();
        $answer = $wsv->get_html();
        
        return $answer;
    }
    // end of get_show_access_data_for_widget()
    
    
    protected function get_plugins_access_data_for_user() {
        
        $pau = new URE_Plugins_Access_User();
        $answer = $pau->get_plugins_list_html();
        
        return $answer;
    }
    // end of get_plugins_access_data_for_user()
    

    protected function get_plugins_access_data_for_role() {
        
        $par = new URE_Plugins_Access_Role();
        $answer = $par->get_html();
        
        return $answer;
    }
    // end of get_plugins_access_data_for_role()

    
    protected function get_admin_menu_allowed_args() {
        
        $view = new URE_Admin_Menu_View();
        $answer = $view->get_allowed_args_html();
        
        return $answer;
    }
    // end of get_plugins_access_data_for_role()
    

    protected function save_admin_menu_allowed_args() {
        
        $answer = URE_Admin_Menu_URL_Allowed_Args::update_white_list();
        
        return $answer;
    }
    // end of get_plugins_access_data_for_role()

    
    protected function import_role_to_site() {
        
        $answer = URE_Import_Single_Role::import_to_site();
        
        return $answer;
    }
    // end of import_role_to_site()
    
    /**
     * Override a parent method
     * 
     * @return string
     */
    protected function get_required_cap() {
                
        if ($this->action=='get_show_access_data_for_widget') {
            $cap = 'ure_widgets_show_access';
        } else {
            $cap = parent::get_required_cap();
        }
        
        return $cap;
    }
    // end of get_required_cap()
                
    
    /**
     * AJAX requests dispatcher
     */    
    protected function _dispatch() {
        
        $answer = parent::_dispatch($this->action);
        if (substr($answer['message'], 0, 14)!='Unknown action') {
            return $answer;
        }
        
        switch ($this->action) {            
            case 'get_admin_menu': {
                $answer = $this->get_admin_menu();
                break;
            }
            case 'get_nav_menus': {
                $answer = $this->get_nav_menus();
                break;
            }
            case 'get_widgets_list': {
                $answer = $this->get_widgets_list();
                break;
            }
            case 'get_meta_boxes_list': {
                $answer = $this->get_meta_boxes_list();
                break;
            }
            case 'remove_from_meta_boxes_list': {
                $answer = $this->remove_from_meta_boxes_list();
                break;
            }
            case 'get_roles_list': {
                $answer = $this->get_roles_list();
                break;
            }
            case 'get_posts_view_access_data': {
                $answer = $this->get_posts_view_access_data_for_role();
                break;
            }
            case 'set_users_edit_restrictions': {
                $answer = $this->set_users_edit_restrictions();
                break;
            }
            case 'get_posts_edit_access_data_for_role': {
                $answer = $this->get_posts_edit_access_data_for_role();
                break;
            }
            case 'get_show_access_data_for_widget': {
                $answer = $this->get_show_access_data_for_widget();
                break;
            }
            case 'get_plugins_access_data_for_user': {
                $answer = $this->get_plugins_access_data_for_user();
                break;
            }
            case 'get_plugins_access_data_for_role': {
                $answer = $this->get_plugins_access_data_for_role();
                break;
            }
            case 'get_admin_menu_allowed_args': {
                $answer = $this->get_admin_menu_allowed_args();
                break;
            }
            case 'save_admin_menu_allowed_args': {
                $answer = $this->save_admin_menu_allowed_args();
                break;
            }
            case 'import_role_to_site': {
                $answer = $this->import_role_to_site();
                break;
            }            
          default:
                $answer = array('result'=>'error', 'message'=>'unknown action "'. $this->action .'"');
        }
        
        return $answer;
    }    
    
}
// end of URE_Pro_Ajax_Processor