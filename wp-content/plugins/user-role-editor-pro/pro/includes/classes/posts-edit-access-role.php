<?php
/*
 * Class: Edit access restrict to posts/pages for role - user interface
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */

class URE_Posts_Edit_Access_Role {

    const ACCESS_DATA_KEY = 'ure_posts_edit_access_data';
    const EDIT_POSTS_ACCESS_CAP = 'ure_edit_posts_access';
    
    // reference to the code library object
    private $lib = null;        


    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        
        if (!(defined('DOING_AJAX') && DOING_AJAX)) {
            add_action('ure_role_edit_toolbar_service', array($this, 'add_toolbar_button'));
            add_action('ure_load_js', array($this, 'add_js'));
            add_action('ure_dialogs_html', 'URE_Posts_Edit_Access_View::dialog_html');
            add_action('ure_process_user_request', 'URE_Posts_Edit_Access_Role_Controller::update_data');
        }

    }
    // end of __construct()

    
    public function add_toolbar_button() {
        if (!current_user_can(self::EDIT_POSTS_ACCESS_CAP)) {
            return;
        }
            
        URE_Posts_Edit_Access_View::add_toolbar_button();
        
    }
    // end of add_toolbar_buttons()

    
    public function add_js() {
        wp_register_script('ure-posts-edit-access', plugins_url('/pro/js/posts-edit-access.js', URE_PLUGIN_FULL_PATH));
        wp_enqueue_script ('ure-posts-edit-access');
        wp_localize_script('ure-posts-edit-access', 'ure_data_posts_edit_access',
                array(
                    'posts_edit' => esc_html__('Posts Edit', 'user-role-editor'),
                    'dialog_title' => esc_html__('Posts Edit Access', 'user-role-editor'),
                    'update_button' => esc_html__('Update', 'user-role-editor')
                ));
    }
    // end of add_js()    
                                   
    /**
     * returns JSON with form data as the response for AJAX request from URE's main page
     * 
     * @return array
     */
    public static function get_html() {
        
        if (!current_user_can(self::EDIT_POSTS_ACCESS_CAP)) {
            return array('result'=>'error', 'message'=>'Not enough permissions');
        }
        
        $role_id = filter_input(INPUT_POST, 'current_role', FILTER_SANITIZE_STRING);
        $args = URE_Posts_Edit_Access_Role_Controller::prepare_form_data($role_id);                        
        $html = URE_Posts_Edit_Access_View::get_html($args);
        
        return array('result'=>'success', 'message'=>'Posts edit permissions for role:'+ $role_id, 'html'=>$html);
    }
    // end of get_html()
    
}
// end of URE_Posts_Edit_Access_Role class