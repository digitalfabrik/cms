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
    private $blocked_users = null;

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
        add_action('pre_user_query', array($this, 'exclude_users_with_blocked_roles' ) );
        // Exclude blocked role(s) from users count
        add_filter( 'pre_count_users', array( $this, 'count_users'), 10, 3 );
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

    
    protected function not_block_local_admin() {
        
        $multisite = $this->lib->get('multisite');
        if ( $multisite ) { 
            $not_block_local_admin = apply_filters('ure_not_block_other_roles_for_local_admin', true);
        } else {
            $not_block_local_admin = true;
        }
        
        return $not_block_local_admin;
    }
    // end of not_block_local_admin()
    

    public function add_js() {
        
        $not_block_local_admin = $this->not_block_local_admin();
        
        wp_register_script( 'ure-other-roles-access', plugins_url( '/pro/js/other-roles-access.js', URE_PLUGIN_FULL_PATH ) );
        wp_enqueue_script ( 'ure-other-roles-access' );
        wp_localize_script( 'ure-other-roles-access', 'ure_data_other_roles_access',
                array(
                    'not_block_local_admin'=> $not_block_local_admin ? 1:0,
                    'other_roles' => esc_html__('Other Roles', 'user-role-editor'),
                    'dialog_title' => esc_html__('Other Roles Access', 'user-role-editor'),
                    'update_button' => esc_html__('Update', 'user-role-editor'),
                    'not_applicable_to_admin' => esc_html__('Blocking any role for "administrator" is not allowed.', 'user-role-editor'),
                    'edit_users_required' => esc_html__('Turn ON "edit_users" capability to manage access of current role to other roles', 'user-role-editor'),
                ) );
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
        
        $editor = URE_Editor::get_instance();
        if (!current_user_can(self::OTHER_ROLES_ACCESS_CAP)) {
            $editor->set_notification( esc_html__('URE: you have not enough permissions to use this add-on.', 'user-role-editor') );
            return;
        }
        $ure_object_type = filter_input(INPUT_POST, 'ure_object_type', FILTER_SANITIZE_STRING);
        if ($ure_object_type!=='role' && $ure_object_type!=='user') {
            $editor->set_notification( esc_html__('URE: other roles access: Wrong object type. Data was not updated.', 'user-role-editor') );
            return;
        }
        $ure_object_name = filter_input(INPUT_POST, 'ure_object_name', FILTER_SANITIZE_STRING);
        if (empty($ure_object_name)) {
            $editor->set_notification( esc_html__('URE: other roles access: Empty object name. Data was not updated', 'user-role-editor') );
            return;
        }
                        
        if ($ure_object_type=='role') {
            $this->objects->save_access_data_for_role($ure_object_name);
        } else {
            $this->objects->save_access_data_for_user($ure_object_name);
        }
        
        $editor->set_notification( esc_html__('Other roles access data was updated successfully', 'user-role-editor') );
        
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
        $blocked0 = $this->objects->load_data_for_user( $current_user );
        $blocked = apply_filters( 'ure_other_roles_access', $blocked0, $current_user );    
        if ( empty( $blocked['data'] ) ) {
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
    public function editable_roles( $roles ) {
        $blocked = $this->blocking_needed();
        if ( $blocked===false ) {
            return $roles;
        }                

        if ( $blocked['access_model']==1 ) {  // exclude selected
            foreach( $blocked['data'] as $role_id ) {
                if ( array_key_exists( $role_id, $roles ) ) {
                    unset( $roles[$role_id] );
                }
            }        
        } else {    // exclude not selected
            foreach( $roles as $role_id=>$role ) {
                if ( !in_array( $role_id, $blocked['data'] ) ) {
                    unset( $roles[$role_id] );
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
    private function get_users_to_block( $blocked ) {
        global $wpdb;
        
        if ( $blocked===false ) {
            return array();
        }
        
        if ( $this->blocked_users!==null ) {
            return $this->blocked_users;
        }
                
        if ($blocked['access_model']==1) {
            $blocked_roles = $blocked['data'];
        } else {
            $roles = $this->lib->get_user_roles();
            $roles_id = array_keys( $roles );
            $blocked_roles = array();
            foreach( $roles_id as $role_id ) {
                if ( !in_array( $role_id, $blocked['data'] ) ) {
                    $blocked_roles[] = $role_id;
                }
            }
        }
                        
        $meta_key = $wpdb->prefix . 'capabilities';
        $ids_arr = array();
        foreach( $blocked_roles as $role_id) {
            $query = $wpdb->prepare(
                    "SELECT user_id
                          FROM {$wpdb->usermeta}
                          WHERE meta_key=%s AND meta_value LIKE %s",
                    array($meta_key, '%"'. $role_id .'"%')
                                  );
            $ids_arr1 = $wpdb->get_col($query);
            $ids_arr = array_merge($ids_arr, $ids_arr1);
        }
        if (is_array($ids_arr) && count($ids_arr) > 0) {
            $this->blocked_users = array_unique( $ids_arr );
        } else {
            $this->blocked_users = array();
        }
        
        return $this->blocked_users;
    }
    // end of get_users_to_block()
    
    
    /**
     * add where criteria to exclude users with blocked roles from users list
     * 
     * @global wpdb $wpdb
     * @param  type $user_query
     */
    public function exclude_users_with_blocked_roles($user_query) {
        global $wpdb;
        
        $blocked = $this->blocking_needed();
        if ($blocked===false) {
            return;
        }

        $result = false;
        $links_to_block = array('profile.php', 'users.php');
        foreach ($links_to_block as $link) {
            $result = stripos($_SERVER['REQUEST_URI'], $link);
            if ($result !== false) {
                break;
            }
        }

        if ($result === false) { // block the user edit stuff only
            return;
        }
        
        $ids_arr = $this->get_users_to_block( $blocked );        
        if ( is_array( $ids_arr ) && count( $ids_arr ) > 0 ) {
            $ids = URE_Base_Lib::esc_sql_in_list('int', $ids_arr);
            $user_query->query_where .= " AND ( {$wpdb->users}.ID NOT IN ( $ids ) )";
        }
    }
    // end of exclude_users_with_blocked_roles()
    
    
    private function get_exclude_users_where( $blocked ) {
        global $wpdb;
        
        $blocked_users = $this->get_users_to_block( $blocked );
        if ( is_array( $blocked_users ) && count( $blocked_users ) > 0 ) {
             $user_ids = URE_Base_Lib::esc_sql_in_list( 'int', $blocked_users );
             $result = " AND ( {$wpdb->usermeta}.user_id NOT IN ( $user_ids ) )";
        } else {
            $result = '';
        }
        
        return $result;
    }
    // end of get_exclude_users_where()
    
    
    private function quick_count_users( $site_id, $blocked ) {
        global $wpdb;
        
        $result = array();
        $blog_prefix = $wpdb->get_blog_prefix( $site_id );
        if ( is_multisite() && $site_id != get_current_blog_id() ) {
                switch_to_blog( $site_id );
                $avail_roles = wp_roles()->get_names();
                restore_current_blog();
        } else {
                $avail_roles = wp_roles()->get_names();
        }
        // Excludes blocked roles
        $avail_roles = $this->editable_roles( $avail_roles );
            
        // Build a CPU-intensive query that will return concise information.
        $select_count = array();
        foreach ( $avail_roles as $this_role => $name ) {
            $select_count[] = $wpdb->prepare( 'COUNT(NULLIF(`meta_value` LIKE %s, false))', '%' . $wpdb->esc_like( '"' . $this_role . '"' ) . '%' );
        }
        $select_count[] = "COUNT(NULLIF(`meta_value` = 'a:0:{}', false))";
        $select_count = implode(', ', $select_count);
        $exclude_users_where = $this->get_exclude_users_where( $blocked );
            
        // Add the meta_value index to the selection list, then run the query.
        $row = $wpdb->get_row(
                "
                SELECT {$select_count}, COUNT(*)
                FROM {$wpdb->usermeta}
                INNER JOIN {$wpdb->users} ON user_id = ID
                WHERE meta_key = '{$blog_prefix}capabilities'
                      $exclude_users_where     
        ",
                ARRAY_N
        );

        // Run the previous loop again to associate results with role names.
        $col = 0;
        $role_counts = array();
        foreach ($avail_roles as $this_role => $name) {
            $count = (int) $row[$col++];
            if ($count > 0) {
                $role_counts[$this_role] = $count;
            }
        }

        $role_counts['none'] = (int) $row[ $col++ ];

        // Get the meta_value index from the end of the result set.
        $total_users = (int) $row[ $col ];

        $result['total_users'] = $total_users;
        $result['avail_roles'] =& $role_counts;
            
        return $result;
    }
    // end of quick_count_users()
    
    
    private function full_count_users( $site_id, $blocked ) {
        global $wpdb;
        
        $blog_prefix = $wpdb->get_blog_prefix( $site_id );
	$result = array(); 
        $roles = wp_roles()->get_names();
        $roles = array_keys( $roles );
        $editable_roles = $this->editable_roles( $roles );
        $avail_roles = array(
                    'none' => 0,
            );
        $exclude_users_where = $this->get_exclude_users_where( $blocked );
        $users_of_blog = $wpdb->get_col(
                "
                SELECT meta_value
                FROM {$wpdb->usermeta}
                INNER JOIN {$wpdb->users} ON user_id = ID
                WHERE meta_key = '{$blog_prefix}capabilities'
                      $exclude_users_where                    
        "
        );

        foreach ( $users_of_blog as $caps_meta ) {
                $b_roles = maybe_unserialize( $caps_meta );
                if ( ! is_array( $b_roles ) ) {
                        continue;
                }
                if ( empty( $b_roles ) ) {
                        $avail_roles['none']++;
                }
                foreach ( $b_roles as $b_role => $val ) {
                    if ( isset( $avail_roles[ $b_role ] ) ) {
                        $avail_roles[ $b_role ]++;
                    } else if ( in_array( $b_role, $editable_roles ) ) {
                        $avail_roles[ $b_role ] = 1;
                    }
                }
        }

        $result['total_users'] = count( $users_of_blog );
        $result['avail_roles'] =& $avail_roles;
        
    }
    // end of full_count_users()
    
    
    /**
     * Override the WordPress version 5.3.2 original count_users() from wp-includes/user.php, #870
     * @param null|array $result
     * @param string $strategy  The computational strategy to use when counting the users.
     *                          Accepts either 'time' or 'memory'. Default 'time'.
     * @param int|null $site_id The site ID to count users for. Defaults to the current site.
     */
    public function count_users( $result, $strategy, $site_id ) {

        $blocked = $this->blocking_needed();
        if ($blocked===false) {
            return $result;
        }
        
	// Initialize
	if ( ! $site_id ) {
            $site_id = get_current_blog_id();
	}     
                
	if ( 'time' == $strategy ) {
            $result = $this->quick_count_users( $site_id, $blocked );
	} else {
            $result = $this->full_count_users( $site_id, $blocked );            
	}

	return $result;                
    }
    // end of count_users()
    
    
    private function block_selected($blocked_roles, &$views) {
        
        foreach($blocked_roles as $role_id) {
            if ( !isset( $views[$role_id] ) ) {
                continue;
            }
            unset($views[$role_id]);                
        }
        
    }
    // end of block_selected()
    
    
    private function block_not_selected($selected_roles, &$views) {
        
        foreach( array_keys( $views ) as $role_id ) {
            if ( $role_id=='all' || in_array( $role_id, $selected_roles ) ) {
                continue;
            }
            if ( !isset( $views[$role_id] ) ) {
                continue;
            }
            unset($views[$role_id]);
        }
        
    }
    // end of block_not_selected()
            
    
    /**
     * Hide blocked roles tabs from the Users list
     * 
     * @param array $views
     * @return array
     */
    public function exclude_blocked_roles_view( $views ) {
        
        $blocked = $this->blocking_needed();
        if ( $blocked===false ) {
            return $views;
        }
                    
        if ( $blocked['access_model']==1 ) {  // block selected
            $this->block_selected( $blocked['data'], $views );
        } else {    // block not selected
            $this->block_not_selected( $blocked['data'], $views );            
        }                
        
        return $views;
    }
    // end of exclude_blocked_roles_view()

    
    private function get_blocked_roles( $blocked ) {
        
        if ($blocked['access_model']==1) {  // block selected
            $blocked_roles = $blocked['data'];
        } else {    // block not selected
            $roles = array_keys( wp_roles()->roles );
            $blocked_roles = array();
            foreach( $roles as $role_id ) {
                if ( !in_array( $role_id, $blocked['data'] ) ) {
                    $blocked_roles[] = $role_id;
                }
            }
        }
        
        return $blocked_roles;
    }
    // end of get_blocked_roles()
    
            
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
            $blocked_roles = $this->get_blocked_roles( $blocked );            
            foreach( $blocked_roles as $role_id ) {
                $access_deny = in_array( $role_id, $user->roles );
                if ( $access_deny && isset( $allcaps[$cap] ) ) {
                    unset( $allcaps[$cap] );
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
