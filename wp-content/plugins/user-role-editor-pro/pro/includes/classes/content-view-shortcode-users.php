<?php
/*
 * Class: Content view shortcode - users related processing
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulia
 * email: support@role-editor.com
 * 
 */

class URE_Content_View_Shortcode_Users {

    const NOT_FOUND = 'not_found_users_attribute';
    
    
    /**
     * Convert user logins (if there are any) to the user IDs
     * @param array $users
     * @return array
     */
    private static function convert($users) {
     
        if (empty($users)) {
            return $users;
        }
        
        $users = array_map('trim', $users);
        foreach($users as $key=>$value)  {
            if (is_numeric($value)) {
                continue;
            }
            $user = get_user_by('login', $value);
            if (!empty($user)) {
                $users[$key] = $user->ID;
            }
        }
     
      return $users;
    }
    // end of convert()
    
    
    private static function extract($key, $value) {
        
        if (!empty($key) && substr($key, -1)!=='s') {
            $key .= 's';    // use 'users' instead of possible 'user'
        }
        $users = null;
        if (!empty($value) || $value=='0') {
            if (strpos($value, ' or ')!==false) {
                $operand = ' or ';
            } else {
                $operand = ',';
            }
            $users = explode($operand, $value);
        }
        
        if (!empty($users)) {            
            // replace user logins (if find any) with user IDs
            $users = self::convert($users);            
        }
                
        $control = array(                        
            'check'=>$key, // users or except_users
            'users'=>$users // '15, 17': check if current user ID is 15 or 17
        );
        
        return $control;
    }
    // end of extract()    
    
    
    private static function get_list($atts) {
        
        $control = null;
        $attrs = shortcode_atts(
                array(
                    'users'=>'',
                    'user'=>'',
                    'except_users'=>'',
                    'except_user'=>''
                ), 
                $atts);                
        foreach($attrs as $key=>$value) {
            $control = self::extract($key, $value);
            if (!empty($control['users'])) {
                break;
            }
        }
        
        return $control;
    }
    // end of get_list()
    
    
    private static function is_show_for_selected($users) {
        
        $current_user_id = get_current_user_id();
        $show = in_array($current_user_id, $users);
        
        return $show;
    }
    // end of is_show_for_selected()
    
    
    private static function is_show_except_selected($users) {
                
        $current_user_id = get_current_user_id();
        $show = !in_array($current_user_id, $users);
        
        return $show;
    }
    // end of is_show_except_selected()
    

    public static function is_show($atts) {
    
        $control = self::get_list($atts);
        if (empty($control['users']) || empty($control['check'])) {
            return self::NOT_FOUND;
        }
        
        $show = true;
        if ($control['check']=='users') {
            $show = self::is_show_for_selected($control['users']);
        } elseif ($control['check']=='except_users') {
            $show = self::is_show_except_selected($control['users']);
        }
        
        return $show;
    }
    // end of is_show()
    
}
// end of URE_Content_View_Shortcode_Users 