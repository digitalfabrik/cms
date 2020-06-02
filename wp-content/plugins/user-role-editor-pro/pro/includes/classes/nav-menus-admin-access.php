<?php

/*
 * User Role Editor WordPress plugin
 * Class URE_Nav_Menus_Admin_Access - prohibit selected Navigation Menus administration for role
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Nav_Menus_Admin_Access {

    private $lib = null;    // reference to the code library object
    private $blocked = null;

    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        new URE_Nav_Menus_Admin_View();
        
        add_filter( 'wp_get_nav_menus', array($this, 'block_nav_menus'), 100 );
        add_action( 'admin_init', array($this, 'block_unneeded_func') );

    }
    // end of __construct()

    
    protected function get_blocked() {
                
        if ( $this->blocked!==null ) {
            return;
        }
        
        $current_user = wp_get_current_user();
        $this->blocked = URE_Nav_Menus_Admin_Controller::load_data_for_user( $current_user );
        
        
    }
    // end of get_blocked()
            
    
    protected function is_restriction_applicable() {
        
        $multisite = $this->lib->get( 'multisite' );
        if ( $multisite && $this->lib->is_super_admin() ) {
            return false;
        }
        
        $current_user = wp_get_current_user();
        if ( !$multisite && $this->lib->user_can_role( $current_user, 'administrator' ) ) {
            return false;
        }
        
        $this->get_blocked();
        if ( empty( $this->blocked ) ) { // There are no any restrictions for current user
            return false;            
        }
        
        return true;
    }
    // end of is_restriction_aplicable()
                            
    
    public function block_nav_menus( $nav_menus ) {
                            
        if ( !$this->is_restriction_applicable() ) {
            return $nav_menus;
        }
                                
        foreach( $this->blocked as $blocked_slug ) {
            foreach( $nav_menus as $key=>$menu ) {
                if ( $menu->slug==$blocked_slug ) {
                    unset( $nav_menus[$key] );
                }
            }
        }        

        return $nav_menus;
    }
    // end of unregister_blocked_widgets()                                            

    
    public function block_unneeded_func() {
        
        global $pagenow;
        
        if ( $pagenow!=='nav-menus.php' ) {
            return;
        }
        
        if ( !$this->is_restriction_applicable() ) {
            return;
        }
        
        wp_register_script( 'ure-nav-menus-block-new', plugins_url( '/pro/js/nav-menus-block-new.js', URE_PLUGIN_FULL_PATH ) );
        wp_enqueue_script ( 'ure-nav-menus-block-new' );
        
    }
}
// end of URE_Nav_Menus_Admin_Access class
