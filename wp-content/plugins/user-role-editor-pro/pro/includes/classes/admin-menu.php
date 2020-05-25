<?php

/*
 * User Role Editor Pro WordPress plugin
 * Class URE_Admin_Menu - support stuff for manipulations with WP admin dashboard menu access
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://role-editor.com
 * License: GPL v2+ 
 */

class URE_Admin_Menu {
    
    const DATA_VERSION = 422;
    const DATA_VERSION_KEY = 'ure_admin_menu_access_data_version';    
    const ADMIN_MENU_COPY_KEY = 'ure_admin_menu_copy';
    const ADMIN_SUBMENU_COPY_KEY = 'ure_admin_sub_menu_copy';
    const ACCESS_DATA_KEY = 'ure_admin_menu_access_data';
    // full list of hashes for the all admin menu/submenu items
    const ADMIN_MENU_HASHES = 'ure_admin_menu_hashes';

    
    public static function load_data_for_role($role_id) {
        
        $ure_menu_access_data = get_option(self::ACCESS_DATA_KEY);
        if (is_array($ure_menu_access_data) && array_key_exists($role_id, $ure_menu_access_data)) {
            $result =  $ure_menu_access_data[$role_id];
            if (!isset($result['access_model'])) {
                $result['data'] = $result;
                $result['access_model'] = 1; // Selected
            }
            if (!isset($result['data'])) {
                $result['data'] = array();
            }
        } else {
            $result = array('access_model'=>1, // Selected
                            'data'=>array()); 
        }
        
        return $result;
    }
    // end of load_menu_access_data_for_role()
    
    
    public static function load_data_for_user($user) {
    
        if (is_object($user)) {
            $id = $user->ID;
        } else if (is_int($user)) {
            $id = $user;
            $user = get_user_by('id', $user);
        } else {
            $user = get_user_by('login', $user);
            $id = $user->ID;
        }
        
        $blocked = get_user_meta($user->ID, self::ACCESS_DATA_KEY, true);
        if (!is_array($blocked)) {
            $blocked = array('access_model'=>0, 'data'=>array());
        }
        
        $ure_menu_access_data = get_option(self::ACCESS_DATA_KEY);
        if (empty($ure_menu_access_data)) {
            $ure_menu_access_data = array();
        }
        
        if (!empty($user->roles)) {
            foreach ($user->roles as $role) {
                if (isset($ure_menu_access_data[$role])) {
                    if (!isset($ure_menu_access_data[$role]['access_model'])) { // for backward compatibility
                        $access_model = 1;   // Use default (block selected) access model
                        $data = $ure_menu_access_data[$role];
                    } else {
                        $access_model = $ure_menu_access_data[$role]['access_model'];
                        if (isset($ure_menu_access_data[$role]['data'])) {
                            $data = $ure_menu_access_data[$role]['data'];
                        } else {
                            $data = array();
                        }
                    }
                    if (empty($blocked['access_model'])) {  
                        $blocked['access_model'] = $access_model;    // take the 1st found role's access model as the main one                    
                    }
                    // take into account data with the same access model only as the 1st one found
                    if ($access_model==$blocked['access_model']) {
                        $blocked['data'] = array_merge($blocked['data'], $data);
                    }
                }
            }
        }
        
        if (empty($blocked['access_model'])) {
            $blocked['access_model'] = 1; // use default value
        }
        $blocked['data'] = array_unique ($blocked['data']);
        
        return $blocked;
    }
    // end of load_menu_access_data_for_user()

    
    public static function get_menu_hashes() {
        
        $hashes = get_option(self::ADMIN_MENU_HASHES);
        
        return $hashes;
    }
    // end of get_menu_hashes()
    
    
    private static function get_menu_access_post_data() {
        
        $keys_to_skip = array(
            'action', 
            'ure_nonce', 
            '_wp_http_referer', 
            'ure_object_type', 
            'ure_object_name', 
            'user_role', 
            'ure_admin_menu_access_model');
        
        $access_model = $_POST['ure_admin_menu_access_model'];
        if ($access_model!=1 && $access_model!=2) { // got invalid value
            $access_model = 1;  // use default value
        }        
        $menu_access_data = array('access_model'=>$access_model);
        
        foreach ($_POST as $key=>$value) {
            if (in_array($key, $keys_to_skip)) {
                continue;
            }
            $menu_access_data['data'][] = filter_var($key, FILTER_SANITIZE_STRING);
        }
        
        return $menu_access_data;
    }
    // end of get_menu_access_post_data()
        
    
    public static function save_menu_access_data_for_role($role_id) {
        global $wp_roles;
        
        $menu_access_for_role = self::get_menu_access_post_data();
        $menu_access_data = get_option(self::ACCESS_DATA_KEY);        
        if (!is_array($menu_access_data)) {
            $menu_access_data = array();
        }
        if (count($menu_access_for_role)>0) {
            $menu_access_data[$role_id] = $menu_access_for_role;
        } else {
            unset($menu_access_data[$role_id]);
        }
        foreach (array_keys($menu_access_data) as $role_id) {
            if (!isset($wp_roles->role_names[$role_id])) {
                unset($menu_access_data[$role_id]);
            }
        }
        update_option(self::ACCESS_DATA_KEY, $menu_access_data);
    }
    // end of save_menu_access_data_for_role()
    
    
    public static function save_menu_access_data_for_user($user_login) {
                
        // under development
        
    }
    // end of save_menu_access_data_for_user()   


