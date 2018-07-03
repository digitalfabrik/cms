<?php
/*
 * Access restriction to plugins administration
 * Data controller for user
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */

class URE_Plugins_Access_User_Controller {

    private $lib = null;
    private $umk_model = '';    // umk - User Meta Key
    private $umk_plugins = '';
    
    public function __construct() {
        global $wpdb;
        
        $this->lib = URE_Lib_Pro::get_instance();
        $this->umk_model = $wpdb->prefix .'ure_plugins_access_selection_model'; //  1 - Selected, 2 - Not Selected
        $this->umk_plugins = $wpdb->prefix .'ure_allow_plugins_activation';
        
    }
    // end of __construct()
 
    
    /**
     * Get selection model: 1 - Selected, 2 - Not Selected
     * 
     * @param int $user_id
     * @return int
     */
    public function get_model($user_id) {
        
        $model = get_user_meta($user_id, $this->umk_model, true);
        
        return $model;
    }
    // end of get_model()
    
    
    public function get_plugins($user_id) {
        
        $plugins = trim(get_user_meta($user_id, $this->umk_plugins, true));
        
        return $plugins;
    }
    // end of get_plugins()
            
    
    public function get_data_from_post() {
        $data = array();
        
        $data['user_id'] = (int) $this->lib->get_request_var('user_id', 'post', 'int');
        $data['plugins'] = $this->lib->get_request_var('plugins', 'post');
        
        return $data;
    }
    // end of get_data_from_post()
    
    /**
     * Set selection model: 1 - Selected, 2 - Not Selected
     * 
     * @param int $user_id
     * @param int $model
     */
    public function set_model($user_id, $model) {
        
        $model = URE_Plugins_Access_Controller::validate_model($model);        
        update_user_meta($user_id, $this->umk_model, $model);
        
    }
    // end of set_model()
    
    
    public function set_plugins($user_id, $plugins) {
        
        $plugins = URE_Plugins_Access_Controller::validate_plugins($plugins);
        update_user_meta($user_id, $this->umk_plugins, $plugins);
        
    }
    // end of set_plugins()
        
}
// end of URE_Plugins_Access_User_Controller