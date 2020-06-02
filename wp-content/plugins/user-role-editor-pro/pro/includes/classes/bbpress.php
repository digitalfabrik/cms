<?php
/**
 * Support for bbPress user roles and capabilities editing - Make bbPress roles editable
 * 
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * 
 **/

class URE_bbPress_Pro extends URE_bbPress {

    private $caps = null;
    
    
    public function __construct() {
        
        parent::__construct();
        
        add_action('plugins_loaded', array($this, 'do_not_reload_roles'), 9);
        add_filter('bbp_get_caps_for_role', array($this, 'get_caps_for_role'), 10, 2);
        add_action( 'wp_roles_init', array($this, 'add_forums_roles'), 10 );
        add_filter('ure_capabilities_groups_tree', array($this, 'add_bbpress_caps_group'), 10, 1);
        add_filter('ure_custom_capability_groups', array($this, 'get_capability_group'), 10, 2);
        
    }
    // end of __construct()
    
    
    /**
     * Exclude roles created by bbPress
     * 
     * @global array $wp_roles
     * @return array
     */
    public function get_roles() {
        
        $wp_roles = wp_roles();
        
        return $wp_roles->roles;
    }
    // end of get_roles()
    
    /**
     * Replace bbPress bbp_add_forums_roles() in order to not overwrite bbPress roles loaded from the database
     * 
     * @param array $wp_roles
     * @return array
     */
    public function add_forums_roles($wp_roles = null) {
        
        // Attempt to get global roles if not passed in & not mid-initialization
        if ((null === $wp_roles) && !doing_action('wp_roles_init')) {
            $wp_roles = wp_roles();
        }

        if (!$this->is_active()) {
            return $wp_roles;
        }
        
        $bbp_roles = bbp_get_dynamic_roles();
        // Loop through dynamic roles and add them (if needed) to the $wp_roles array
        foreach ($bbp_roles  as $role_id=>$details) {
            if (isset($wp_roles->roles[$role_id])) {
                continue;
            }
            $wp_roles->roles[$role_id] = $details;
            $wp_roles->role_objects[$role_id] = new WP_Role( $role_id, $details['capabilities']);
            $wp_roles->role_names[$role_id] = $details['name'];
        }
        
        return $wp_roles;
    }
    // end of add_forums_roles()
    
    
    /**
     * Returns true if role does not include any capability, false in other case
     * @param array $caps - list of capabilities: cap=>1 or cap=>0
     * @return boolean
     */
    private function is_role_without_caps($caps) {
        if (empty($caps)) {
            return true;
        }
        
        if (!is_array($caps) || count($caps)==0) {
            return true;
        }
        
        $nocaps = true;
        foreach($caps as $turned_on) {
            if ($turned_on) {
                $nocaps = false;
                break;
            }
        }
        
        return $nocaps;        
    }
    // end of is_role_without_caps()
    
    
    public function get_caps_for_role($caps, $role_id) {
    
        if ($this->is_active()) {
            $bbp_roles = array(
                bbp_get_keymaster_role(),
                bbp_get_moderator_role(),
                bbp_get_participant_role(),
                bbp_get_spectator_role(),
                bbp_get_blocked_role()
                );
        } else {
            $bbp_roles = array();
        }
        
        if (!in_array($role_id, $bbp_roles)) {
            return $caps;
        }
        
        // to exclude endless recursion
        remove_filter('bbp_get_caps_for_role', array($this, 'get_caps_for_role'), 10);
        
        $wp_roles = wp_roles();

        // restore it back
        add_filter('bbp_get_caps_for_role', array($this, 'get_caps_for_role'), 10, 2);
        
        if (!isset($wp_roles->roles[$role_id]) ||
            $this->is_role_without_caps($wp_roles->roles[$role_id]['capabilities'])) {
            return $caps;
        }
        
        $caps = $wp_roles->roles[$role_id]['capabilities'];
        
        return $caps;
    }
    // end of get_caps_for_role()
    
    
    public function do_not_reload_roles() {
        remove_action('bbp_loaded', 'bbp_filter_user_roles_option',  16);
        remove_action( 'bbp_roles_init', 'bbp_add_forums_roles', 8 );
        remove_action('bbp_deactivation', 'bbp_remove_caps');
        register_uninstall_hook('bbpress/bbpress.php', 'bbp_remove_caps');
    }
    // end of do_not_reload_roles()
    
    
    public function get_bbp_editable_roles() {
        
        if ($this->is_active()) {
            $all_bbp_roles = bbp_get_dynamic_roles();
        } else {
            $all_bbp_roles = array();
        }
        
        return $all_bbp_roles;        
    }
    // end of get_bbp_editable_roles()
    

    /**
     * Return bbPress roles found at $roles array. Used to exclude bbPress roles from processing, as free version should not support them
     * 
     * @param array $roles
     * @return array
     */
    public function extract_bbp_roles($roles) {
        
        $roles = array();  // Pro version supports processing bbPress roles, so there is no need to exclude them
                
        return $roles;
    }
    // end of extract_bbp_roles()
    
    
    public function add_bbpress_caps_group($groups) {
        
        if ($this->is_active()) {
            $groups['bbpress'] = array('caption'=>esc_html__('bbPress', 'user-role-editor'), 'parent'=>'custom', 'level'=>3);
        }
        
        return $groups;
    }
    // end of add_bbpress_caps_groups()

    
    public function get_capability_group($groups, $cap) {
        
        if (!$this->is_active()) {
            return $groups;
        }
        
        if (empty($this->caps)) {
            $this->caps = bbp_get_caps_for_role(bbp_get_keymaster_role());
        }
        
        if (isset($this->caps[$cap])) {
            $groups[] = 'bbpress';
        }
        
        return $groups;
    }
    // end of bbpress_cap_groups
    
}
// end of URE_bbPress_Pro class