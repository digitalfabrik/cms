<?php
/*
 * Class: Add/Process Content view shortcode
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulia
 * email: support@role-editor.com
 * 
 */

class URE_Content_View_Shortcode {
        
    public function __construct() {
            
        add_action('init', array($this, 'add'));
        
    }
    // end of __construct()
    
    
    public function add() {
                
        add_shortcode('user_role_editor', array($this, 'process'));        
        
    }
    // end of add()
                    
                                
    public function process($atts, $content=null) {
        
        // Render shortcode for user with 'administrator' role by default.
        // But allow plugin users to change this default behavior, as there are no restrictions for administrator
        $render_for_admin = apply_filters('ure_render_content_view_shortcode_for_admin', true);
        if (!$render_for_admin && current_user_can('administrator')) {    // leave content unchanged
            $content = do_shortcode($content);
            return $content; 
        }
        
        $show = URE_Content_View_Shortcode_Users::is_show($atts);
        if ($show===URE_Content_View_Shortcode_Users::NOT_FOUND) {
            $show = URE_Content_View_Shortcode_Roles::is_show($atts);
        }
        
        if (!$show) {
            $content = '';
        } else {
            $content = do_shortcode($content);
        }
        
        return $content;
    }
    // end of process_content_view_shortcode()
    
}
// end of URE_Content_View_Shortcode class