<?php
class URE_Admin_Menu_Eventon_Args {
    
    public static function get_for_edit($args) {
        if (!isset($_GET['post_type']) || $_GET['post_type']!=='ajde_events') {
            return $args;
        }
        
        $args[''][] = 'event_date_type';
        
        return $args;
    }
    // end of get_for_edit()
    

}
// end of URE_Admin_Menu_Eventon_Args