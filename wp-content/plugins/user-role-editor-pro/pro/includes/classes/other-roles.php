<?php

/*
 * User Role Editor WordPress plugin
 * Class URE_Other_Roles - support stuff for "Other Roles Access" add-on
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Other_Roles {

    private $lib = null;
    const ACCESS_DATA_KEY = 'ure_other_roles_access_data';
    
    
    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        
    }
    // end of __construct()
    
        
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
                $result['access_model'] = 1; // Selected
            }
        } else {
            $result = array('access_model'=>1, // Selected
                            'data'=>array());
        }
        
        return $result;
    }
    // end of load_data_for_role()
    
    
    /**
     * Merge roles access restrictions for all roles assigned to the user
     * @param WP_User $user
     * @param array $access_data
     * @param array $blocked
     * @return array
     */
    private function get_access_data_for_user_roles($user, $blocked) {
    
        if (!is_array($user->roles) || count($user->roles)==0) {
            return $blocked;
        }
        
        $access_data = get_option(self::ACCESS_DATA_KEY);
        if (empty($access_data) || !is_array($access_data)) {
            return $blocked;
        }
        
        foreach ($user->roles as $role) {
            if (!isset($access_data[$role])) {
                continue;
            }
            if (!isset($access_data[$role]['access_model'])) { // for backward compatibility
                $access_model = 1;   // Use default (block selected) access model
                $data = $access_data[$role];
            } else {
                $access_model = $access_data[$role]['access_model'];
                $data = $access_data[$role]['data'];
            }
            if (empty($blocked['access_model'])) {  
                $blocked['access_model'] = $access_model;    // take the 1st found role's access model as the main one                    
            }
            // take into account data with the same access model only as the 1st one found
            if ($access_model==$blocked['access_model'] && is_array($data)) {
                $blocked['data'] = array_merge($blocked['data'], $data);
            }            
        }   // foreach(...)
        
        return $blocked;
    }
    // end of get_access_data_for_user_roles()
    
    
    public function load_data_for_user($user) {
    
        $user = $this->lib->get_user($user);
        $blocked = get_user_meta($user->ID, self::ACCESS_DATA_KEY, true);
        if (!is_array($blocked)) {
            $blocked = array('access_model'=>0, 'data'=>array());
        }                            
        $blocked = $this->get_access_data_for_user_roles($user, $blocked);        
        if (empty($blocked['access_model'])) {
            $blocked['access_model'] = 1; // use default value
        }
        $blocked['data'] = array_unique($blocked['data']); //!!! Test
        
        return $blocked;
    }
    // end of load_data_for_user()

    
    protected function get_access_data_from_post() {
        global $wp_roles;
        
        $keys_to_skip = array(
            'action', 
            'ure_nonce', 
            '_wp_http_referer', 
            'ure_object_type', 
            'ure_object_name', 
            'user_role', 
            'ure_access_model');
        $access_model = $_POST['ure_access_model'];
        if ($access_model!=1 && $access_model!=2) { // got invalid value
            $access_model = 1;  // use default value
        }        
        $access_data = array('access_model'=>$access_model, 'data'=>array());
        foreach ($_POST as $key=>$value) {
            if (in_array($key, $keys_to_skip)) {
                continue;
            }
            if (!isset($wp_roles->role_names[$key])) {
                continue;
            }
            $access_data['data'][] = filter_var($key, FILTER_SANITIZE_STRING);
        }
        
        return $access_data;
    }
    // end of get_access_data_from_post()
        
    
    public function save_access_data_for_role($role_id) {
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
    
    
    public function save_access_data_for_user($user_login) {
        $access_for_user = $this->get_access_data_from_post();
        // TODO ...
    }
    // end of save_menu_access_data_for_role()   
                    
    
    protected function get_allowed_roles($user) {
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
                            
    
    public function get_html($user=null) {        
                        
        $allowed_roles = $this->get_allowed_roles($user);                
        $roles = $this->lib->get_user_roles();
        if (empty($user)) {
            $ure_object_type = 'role';
            $ure_object_name = $allowed_roles[0];
            $blocked_items = self::load_data_for_role($ure_object_name);
        } else {
            $ure_object_type = 'user';
            $ure_object_name = $user->user_login;
            $blocked_items = $this->load_data_for_user($ure_object_name);
        }
        $network_admin = filter_input(INPUT_POST, 'network_admin', FILTER_SANITIZE_NUMBER_INT);
        
        ob_start();
?>
<form name="ure_other_roles_access_form" id="ure_other_roles_access_form" method="POST"
      action="<?php echo URE_WP_ADMIN_URL . ($network_admin ? 'network/':'') . URE_PARENT.'?page=users-'.URE_PLUGIN_FILE;?>" >
    <span style="font-weight: bold;"><?php echo esc_html_e('Block roles:', 'user-role-editor');?></span>&nbsp;&nbsp;
    <input type="radio" name="ure_access_model" id="ure_access_model_selected" value="1" 
        <?php echo ($blocked_items['access_model']==1) ? 'checked="checked"' : '';?> > <label for="ure_access_model_selected"><?php esc_html_e('Selected', 'user-role-editor');?></label> 
    <input type="radio" name="ure_access_model" id="ure_access_model_not_selected" value="2" 
        <?php echo ($blocked_items['access_model']==2) ? 'checked="checked"' : '';?> > <label for="ure_access_model_not_selected"><?php esc_html_e('Not Selected', 'user-role-editor');?></label>
    <hr/>
<table id="ure_other_roles_access_table">
    <th><input type="checkbox" id="ure_other_roles_select_all"></th>
    <th><?php esc_html_e('Role Name','user-role-editor');?></th>
    <th><?php esc_html_e('Role ID', 'user-role-editor');?></th>    
<?php
        foreach($roles as $role_id=>$role) {            
?>
    <tr>
        <td>   
<?php     
            $checked = in_array($role_id, $blocked_items['data']) ? 'checked' : '';
            $cb_class = 'ure-cb-column';
            $disabled = '';
            if ($role_id=='administrator') {
                $disabled = 'disabled';
                if ($blocked_items['access_model']==1) {
                    $checked = 'checked';
                } else {
                    $checked = '';
                }
                $cb_class = '';
            }
?>
            <input type="checkbox" name="<?php echo $role_id;?>" id="<?php echo $role_id;?>" class="<?php echo $cb_class;?>" <?php echo $checked .' '. $disabled;?> />
        </td>
        <td><?php echo $role['name'];?></td>
        <td><?php echo $role_id;?></td>
    </tr>        
<?php
        }   // foreach($roles)
?>
</table> 
    <input type="hidden" name="action" id="action" value="ure_update_other_roles_access" />
    <input type="hidden" name="ure_object_type" id="ure_object_type" value="<?php echo $ure_object_type;?>" />
    <input type="hidden" name="ure_object_name" id="ure_object_name" value="<?php echo $ure_object_name;?>" />
<?php
    if ($ure_object_type=='role') {
?>
    <input type="hidden" name="user_role" id="ure_role" value="<?php echo $ure_object_name;?>" />
<?php
    }
?>
    <?php wp_nonce_field('user-role-editor', 'ure_nonce'); ?>
</form>    
<?php    
        $html = ob_get_contents();
        ob_end_clean();
        
        if (!empty($user)) {
            $current_object = $user->user_login;
        } else {
            $current_object = $allowed_roles[0];
        }
     
        return array('result'=>'success', 'message'=>'Other Roles permissions for '+ $current_object, 'html'=>$html);
    }
    // end of get_html()

}
// end of URE_Other_Roles class