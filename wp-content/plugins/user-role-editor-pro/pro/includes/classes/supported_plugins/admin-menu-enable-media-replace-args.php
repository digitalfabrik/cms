<?php
class URE_Admin_Menu_Enable_Media_Replace_Args {
       
    public static function get_for_upload($args) {
        
        $page = 'enable-media-replace/enable-media-replace.php';
        if (isset($args[$page])) {
            return $args;
        }
        
        $args[$page] = array(
            'page',
            'action',
            'attachment_id',
            '_wpnonce'
        );        
        
        return $args;
    }
    // end of get_for_admin()
    
}
// end of URE_Admin_Menu_Enable_Media_Replace_Args