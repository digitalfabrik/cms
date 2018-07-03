<?php
class URE_Admin_Menu_Ninja_Forms_Args {
    
    public static function get_for_edit($args) {
        if (!isset($_GET['post_type']) || $_GET['post_type']!=='nf_sub') {
            return $args;
        }
        
        $args[''][] = 'post_status';
        $args[''][] = 'm';
        $args[''][] = 'form_id';
        $args[''][] = 'begin_date';
        $args[''][] = 'end_date';
        $args[''][] = 'paged';
        
        return $args;
    }
    // end of get_for_edit()
    
    
    public static function get_for_admin($args) {
        
        if (isset($args['ninja-forms'])) {
            return $args;
        }
        $args['ninja-forms'] = array(
            'page',
            'tab',
            'form_id',
            );
        
        return $args;
    }
    // end of get_for_admin()
}
// end of URE_Admin_Menu_Global_Content_Blocks_Args