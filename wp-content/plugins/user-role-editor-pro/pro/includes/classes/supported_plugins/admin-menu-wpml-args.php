<?php
class URE_Admin_Menu_Wpml_Args {

    public static function get_for_edit($args) {
        
        $args[''][] = 'lang';
        
        return $args;
    }
    // end of get_for_edit()
    
    
    public static function get_for_post_new($args) {
        
        $args[''][] = 'lang';
        $args[''][] = 'trid';
        $args[''][] = 'source_lang';
        
        return $args;
    }
    // end of get_for_post_new()
    
    
    public static function get_for_admin($args) {
        
        if (isset($args['sitepress-multilingual-cms/menu/languages.php'])) {
            return $args;
        }
        
        $args['sitepress-multilingual-cms/menu/languages.php'] = array('trop');
        $args['wpml-string-translation/menu/string-translation.php'] = array('trop');
        
        return $args;
    }
    // end of get_for_admin()
    
}
// end of URE_Admin_Menu_Wpml_Args