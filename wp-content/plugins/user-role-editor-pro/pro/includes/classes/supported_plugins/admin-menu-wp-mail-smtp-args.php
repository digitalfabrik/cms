<?php
class URE_Admin_Menu_Wp_Mail_Smtp_Args {
        
    public static function get_for_settings( $args ) {
               
        $args['wp-mail-smtp'] = array('tab');
        
        return $args;
    }
    // end of get_for_admin()
    
}
// end of URE_Admin_Menu_Wpml_Args