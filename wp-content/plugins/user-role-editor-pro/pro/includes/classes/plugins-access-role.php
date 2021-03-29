<?php
/*
 * Access restriction to plugins administration
 * Role level restrictions
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */

class URE_Plugins_Access_Role {
    const ACCESS_DATA_KEY = 'ure_plugins_access_data';
    
    private $lib = null;
    private $controller = null;
    
    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        $this->controller = new URE_Plugins_Access_Role_Controller();
        
        if (!(defined('DOING_AJAX') && DOING_AJAX)) {
            add_action('ure_role_edit_toolbar_service', array($this, 'add_toolbar_button'));
            add_action('ure_load_js', array($this, 'add_js'));
            add_action('ure_dialogs_html', 'URE_Plugins_Access_Role_View::dialog_html');
            add_action('ure_process_user_request', array($this->controller, 'update_data'));
        }

    }
    // end of __construct()
    
    
    public function add_toolbar_button() {
        
        if (!current_user_can(URE_Plugins_Access::CAPABILITY)) {
            return;
        }

        URE_Plugins_Access_Role_View::add_toolbar_button();
        
    }
    // end of add_toolbar_buttons()
    
    
    public function add_js() {
        wp_register_script('ure-plugins-access', plugins_url('/pro/js/plugins-access-role.js', URE_PLUGIN_FULL_PATH ), array(), URE_VERSION );
        wp_enqueue_script ('ure-plugins-access');
        wp_localize_script('ure-plugins-access', 'ure_data_plugins_access',
                array(
                    'plugins_button' => esc_html__('Plugins', 'user-role-editor'),
                    'dialog_title' => esc_html__('Allow manage plugins', 'user-role-editor'),
                    'update_button' => esc_html__('Update', 'user-role-editor'),
                    'activate_plugins_required' => esc_html__('Role should have "activate_plugins" capability', 'user-role-editor')
                ));
    }
    // end of add_js()    
    
    
    /**
     * returns JSON with form data as the response for AJAX request from URE's main page
     * 
     * @return array
     */
    public static function get_html() {
        global $wp_roles;
        
        if (!current_user_can(URE_Plugins_Access::CAPABILITY)) {
            return array('result'=>'error', 'message'=>'Not enough permissions');
        }
        
        $lib = URE_Lib_Pro::get_instance();
        $role_id = $lib->get_request_var('current_role', 'post');
        if (!isset($wp_roles->role_names[$role_id])) {
            return array('result'=>'error', 'message'=>'Wrong current role value!');
        }
        
        $controller = new URE_Plugins_Access_Role_Controller();
        $args = $controller->prepare_form_data($role_id);
        $html = URE_Plugins_Access_Role_View::get_html($args);
        
        return array('result'=>'success', 'message'=>'Plugins management permissions for role: '. $role_id, 'html'=>$html);
    }
    // end of get_html()
    
    
}
// end of URE_Plugins_Access_Role