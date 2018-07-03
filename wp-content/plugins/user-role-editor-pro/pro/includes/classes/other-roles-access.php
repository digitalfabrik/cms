<?php

/*
 * User Role Editor WordPress plugin
 * Class URE_Other_Roles_Access - prohibit access to the selected roles on per role/user basis
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Other_Roles_Access {

    const OTHER_ROLES_ACCESS_CAP = 'ure_other_roles_access';
    
    // reference to the code library object
    private $lib = null;        
    private $objects = null;
    private $notice = '';

    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        $this->objects = new URE_Other_Roles();
        
        add_action('ure_role_edit_toolbar_service', array(&$this, 'add_toolbar_buttons'));
        add_action('ure_load_js', array(&$this, 'add_js'));
        add_action('ure_dialogs_html', array(&$this, 'dialog_html'));
        add_action('ure_process_user_request', array(&$this, 'update_data'));

        // prohibit any actions with user who has blocked role
        add_filter('user_has_cap', array($this, 'not_edit_user_with_blocked_roles' ), 10, 3);
        // exclude blocked roles from WordPress dropdown menus
        add_action('editable_roles', array($this, 'editable_roles'), 100);
        // Exclude users with blocked roles from users list
        add_action('pre_user_query', array($this, 'exclude_blocked_roles' ) );
        // do not show 'Blocked Role(s)' view above users list
        add_filter('views_users',  array($this, 'exclude_blocked_roles_view' ) );
    }
    // end of __construct()

    
    public function add_toolbar_buttons() {
        if (current_user_can(self::OTHER_ROLES_ACCESS_CAP)) {
?>                
        <button id="ure_other_roles_access_button" class="ure_toolbar_button" title="<?php esc_html__('Prohibit access to selected roles','user-role-editor');?>">Other Roles</button>
<?php

        }
    }
    // end of add_toolbar_buttons()


    public function add_js() {
        wp_register_script( 'ure-other-roles-access', plugins_url( '/pro/js/other-roles-access.js', URE_PLUGIN_FULL_PATH ) );
        wp_enqueue_script ( 'ure-other-roles-access' );
        wp_localize_script( 'ure-other-roles-access', 'ure_data_other_roles_access',
                array(
                    'other_roles' => esc_html__('Other Roles', 'user-role-editor'),
                    'dialog_title' => esc_html__('Other Roles Access', 'user-role-editor'),
                    'update_button' => esc_html__('Update', 'user-role-editor'),
                    'edit_users_required' => esc_html__('Turn ON "edit_users" capability to manage access of current role to other roles', 'user-role-editor')                    
                ));
    }
    // end of add_js()    
    
    
    public function dialog_html() {
        
?>
        <div id="ure_other_roles_access_dialog" class="ure-modal-dialog">
            <div id="ure_other_roles_access_container">
            </div>    
        </div>
<?php        
        
    }
    // end of dialog_html()

            
    public function update_data() {
    
        if (!isset($_POST['action']) || $_POST['action']!=='ure_update_other_roles_access') {
            return;
        }
        
        if (!current_user_can(self::OTHER_ROLES_ACCESS_CAP)) {
            $this->lib->set_notification( esc_html__('URE: you have not enough permissions to use this add-on.', 'user-role-editor') );
            return;
        }
        $ure_object_type = filter_input(INPUT_POST, 'ure_object_type', FILTER_SANITIZE_STRING);
        if ($ure_object_type!=='role' && $ure_object_type!=='user') {
            $this->lib->set_notification( esc_html__('URE: other roles access: Wrong object type. Data was not updated.', 'user-role-editor') );
            return;
        }
        $ure_object_name = filter_input(INPUT_POST, 'ure_object_name', FILTER_SANITIZE_STRING);
        if (empty($ure_object_name)) {
            $this->lib->set_notification( esc_html__('URE: other roles access: Empty object name. Data was not updated', 'user-role-editor') );
            return;
        }
                        
        if ($ure_object_type=='role') {
            $this->objects->save_access_data_for_role($ure_object_name);
        } else {
            $this->objects->save_access_data_for_user($ure_object_name);
        }
        
        $this->lib->set_notification( esc_html__('Other roles access data was updated successfully', 'user-role-editor') );
        
    }
    // end of update_data()
               
    
    protected function blocking_needed() {
                        
        $multisite = $this->lib->get('multisite');
        if ($multisite) { 
            if ($this->lib->is_super_admin()) {
                // do not block data for superadmin
                return false;
            }
            $not_block_local_admin = apply_filters('ure_not_block_other_roles_for_local_admin', true);
        } else {
            $not_block_local_admin = true;
        }
        
        $current_user = wp_get_current_user();
        // do not block data for local administrator
        if ($not_block_local_admin && $this->lib->user_has_capability($current_user, 'administrator')) {
            return false;
        }
        
        // user can update access to other roles
        if ($this->lib->user_has_capability($current_user, self::OTHER_ROLES_ACCESS_CAP)) {
            return false;
        }

        // load data to block
        $blocked = $this->objects->load_data_for_user($current_user);
        if (empty($blocked['data'])) {
            return false;
        }
        
        return $blocked;
    }
    // end of blocking_needed()
    
    
    /**
     * Exclude blocked roles from WordPress drop-down menus
     * @param array $roles
     * @return array
     */
    public function editable_roles($roles) {
        $blocked = $this->blocking_needed();
        if ($blocked===false) {
            return $roles;
        }                

        if ($blocked['access_model']==1) {  // exclude selected
            foreach($blocked['data'] as $role_id) {
                if (array_key_exists($role_id, $roles)) {
                    unset($roles[$role_id]);
                }
            }        
        } else {    // exclude not selected
            foreach($roles as $role_id=>$role) {
                if (!in_array($role_id, $blocked['data'])) {
                    unset($roles[$role_id]);
                }
            }
        }
        
        return $roles;
    }
    // end of editable_roles()
    

    /**
     * Get user_id of users with blocked roles
     * @param array $blocked 
     */
    private function get_users_to_block($blocked) {
        global $wpdb;
        
        $meta_key = $wpdb->prefix . 'capabilities';
        $ids_arr = array();
        if ($blocked['access_model']==1) {
            foreach($blocked['data'] as $role_id) {
                $query = $wpdb->prepare(
                        "SELECT user_id
                              FROM {$wpdb->usermeta}
                              WHERE meta_key=%s AND meta_value LIKE %s",
                        array($meta_key, "%$role_id%")
                                      );
                $ids_arr1 = $wpdb->get_col($query);
                $ids_arr = array_merge($ids_arr, $ids_arr1);
            }
            if (is_array($ids_arr) && count($ids_arr) > 0) {
                $ids_arr = array_unique($ids_arr);
            }
        } else {            
            $query = $wpdb->prepare(
                    "SELECT user_id
                          FROM {$wpdb->usermeta}
                          WHERE meta_key=%s ",
                    array($meta_key)); 
            foreach($blocked['data'] as $role_id) {
                $query .= $wpdb->prepare(" AND meta_value NOT LIKE %s", array("%$role_id%"));
            }              
            $ids_arr = $wpdb->get_col($query);
        }        
        
        return $ids_arr;
    }
    // end of get_users_to_block()
    
    
    /**
     * add where criteria to exclude users with blocked roles from users list
     * 
     * @global wpdb $wpdb
     * @param  type $user_query
     */
    public function exclude_blocked_roles($user_query) {
        global $wpdb;
        
        $blocked = $this->blocking_needed();
        if ($blocked===false) {
            return;
        }

        $result = false;
        $links_to_block = array('profile.php', 'users.php');
        foreach ($links_to_block as $key => $value) {
            $result = stripos($_SERVER['REQUEST_URI'], $value);
            if ($result !== false) {
                break;
            }
        }

        if ($result === false) { // block the user edit stuff only
            return;
        }
        
        $ids_arr = $this->get_users_to_block($blocked);        
        if (is_array($ids_arr) && count($ids_arr) > 0) {
            $ids = URE_Base_Lib::esc_sql_in_list('int', $ids_arr);
            $user_query->query_where .= " AND ( {$wpdb->users}.ID NOT IN ( $ids ) )";
        }
    }
    // end of exclude_blocked_roles()
    

    private function extract_view_quantity($text) {
        $match = array();
        $result = preg_match('#\((.*?)\)#', $text, $match);
        if ($result) {
            $quantity = $match[1];
        } else {
            $quantity = 0;
        }
        
        return $quantity;
    }
    // end of extract_view_quantity()

    
    private function extract_view_quantity_int($text) {
        
        $quant_orig = $this->extract_view_quantity($text);
        $quant_int = str_replace(',', '', $quant_orig);
        
        return $quant_int;
    }
    // end of extract_view_quantity_int()
    
    
    private function block_selected($blocked_roles, &$views) {
        
        $hidden_quant = 0;
        foreach($blocked_roles as $role_id) {
            if (!isset($views[$role_id])) {
                continue;
            }
            if (isset($views['all'])) {
                $quant = $this->extract_view_quantity_int($views[$role_id]);
                $hidden_quant += $quant;
            }
            unset($views[$role_id]);                
        }
        
        return $hidden_quant;
    }
    // end of block_selected()
    
    
    private function block_not_selected($selected_roles, &$views) {
        
        $hidden_quant = 0;
        foreach(array_keys($views) as $role_id) {
            if ($role_id=='all' || in_array($role_id, $selected_roles)) {
                continue;
            }
            if (!isset($views[$role_id])) {
                continue;
            }
            if (isset($views['all'])) {
                $quant = $this->extract_view_quantity_int($views[$role_id]);
                $hidden_quant += $quant;
            }
            unset($views[$role_id]);
        }
        
        return $hidden_quant;
    }
    // end of block_not_selected()
    
    
    /**
     * Hide blocked roles tabs from the Users list
     * 
     * @param array $views
     * @return array
     */
    public function exclude_blocked_roles_view($views) {
        $blocked = $this->blocking_needed();
        if ($blocked===false) {
            return $views;
        }
                    
        if ($blocked['access_model']==1) {  // block selected
            $hidden_quant = $this->block_selected($blocked['data'], $views);
        } else {    // block not selected
            $hidden_quant = $this->block_not_selected($blocked['data'], $views);            
        }
        
        $all_orig = $this->extract_view_quantity($views['all']);
        $all_orig_int = str_replace(',', '', $all_orig);
        $all_new = $all_orig_int - $hidden_quant;
        $views['all'] = str_replace($all_orig, $all_new, $views['all']);
        
        
        return $views;
    }
    // end of exclude_blocked_roles_view()

            
    /**
     * We have two vulnerable queries with user id at admin interface, which should be controled
     * 1st: http://blogdomain.com/wp-admin/user-edit.php?user_id=ID
     * 2nd: http://blogdomain.com/wp-admin/users.php?action=delete&user=ID
     * If put user ID into such request, user with limited role can edit / delete that user record
     * This function removes 'edit_users', 'delete_users' capabilities from current user capabilities
     * if request has user ID with blocked role
     *
     * @param array $allcaps
     * @param type $caps
     * @param string $name
     * @return array
     */
    public function not_edit_user_with_blocked_roles($allcaps, $caps, $name) {

        //$cap = (is_array($caps) & count($caps)>0) ? array_values($caps)[0] : $caps;   
        // For compatibility with PHP versions prior 5.5
        if (is_array($caps) & count($caps)>0) {
            $caps_val = array_values($caps);
            $cap = $caps_val[0];
        } else {
            $cap = $caps;
        }
        
        $checked_caps = array('edit_users', 'delete_users', 'remove_users');
        if (!in_array($cap, $checked_caps)) {
            return $allcaps;
        }
        
        $blocked = $this->blocking_needed();        
        if ($blocked===false) {
            return $allcaps;
        }
                        
        $user_keys = array('user_id', 'user');
        foreach ($user_keys as $user_key) {
            $access_deny = false;
            $user_id = (int) $this->lib->get_request_var($user_key, 'get', 'int');
            if (empty($user_id)) {
                return $allcaps;
            }
            $user = get_user_by('id', $user_id);
            foreach($blocked['data'] as $role_id) {
                $access_deny = in_array($role_id, $user->roles);
                if ($access_deny && isset($allcaps[$cap])) {
                    unset($allcaps[$cap]);
                    break;
                }                            
            }
            if ($access_deny) {
                break;
            }
        }

        return $allcaps;
    }
    // end of not_edit_user_with_blocked_roles()    
    
}
// end of URE_Other_Roles_Access class