<?php
class URE_Admin_Menu_Unitegallery_Args {

    public static function get_for_admin($args) {
        
        if (isset($args['unitegallery'])) {
            return $args;
        }
        $args['unitegallery'] = array(
                'view',
                'type');
        
        return $args;
    }
    // end of get_for_admin()
}
// end of URE_Admin_Menu_Unitegallery_Args