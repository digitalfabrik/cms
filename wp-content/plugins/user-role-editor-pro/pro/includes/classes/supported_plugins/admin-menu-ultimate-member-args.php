<?php
class URE_Admin_Menu_Ultimate_Member_Args {

    public static function get_for_users($args) {
        
        if (isset($args['um_filter_role'])) {
            return $args;
        }
        
        $args0 = array(
            'um_filter_role',
            'um_role',
            'um_bulk_action',
            'um_change_role',
            'um_filter_processed'
        );
        $args1 = apply_filters('ure_admin_menu_ultimate_member_args', $args0);                
        $args2 = array_merge($args[''], $args1);
        $args[''] = $args2;
                
        return $args;
    }
    // end of get_for_users()
}
// end of URE_Admin_Menu_Ultimate_Member_Args