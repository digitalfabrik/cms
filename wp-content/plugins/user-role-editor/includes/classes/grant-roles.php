<?php
/**
 * Project: User Role Editor plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+
 * 
 * Assign multiple roles to the list of selected users directly from the "Users" page
 */

class URE_Grant_Roles {
    
    private $lib = null;
    
    public function __construct() {
        
        $this->lib = URE_Lib::get_instance();        
        
        add_action('restrict_manage_users', array($this, 'show_grant_roles_html'));
        add_action('admin_head', array(User_Role_Editor::get_instance(), 'add_css_to_users_page'));
        add_action('admin_enqueue_scripts', array($this, 'load_js'));
    }
    // end of __construct()
    
    
    private static function validate_users($users) {
        if (!is_array($users)) {
            return false;
        }
        
        foreach ($users as $user_id) {
            if (!is_numeric($user_id)) {
                return false;
            }
            if (!current_user_can('edit_user', $user_id)) {
                return false;
            }
        }
        
        return true;
    }
    // end of validate_users()
    
    
    private static function validate_roles($roles) {
        
        $editable_roles = get_editable_roles();
        $valid_roles = array_keys($editable_roles);
        foreach($roles as $role) {
            if (!in_array($role, $valid_roles)) {
                return false;
            }
        }
        
        return true;        
    }
    // end of validate_roles()
    
    
    private static function grant_roles_to_user($user_id, $roles) {
                        
        $user = get_user_by('id', $user_id);
        if (empty($user)) {
            return;
        }
        
        $user->remove_all_caps();
        foreach($roles as $role) {
            $user->add_role($role);
        }
        
    }
    // end of grant_roles_to_user()
    
    
    public static function process_user_request() {
            
        $users = $_POST['users'];        
        if (!self::validate_users($users)) {
            $answer = array('result'=>'error', 'message'=>esc_html__('Invalid data at the users list', 'user-role-editor'));
            return $answer;
        }
        
        $roles = $_POST['roles'];
        if (!self::validate_roles($roles)) {
            $answer = array('result'=>'error', 'message'=>esc_html__('Invalid data at the roles list', 'user-role-editor'));
            return;
        }
    
        if (!current_user_can('edit_users')) {
            $answer = array('result'=>'error', 'message'=>esc_html__('Not enough permissions', 'user-role-editor'));
            return $answer;
        }
        
        foreach($users as $user_id) {
            self::grant_roles_to_user($user_id, $roles); 
        }        
        
        $answer = array();
        
        return $answer;
    }
    // end of process_user_request()
    
    
    private function select_roles_html() {
        $show_admin_role = $this->lib->show_admin_role_allowed();
        $roles = get_editable_roles();
        foreach ($roles as $role_id => $role) {
            if (!$show_admin_role && $role_id=='administrator') {
                continue;
            }
            echo '<label for="wp_role_' . $role_id . '"><input type="checkbox"	id="wp_role_' . $role_id .
                 '" name="ure_roles[]" value="' . $role_id . '" />&nbsp;' .
            esc_html__($role['name'], 'user-role-editor') .' ('. $role_id .')</label><br />'. PHP_EOL;            
        }
    }
    // end of show_secondary_roles()
    
    
    public function show_grant_roles_html() {
        if (!$this->lib->is_right_admin_path('users.php')) {      
            return;
        }      
?>        
            &nbsp;&nbsp;<input type="button" name="ure_grant_roles" id="ure_grant_roles" class="button"
                        value="Grant Roles" onclick="ure_show_grant_roles_dialog();">
            <div id="ure_grant_roles_dialog" class="ure-dialog">
                <div id="ure_grant_roles_content" style="padding: 10px;">                    
<?php
                $this->select_roles_html();
?>                
                </div>
            </div>
            <div id="ure_task_status" style="display:none;position:absolute;top:10px;right:10px;padding:10px;background-color:#000000;color:#ffffff;">
                <img src="<?php echo URE_PLUGIN_URL .'/images/ajax-loader.gif';?>" width="16" height="16"/> <?php esc_html_e('Working...','user-role-editor');?>
            </div>
<?php        

        
    }
    // end of show_grant_roles_button()
    
    
    public function load_js() {
        wp_enqueue_script('jquery-ui-dialog', false, array('jquery-ui-core','jquery-ui-button', 'jquery') );
        wp_register_script('ure-users-grant-roles', plugins_url('/js/users-grant-roles.js', URE_PLUGIN_FULL_PATH));
        wp_enqueue_script('ure-users-grant-roles', '', array(), false, true);
        wp_localize_script('ure-users-grant-roles', 'ure_users_grant_roles_data', array(
            'wp_nonce' => wp_create_nonce('user-role-editor'),
            'dialog_title'=> esc_html__('Grant roles to selected users', 'user-role-editor'),
            'select_users_first'=> esc_html__('Select users to which you wish to grant multiple roles!', 'user-role-editor'),
            'select_roles_first'=> esc_html__('Select role(s) which you wish to grant!', 'user-role-editor')
        ));
    }
    // end of load_js()
    
}
// end of URE_Grant_Roles class
