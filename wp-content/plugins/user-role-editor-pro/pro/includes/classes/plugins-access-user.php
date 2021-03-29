<?php
/*
 * Access restriction to plugins on per user basis
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */

class URE_Plugins_Access_User {
    
    private $lib = null;
    public $controller = null;
    
    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        $this->controller = new URE_Plugins_Access_User_Controller();
        
        add_action('edit_user_profile', array($this, 'show'), 10);
        add_action('profile_update', array($this, 'save'), 10);
        add_action('admin_enqueue_scripts', array($this, 'load_js'));
        add_action('admin_print_styles-user-edit.php', array($this, 'load_css'));
        
    }
    // end of __construct()
    
    
    private function get_data_from_user_roles() {
        
        $current_user = wp_get_current_user();        
        $data = array(
            'model'=>0, 
            'plugins'=>''
            );
        if (empty($current_user->roles)) {            
            return $data;
        }
        
        $controller = new URE_Plugins_Access_Role_Controller();
        foreach(array_values($current_user->roles) as $role_id) {
            $role_data = $controller->load_data($role_id);
            if (empty($role_data['data']['plugins'])) {
                continue;
            }
            if ($data['model']==0 && $role_data['data']['plugins']!='') {
                $data['model'] = $role_data['selection_model'];
            }
            if ($data['model']!=$role_data['selection_model']) {
                continue;
            }
            if (!empty($data['plugins'])) {
                $data['plugins'] .= ',';
            }
            $data['plugins'] .= $role_data['data']['plugins'];
        }
        
        return $data;
    }
    // end of get_data_from_user_roles()
    
               
    
    private function add_data_from_roles($data) {
        
        $roles_data = $this->get_data_from_user_roles();
        if ($data['model']!=0) { // User level data exists, add data from roles to them
            if ($roles_data['model']==$data['model'] && !empty($roles_data['plugins'])) { // Add roles level data only if we get from roles the same selection model as at the user level
                $data_str = $data['plugins'] .','. $roles_data['plugins'];
                $data_arr = explode(',', $data_str);
                $data_arr = array_unique($data_arr);
                $data['plugins'] = implode(',', $data_arr);
            }
        } else {    // user level data not found, so use roles level data
            $data['model'] = $roles_data['model'];
            $data['plugins'] = $roles_data['plugins'];
        }

        return $data;
    }
    // end of add_data_from_roles()
    
    
    /**
     * Get allowed plugins data for this user as from roles level, as directly from a user level together
     * 
     * @return array
     */
    public function get_data() {

        $current_user_id = get_current_user_id();
        $data = array('model'=>0, 'plugins'=>'');                        
        $model = $this->controller->get_model($current_user_id);
        $plugins = $this->controller->get_plugins($current_user_id);
        if (!empty($plugins)) {
            $data['model'] = $model;
            $data['plugins'] = $plugins;
        }
        
        $data = $this->add_data_from_roles($data);        
        $data['model'] = URE_Plugins_Access_Controller::validate_model($data['model']);
        
        return $data;
    }
    // end of get_plugins()
    
    
    public function can_activate_plugins($user) {
        
        $result = $this->lib->user_has_capability($user, 'activate_plugins');        
        
        return $result;
    }
    // end of can_activate_plugins()
    
    
    /**
     * Load javascript
     * 
     * @param string $hook_suffix
     */
    public function load_js($hook_suffix) {
                
        if ($hook_suffix!=='user-edit.php') {
            return;
        }
        
        wp_enqueue_script('jquery-ui-dialog', '', array('jquery-ui-core', 'jquery-ui-button', 'jquery'));
        wp_register_script('ure-user-profile-plugins', plugins_url('/pro/js/plugins-access-user.js', URE_PLUGIN_FULL_PATH ), array(), URE_VERSION );
        wp_enqueue_script('ure-user-profile-plugins');
        wp_localize_script('ure-user-profile-plugins', 'ure_data_plugins_access', array(
            'wp_nonce' => wp_create_nonce('user-role-editor'),
            'allow_manage' => esc_html__('Allow manage', 'user-role-editor'),
            'update' => esc_html__('Update', 'user-role-editor'),
            'cancel' => esc_html__('Cancel', 'user-role-editor'),
            'selected' => esc_html__('selected plugins', 'user-role-editor'),
            'not_selected' => esc_html__('all plugins except selected', 'user-role-editor')
        ));
        
    }
    // end of load_js()
    
    
    public function load_css() {        
        
        wp_enqueue_style('wp-jquery-ui-dialog');        
        
    }
    // end of admin_css_action()

    
    
    public function show($user) {
        
        URE_Plugins_Access_User_View::show($user, $this);
        
    }
    // end of show()
    
    
    /**
     * Returns HTML markup with plugins list data for selected user
     */
    public function get_plugins_list_html() {
     
        $data = $this->controller->get_data_from_post();        
        if (empty($data['user_id'])) {
            $answer = array('result'=>'error', 'message'=>'User ID is required');
            return $answer;
        }
        if (empty($data['plugins'])) {
            $plugins = array();
        } else {
            $plugins = explode(',', $data['plugins']); 
        }
        $html = URE_Plugins_Access_View::get_plugins_list_html($plugins);
     
        $answer = array('result'=>'success', 'html'=>$html);
        
        return $answer;
    }
    // end of get_plugins_list_html()
    
    
    /**
     *  Save plugins access list when user profile is updated, 
     *  as WordPress itself doesn't know about this data
     * 
     * @param int $user_id
     * @return void
     */
    public function save($user_id) {

        if (!isset($_POST['ure_plugins_access_list'])) {
            return false;
        }
        
        if (!current_user_can('edit_users', $user_id) || !current_user_can(URE_Plugins_Access::CAPABILITY)) {
            return false;
        }

        $model = $this->lib->get_request_var('ure_plugins_access_model', 'post', 'int');
        $this->controller->set_model($user_id, $model);
        
        $plugings_list_str = $this->lib->get_request_var('ure_plugins_access_list', 'post');                            
        $this->controller->set_plugins($user_id, $plugings_list_str);
        
        return true;
    }
    // end of save()    
    
}
// end of URE_Plugins_Access_User