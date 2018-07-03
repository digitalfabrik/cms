<?php
/*
 * Class to validate imported data
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v3
 * 
*/

class URE_Import_Validator {


    public static function validate_role($role_id, $role) {
        $lib = URE_Lib_Pro::get_instance();
        $result = $lib->init_result();
        
        $sanitized_role_id = sanitize_key($role_id);
        if ($role_id!==$sanitized_role_id) {
            $result->message = esc_html__('Import failure: role ID contains invalid characters.', 'user-role-editor');            
        } elseif (!is_array($role)) {
            $result->message = esc_html__('Import failure: role should have an array structure.', 'user-role-editor');            
        } elseif (count($role)!==2) { // only two items 'name' and 'capabilities' should be in role array
            $result->message = esc_html__('Import failure: role array is not valid (should have 2 items).', 'user-role-editor');
        } elseif (!isset($role['name'])) {   // wrong role array structure
            $result->message = esc_html__('Import failure: Wrong role array structure: "name" key not found.', 'user-role-editor');
        } elseif (!isset($role['capabilities'])) {
            $result->message = esc_html__('Import failure: Wrong role array structure: "capabilities" key not found.', 'user-role-editor');
        } elseif ($role['name']!=($name = sanitize_text_field($role['name']))) {  // wrong characters in the role name
            $result->message = esc_html__('Import failure: Wrong characters in the role name - sanitized version:', 'user-role-editor').' '.esc_html($name);
        } elseif (!is_array($role['capabilities'])) {
            $result->message = esc_html__('Import failure: role capabilities should have an array structure.', 'user-role-editor');
        } else {
            $result->success = true;
        }
                        
        return $result;        
    }
    // end of validate_role()
    
    
    public static function sanitize_capability($key) {
    
        $filter0 = '/[^a-zA-Z0-9_\-\s\/]/';   //  should be a valid PHP regular expression
        $filter = apply_filters('ure_sanitize_capability_filter', $filter0);
        $key1 = preg_replace($filter, '', $key);
        
        return $key1;
    }
    // end of sanitize_capability()
    

    public static function validate_capabilities($role_id, $capabilities) {
        $lib = URE_Lib_Pro::get_instance();
        $result = $lib->init_result();
        $result->success = true;
        if (empty($capabilities)) {
            return $result;
        }
        foreach($capabilities as $key=>$value) {            
            $sanitized_key = self::sanitize_capability($key);            
            if ($key!==$sanitized_key) {    // illegial character found at the capability identifire
                $result->success = false;
                $result->message = esc_html__('Import failure: Wrong characters in the capability ID - sanitized version:', 'user-role-editor').' '.$sanitized_key;
                break;
            } elseif ((!is_bool($value) && !is_integer($value)) || (is_integer($value) && (int) $value!==1 && (int) $value!==0)) {                
                if (strpos($key, 'vc_access_rules_')!==false) { // do not validate WPBakery Visual Composer capabilities value, as it intentionally uses strings instead of booleans.
                    continue;
                }
                $result->success = false;
                $message = __('Import failure: Role "%s", wrong capability value for "%s" - only TRUE, FALSE, 1 and 0 are allowed, but "%s" found', 'user-role-editor');
                $result->message = esc_html(sprintf($message, $role_id, $key, $value));
                break;
            }            
        }   // foreach($capabilities as ...        
        
        return $result;
    }
    // end of validate_capabilities()    

    
    public static function full_access_for_admin($roles, $role_id) {
        if ($role_id==='administrator') {
            return $roles;
        }
        if (!isset($roles[$role_id])) {
            return $roles;
        }
        // add missed capabilities to the administrator role
        foreach($roles[$role_id]['capabilities'] as $cap=>$value) {
            if (!isset($roles['administrator']['capabilities'][$cap])) {
                $roles['administrator']['capabilities'][$cap] = true;
            }
        }
        
        return $roles;
    }
    // full_access_for_admin()
    
    /**
     * 
     * @return array
     */
    public static function what_addons_to_import($addons_to_import = null) {
        $lib = URE_Lib_Pro::get_instance();
        $addons_manager = URE_Addons_Manager::get_instance();
        $active_addons = $addons_manager->get_active();
        $addons = array();        
        foreach($active_addons as $addon) {        
            if (!isset($addon->access_data_key) || !$addon->exportable) {       
                continue;
            }
            if (empty($addons_to_import)) {
                $import = $lib->get_request_var($addon->access_data_key, 'post', 'checkbox');
            } else { 
                $import = in_array($addon->access_data_key, $addons_to_import);
            }
            if ($import==1) {
                $addons[] = $addon->access_data_key;
            }            
        }
                
        return $addons;
    }
    // end of what_addons_to_import()

}
// end of URE_Import_Validator
