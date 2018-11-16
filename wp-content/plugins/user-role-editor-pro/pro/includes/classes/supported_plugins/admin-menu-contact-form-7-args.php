<?php
class URE_Admin_Menu_Contact_Form_7_Args {

    public static function get_for_admin($args) {
        
        if (isset($args['wpcf7'])) {
            return $args;
        }
        $args['wpcf7'] = array(            
            'action',
            'action2',
            'active-tab',
            'message',
            'paged',
            'orderby',
            'order',
            'post',
            's',
            '_wp_http_referer',
            '_wpnonce');
        
        return $args;
    }
    // end of get_for_admin()
}
// end of URE_Admin_Contact_Form_7_Args