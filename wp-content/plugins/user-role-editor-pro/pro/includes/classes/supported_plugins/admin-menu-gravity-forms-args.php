<?php
class URE_Admin_Menu_Gravity_Forms_Args {

    public static function get_for_admin($args) {
        
        if (isset($args['gf_edit_forms'])) {
            return $args;
        }
        
        $args['gf_edit_forms'] = array(
            'id',
            'orderby',
            'order',
            'subview',
            'view'
        );
        $args['gf_entries'] = array(
            'field_id',
            'filter',
            'id',
            'lid',   
            'operator',
            'orderby',
            'paged',
            'pos',
            'view'            
        );
        $args['gf_settings'] = array(
            'subview'
        );
        $args['gf_export'] = array(
            'view'
        );
        
        
        return $args;
    }
    // end of get_for_admin()
}
// end of URE_Admin_Menu_Gravity_Forms_Args