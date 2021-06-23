<?php

/*
 * User Role Editor WordPress plugin
 * Class URE_Widgets_Admin_Access - prohibit selected widgets administration for role
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Widgets_Admin_Access {

    private $lib = null;    // reference to the code library object
    private $unregistered_widgets = null;
    private $blocked = null;

    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        new URE_Widgets_Admin_View();                
        
        add_action('widgets_admin_page', array($this, 'unregister_blocked_sidebars'), 100);
        add_action('widgets_init', array($this, 'unregister_blocked_widgets'), 100);
        add_action('wp_ajax_widgets-order', array($this, 'ajax_widgets_order'), 0);

    }
    // end of __construct()

    
    protected function get_blocked() {
                
        if ( $this->blocked!==null ) {
            return;
        }
        
        $user = wp_get_current_user();
        $this->blocked = URE_Widgets_Admin_Controller::load_data_for_user( $user );        
        
    }
    // end of get_blocked()
            
    
    protected function is_restriction_applicable() {
        
        $multisite = $this->lib->get('multisite');
        if ($multisite && $this->lib->is_super_admin()) {
            return false;
        }
        
        $current_user = wp_get_current_user();
        if (!$multisite && $this->lib->user_can_role($current_user, 'administrator')) {
            return false;
        }
        
        $this->get_blocked();
        if (empty($this->blocked['widgets']) && empty($this->blocked['sidebars'])) { // There are no any restrictions for current user
            return false;            
        }
        
        return true;
    }
    // end of is_restriction_aplicable()
                            
    
    public function unregister_blocked_widgets() {
                            
        if (!$this->is_restriction_applicable()) {
            return;
        }
                        
        $widgets = URE_Widgets_Admin_Controller::get_all_widgets();
        $this->unregistered_widgets = array();
        foreach($this->blocked['widgets'] as $widget) {
            if ( !isset( $widgets[$widget] ) ) {
                continue;
            }
            $this->unregistered_widgets[$widget] = $widgets[$widget]->id_base;
            unregister_widget($widget);            
        }        
        
    }
    // end of unregister_blocked_widgets()

    
    public function unregister_blocked_sidebars() {
                            
        if (!$this->is_restriction_applicable()) {
            return;
        }
        foreach($this->blocked['sidebars'] as $sidebar) {
            unregister_sidebar($sidebar);            
        }        
        
    }
    // end of unregister_blocked_sidebars()
    
    
    /* 
     * Widget list decoding code was written on the base of wp_ajax_widgets_order() from wp-admin/ajax-actions.php
     * 
     */
    private function decode_widgets_list($widgets_list) {
        $widgets = array();
        $widgets_raw = explode(',', $widgets_list);
        foreach ($widgets_raw as $key => $widget_id_str) {
            if (strpos($widget_id_str, 'widget-') === false) {
                continue;
            }
            $widgets[$key] = substr($widget_id_str, strpos($widget_id_str, '_') + 1);
        }
        
        return $widgets;
    }
    // end of decode_widget_id_str()
    
    
    /**
     * Convert string from POST to the sidebars with widgets array     
     * @return array
     */
    private function get_sidebars_from_post() {
        if (!is_array($_POST['sidebars'])) {
            return array();
        }
        $sidebars = array();
        foreach ($_POST['sidebars'] as $key=>$widgets_list) {            
            if (empty($widgets_list)) {
                continue;
            }                                    
            $sidebars[$key] = $this->decode_widgets_list($widgets_list);
        }                
        
        return $sidebars;
    }
    // end of get_sidebars_from_post()
    
    
    private function get_id_base_from_str($widget_id_str) {
       
        $id_base = substr($widget_id_str, 0, strrpos($widget_id_str, '-'));
       
       return $id_base;
    }
    // get_id_base_from_str()
    
    
    private function is_sidebar_blocked($sidebar) {
        
        $result = in_array($sidebar, $this->blocked['sidebars']);
        
        return $result;
    }
    // end if is_sidebar_blocked()
    
    
    private function is_widget_blocked($id_base) {
        
        $result = false;
        foreach($this->blocked['widgets'] as $widget) {
            if ($this->unregistered_widgets[$widget]===$id_base) {
                $result = true;
                break;
            }
        }
        
        return $result;
    }
    // end of is_widget_blocked()
    
    
    private function get_blocked_widgets_to_save($widgets_list) {
        
        $widgets_to_save = array();
        foreach ($widgets_list as $id_str) {
            $id_base = $this->get_id_base_from_str($id_str);
            if ($this->is_widget_blocked($id_base)) {
                $ind = count($widgets_to_save);
                $widgets_to_save[$ind] = 'widget-'. $ind .'_'. $id_str;
            }
        }
        
        return $widgets_to_save;
    }
    // end of get_blocked_widgets_to_save()
    
    
    private function convert_widgets_id($widgets_list) {
        $widgets_to_save = array();
        foreach ($widgets_list as $id_str) {
            $ind = count($widgets_to_save);
            $widgets_to_save[$ind] = 'widget-'. $ind .'_'. $id_str;
        }
        
        return $widgets_to_save;
    }
    // end of convert_widgets_id()
    
    
    // Get blocked items sidebars and widgets in format compatible for saving with POST request
    private function get_active_items_blocked() {
        
        $sidebars_to_save = array();            
        $sidebars = wp_get_sidebars_widgets();
        foreach ($sidebars as $key => $widgets_list) {
            if ($key=='wp_inactive_widgets') {
                $sidebars_to_save[$key] = $this->convert_widgets_id($widgets_list);
                continue;
            }
                  
            if ($this->is_sidebar_blocked($key)) {
                $sidebars_to_save[$key] = $this->convert_widgets_id($widgets_list);
                continue;
            }
            
            $sidebars_to_save[$key] = $this->get_blocked_widgets_to_save($widgets_list);
        }
        
        return $sidebars_to_save;
    }
    // end of get_active_items_blocked()
    
    
    /**
     * Process situation, when user with restricted role updates sidebar, which has active widgets, to which role does not have access.
     * We should add those blocked active widgets back to the $POST['sidebars'] in order do not lose them after update
     * We should also add blocked sidebars, which absent at $_POST['sidebars'] array
     */
    public function ajax_widgets_order() {
        
        if (!$this->is_restriction_applicable()) {
            return;
        }                
        
        $sidebars_to_save = $this->get_active_items_blocked();                       
        $sidebars_from_post = $this->get_sidebars_from_post();
        foreach($sidebars_from_post as $key=>$widgets_list) {                
            foreach($widgets_list as $id_str) {
                //$id_base = $this->get_id_base_from_str($id_str);
                if (!in_array($id_str, $sidebars_to_save[$key])) {
                    $ind = count($sidebars_to_save[$key]);
                    $sidebars_to_save[$key][$ind] = 'widget-'. $ind .'_'. $id_str;
                }
            }
            $_POST['sidebars'][$key] = implode(',', $sidebars_to_save[$key]);
        }
        
        // add blocked sidebars
        foreach($sidebars_to_save as $key=>$sidebar) {
            if (!isset($_POST['sidebars'][$key])) {
                $_POST['sidebars'][$key] = implode(',', $sidebar);
            }            
        }

        // call an original routine from WordPress core
        wp_ajax_widgets_order();
    }
    // end of ajax_widgets_order()
                        
}
// end of URE_Widgets_Admin_Access class
