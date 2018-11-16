<?php
/*
 * Class: Content view shortcode - roles related processing
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulia
 * email: support@role-editor.com
 * 
 */

class URE_Content_View_Shortcode_Roles {
    
    /**
     * Extract roles from shortcode  $key attribute $value
     * 
     * @param string $key
     * @param string $value
     * @return string
     */
    private static function extract($key, $value) {
        
        if (!empty($key) && substr($key, -1)!=='s') {
            $key .= 's';    // use 'roles' instead of possible 'role'
        }
        $logic = ''; $roles = null;
        if (!empty($value)) {
            if (strpos($value, ',')!==false) {
                $roles = explode(',', $value);
                $logic = 'or';
            } elseif (strpos($value, '&&')!==false) {
                $roles = explode('&&', $value);
                $logic = 'and';
            } else {
                $roles = array($value);
                $logic = 'or';
            }
        }
        
        if (!empty($roles)) {
            $roles = array_map('trim', $roles);
        }
        
        $control = array(
            // or: 'role1, role2': check if user has role1 or role2; 
            // and: role1 && role2': check if user has both role1 and role2.simultaneously
            'logic'=>$logic,
            // roles or except_roles
            'check'=>$key,
            'roles'=>$roles
        );
        
        return $control;
    }
    // end of extract()
    
        
    private static function get_list($atts) {
               
        $control = null;
        $attrs = shortcode_atts(
                array(
                    'roles'=>'',
                    'role'=>'',
                    'except_roles'=>'',
                    'except_role'=>''
                ), 
                $atts);                
        foreach($attrs as $key=>$value) {
            $control = self::extract($key, $value);
            if (!empty($control['roles'])) {
                break;
            }
        }                                                
        
        return $control;
    }
    // end of get_list()      
    
    
    /**
     * Check if current user has at least one of roles inside $roles array
     * @param array $roles
     * @return boolean
     */
    private static function is_show_for_selected_or($roles) {        

        if (empty($roles)) {
            return false;
        }
        
        $current_user_id = get_current_user_id();
        $show_content = false;
        foreach($roles as $role) {
            $role = trim($role);
            if ($role=='none') { 
                if ($current_user_id===0) { // not logged-in visitor
                    $show_content = true;
                    break;
                }
            } elseif (current_user_can($role)) {
                $show_content = true;
                break;
            }
        }
        
        return $show_content;
    }
    // end of is_show_for_selected_or()    

    
    /**
     * Check if current user has all roles inside $roles array simultaneously
     * @param array $roles
     * @return boolean
     */
    private static function is_show_for_selected_and($roles) {
        
        if (empty($roles)) {
            return false;
        }

        $current_user_id = get_current_user_id();        
        $show_content = true;
        foreach($roles as $role) {
            $role = trim($role);
            if ($role=='none') { 
                if ($current_user_id===0) {  // not logged-in visitor
                    break;
                }
            } elseif (!current_user_can($role)) {
                $show_content = false;
                break;
            }
        }
        
        return $show_content;
    }
    // end of is_show_for_selected_and()        
    
    
    private static function is_show_except_selected_or($roles) {        
        
        if (empty($roles)) {
            return false;
        }
        
        $current_user_id = get_current_user_id();
        $show_content = true;
        foreach($roles as $role) {
            $role = trim($role);
            if ($role=='none') { 
                if ($current_user_id===0) {  // not logged-in visitor
                    $show_content = false;
                    break;
                }
            } elseif (current_user_can($role)) {
                $show_content = false;
                break;
            }
        }
        
        return $show_content;
    }
    // end of is_show_except_selected_or()
    
    
    /**
     * Check if current user does not have all roles inside $roles array simultaneously
     * @param array $roles
     * @return boolean
     */
    private static function is_show_except_selected_and($roles) {        
        
        if (empty($roles)) {
            return false;
        }
        
        $current_user_id = get_current_user_id();
        $show_content = true; 
        foreach($roles as $role) {
            $role = trim($role);
            if ($role=='none' && $current_user_id===0) {    // not logged-in visitor
                $show_content = false;
                break;
            }
        }
        if (!$show_content) {
            return false;
        }
        
        $can_it = 0;
        foreach($roles as $role) {
            if (current_user_can($role)) {
                $can_it++;
            }
        }
        if ($can_it==count($roles)) {   // user can all roles inside $roles array
            $show_content = false;
        }
        
        return $show_content;
    }
    // end of is_show_except_selected_and()
            
    
    public static function is_show($atts) {        
        
        $control = self::get_list($atts);
        if (empty($control['roles']) || empty($control['check'])) {
            return false;
        }
        
        $show = true;
        if ($control['check']=='roles') {
            if ($control['logic']=='or') {
                $show = self::is_show_for_selected_or($control['roles']);
            } else {
                $show = self::is_show_for_selected_and($control['roles']);
            }
        } elseif($control['check']=='except_roles') {
            if ($control['logic']=='or') {
                $show = self::is_show_except_selected_or($control['roles']);
            } else {
                $show = self::is_show_except_selected_and($control['roles']);
            }
        }

        return $show;
    }
    // end of is_show()

}
// end of URE_Content_View_Shortcode_Roles