    public static function update_data() {
    
        if (!isset($_POST['action']) || $_POST['action']!=='ure_update_admin_menu_access') {            
            return;
        }
        
        $editor = URE_Editor::get_instance();
        
        if (!current_user_can('ure_admin_menu_access')) {
            $editor->set_notification( esc_html__('URE: Insufficient permissions to use this add-on','user-role-editor') );
            return;
        }
        
        $ure_object_type = filter_input(INPUT_POST, 'ure_object_type', FILTER_SANITIZE_STRING);
        if ($ure_object_type!=='role' && $ure_object_type!=='user') {
            $editor->set_notification( esc_html__('URE: administrator menu access: Wrong object type. Data was not updated.', 'user-role-editor') );
            return;
        }
        
        $ure_object_name = filter_input(INPUT_POST, 'ure_object_name', FILTER_SANITIZE_STRING);
        if (empty($ure_object_name)) {
            $editor->set_notification( esc_html__('URE: administrator menu access: Empty object name. Data was not updated', 'user-role-editor') );
            return;
        }
                        
        if ($ure_object_type=='role') {
            URE_Admin_Menu::save_menu_access_data_for_role($ure_object_name);
        } else {
            URE_Admin_Menu::save_menu_access_data_for_user($ure_object_name);
        }
                
        $editor->set_notification( esc_html__('Administrator menu access data was updated successfully', 'user-role-editor') );
    }
    // end of update_data()
    
    
    public static function get_allowed_roles($user) {
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
    
    
    public static function get_allowed_caps($allowed_roles, $user) {
        global $wp_roles;
        
        $allowed_caps = array();        
        foreach($allowed_roles as $allowed_role) {
            $allowed_caps = array_merge($allowed_caps, $wp_roles->roles[$allowed_role]['capabilities']);
            if (!empty($user)) {
                $allowed_caps = array_merge($allowed_caps, $user->allcaps);
            }
        }
        
        return $allowed_caps;
    }
    // end of get_allowed_caps()
        
                    
    public static function calc_menu_item_id($menu_kind, $link) {
                
        $item_id = md5($menu_kind . $link);
        
        return $item_id;
    }
    // end calc_menu_item_id()

    
    private static function has_cap($cap_required, $allowed_roles, $allowed_caps) {
        // according to class-wp-user.php (#743) - Everyone is allowed to exist.
        if (empty($cap_required) || $cap_required==='exist' ||
            in_array($cap_required, $allowed_roles) || array_key_exists($cap_required, $allowed_caps)) {
            return true;
        }
        if ( in_array($cap_required, array('switch_themes', 'customize')) && 
            (in_array('edit_theme_options', $allowed_roles) || array_key_exists('edit_theme_options', $allowed_caps)) ) {
            return true;    // permissions extension for "Appearance" menu, "Themes", "Customize" items of "Appearance" menu
        }
        
        return false;
        
    }
    // end of has_cap()
    
    
    public static function has_permission(&$cap_required, $allowed_roles, $allowed_caps) {        
        
        // to show full menu for 'administrator' in any case        
        if (in_array('administrator', $allowed_roles)) {
            return true;
        }
        
        if (self::has_cap($cap_required, $allowed_roles, $allowed_caps)) {
            return true;
        }        
        
        // in case we meet meta-capability, try to map it to the real user capability
        $current_user_id = get_current_user_id();
        $args = array($cap_required, $current_user_id);
        $caps = call_user_func_array('map_meta_cap', $args);
        if (in_array('do_not_allow', $caps)) {
            return false;
        }
        
        $caps_values = array_values($caps);
        $real_cap = array_shift($caps_values);  // extract real capability from array
        if (!self::has_cap($real_cap, $allowed_roles, $allowed_caps)) {
            return false;
        }                
        
        $cap_required = $real_cap;  // replace meta capability with existing real user capability for user reference
        
        return true;
    }
    // end of has_permission()        
    
    
    public static function has_permission_on_submenu($submenu, $allowed_roles, $allowed_caps) {
        
        $allowed = false;
        foreach($submenu as $submenu_item) {
            if (URE_Admin_Menu::has_permission($submenu_item[1], $allowed_roles, $allowed_caps)) {
                $allowed = true;   // user has access to this submenu item
                break;
            }
        }
        
        return $allowed;
    }
    // end of has_permission_on_submenu()
    
    
    /**
     * Returns 1st required capability which exists at the list of allowed capabilities
     * @param array $allowed_caps
     * @param array $required_caps
     * @return string
     */
    public static function min_cap($allowed_caps, $required_caps) {
        
        foreach($required_caps as $rqc) {
            if (array_key_exists($rqc, $allowed_caps)) {
                return $rqc;
            }
        }
        
        return 'do-not-allow';
    }
    // end of min_cap()

    /**
     * Remove $param_name parameter from URL
     * 
     * @param string $url       - full URL
     * @param string $command   - command to start URL with
     * @param string $param_name    - parameter name to remove from URL
     * @return string   - resulting URL without removed parameter
     */
    private static function remove_param_from_url($url, $command, $param_name) {
        
        $key_pos = strpos($url, '?');
        if ($key_pos===false) {
            return $url;
        }
                
        $param_str = substr($url, $key_pos + 1);
        if (empty($param_str)) {
            return $url;
        }
        
        if ( strpos( $param_str, '&#038;' )!==false || strpos( $param_str, '&amp;' )!==false ) {
            // decode URL query parameters separator back to the single ampersand character '&'
            $param_str = str_replace(array('&#038;', '&amp;'), '&', $param_str);
        }
        $new_params = array();
        $params = explode('&', $param_str);
        foreach($params as $param) {
            if (strpos($param, $param_name .'=')===false) {
                $new_params[] = $param;
            }
        }
        $link = $command;
        if (count($new_params)>0) {
            $new_param_str = implode('&', $new_params);
            $link .= '?'. $new_param_str;
        }

        return $link;
            
    }
    // end of remove_param_from_url()
    
    
    /**
     * Remove dynamic part from the menu command, e.g. the 'return' parameter included into the links with customize.php
     * @param string $link
     * @return string
     */
    public static function normalize_link($link) {
        
        $command = 'customize.php';
        if (strpos($link, $command)!==false) {
            $link = self::remove_param_from_url($link, $command, 'return');
        }
        
        return $link;
    }
    // end of normalize_link()
    
    
    public static function is_separator($type) {
        
        $separators = array(
            'wp-menu-separator',    // WordPress
            'separator-woocommerce',    // WooCommerce
            'wps_break_menu'    // WP Statistics
        );
        
        $result = false;
        foreach($separators as $separator) {
            if ($type===$separator || strpos($type, $separator)!==false) {
                $result = true;
                break;
            }
        }
        
        return $result;
    }
    // end of is_separator()
    
}
// end of URE_Admin_Menu class