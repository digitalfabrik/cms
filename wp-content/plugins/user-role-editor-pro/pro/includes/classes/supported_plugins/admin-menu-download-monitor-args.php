<?php
class URE_Admin_Menu_Download_Monitor_Args {

    public static function get_for_edit($args) {
        if (!isset($_GET['post_type']) || $_GET['post_type']!=='dlm_download') {
            return $args;
        }
        $args[''][] = 'dlm_download_category';
        
        return $args;
    }
    // end of get_for_edit()
  
}
// end of URE_Admin_Menu_Download_Monitor_Args