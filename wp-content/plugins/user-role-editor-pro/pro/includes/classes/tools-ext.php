<?php

class URE_Tools_Ext {
                    
    
    public static function exec() {
        
        
    }
    // end of exec()
    
    
        
    public static function show( $tab_idx ) {
                     

        
    }
    // end of show()

    
/*    For use in future
    //
    //  Go through all users and if user has non-existing role lower him to Subscriber role
    //  ToDo: Think about exposing this to a user as one of useful tools
    // 
    protected function validate_user_roles() {
        global $wp_roles;

        $default_role = get_option('default_role');
        if (empty($default_role)) {
            $default_role = 'subscriber';
        }
        $users_query = new WP_User_Query(array('fields' => 'ID'));
        $users = $users_query->get_results();
        foreach ($users as $user_id) {
            $user = get_user_by('id', $user_id);
            if (is_array($user->roles) && count($user->roles) > 0) {
                foreach ($user->roles as $role) {
                    $user_role = $role;
                    break;
                }
            } else {
                $user_role = is_array($user->roles) ? '' : $user->roles;
            }
            if (!empty($user_role) && !isset($wp_roles->roles[$user_role])) { // role doesn't exists
                $user->set_role($default_role); // set the lowest level role for this user
                $user_role = '';
            }

            if (empty($user_role)) {
                // Cleanup users level capabilities from non-existed roles
                $cap_removed = true;
                while (count($user->caps) > 0 && $cap_removed) {
                    foreach ($user->caps as $capability => $value) {
                        if (!isset($this->full_capabilities[$capability])) {
                            $user->remove_cap($capability);
                            $cap_removed = true;
                            break;
                        }
                        $cap_removed = false;
                    }
                }  // while ()
            }
        }  // foreach()
    }
    // end of validate_user_roles()    
*/    
    
}
// end of URE_Tools_Ext
