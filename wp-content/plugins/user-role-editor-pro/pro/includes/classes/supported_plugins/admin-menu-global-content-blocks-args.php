<?php
class URE_Admin_Menu_Global_Content_Blocks_Args {

    public static function get_for_admin($args) {
        
        if (isset($args['global-content-blocks'])) {
            return $args;
        }
        $args['global-content-blocks'] = array(
            'view',
            'edid');
        
        return $args;
    }
    // end of get_for_admin()
}
// end of URE_Admin_Menu_Global_Content_Blocks_Args