<?php
/**
 * User Role Editor WordPress plugin
 * Class URE_Front_End_Menu_Access - prohibit selected front end menu items for role(s), logged-in or logged-out users 
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
**/
class URE_Front_End_Menu_Access {
    
    const MENU_ITEM_META_KEY = 'ure_front_end_menu_access';
    
    private $lib = null;   
    
    
    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        
        $wp_ver = get_bloginfo( 'version' );
        if ( version_compare( $wp_ver, '5.4', '<' ) ) {
            // switch the admin walker for WordPress versions earlier than 5.4
            add_filter('wp_edit_nav_menu_walker', array($this, 'get_walker'), 10, 1);
        }
        
        add_action('wp_nav_menu_item_custom_fields', 'URE_Front_End_Menu_View::show', 10, 2);        
        add_action('admin_enqueue_scripts' , array($this, 'add_js'));
        add_action('wp_update_nav_menu_item', array('URE_Front_End_Menu_Controller', 'update'), 10, 2 );
        
        if (!is_admin()) {
            add_filter('wp_get_nav_menu_items', array($this, 'hide_menu_items'), 99);
        }
        
    }
    // end of __construct()
    
    
    public function get_walker( $walker ) {
        
        require_once( URE_PLUGIN_DIR .'pro/includes/classes/front-end-menu-edit.php');
        
        $walker = 'URE_Front_End_Menu_Edit';
        
        return $walker;
    }
    // end of get_walker()

    
    public function add_js( $hook ) {
                                
        if ($hook!== 'nav-menus.php') {
            return;
        }
        
        if (!current_user_can('ure_front_end_menu_access')) {
            return;
        }        
        
        $wp_roles = wp_roles();
        $roles = $wp_roles->role_names;
        
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-dialog', '', array('jquery-ui-core', 'jquery-ui-button', 'jquery') );
        wp_register_script('ure-pro-front-end-menu-access', plugins_url('/pro/js/front-end-menu-access.js', URE_PLUGIN_FULL_PATH ), array(), URE_VERSION );
        wp_enqueue_script('ure-pro-front-end-menu-access');
        
        wp_localize_script('ure-pro-front-end-menu-access', 'ure_pro_front_end_menu_access_data', array(
            'dialog_title'=>esc_html__('Select roles', 'user_role_editor'),
            'roles'=>$roles
        ));
        
    }
    // end of add_js()
    
    
    private function  current_user_can_role_from( $roles_str ) {
        
        $user = wp_get_current_user();
        $roles = explode(',', $roles_str );
        $is_available = false;
        foreach( $roles as $role ) {
            $role = trim( $role );
            if ( $this->lib->user_can_role( $user, $role ) ) {
                $is_available = true;
                break;
            }
        }
        
        return $is_available;
    }
    // end of current_user_can_role_from()
    

    private function check_access_to_menu_link( $nav_menu_item ) {        
        
        $is_available = true;
        if ( $nav_menu_item->type!='custom' && $nav_menu_item->object_id>0 ) {
            if ( $this->lib->post_exists( $nav_menu_item->object_id ) ) {
                // check if current user has access to view this post or page content
                $is_available = URE_Content_View_Restrictions::current_user_can_view( $nav_menu_item->object_id );
            }            
        }
                
        return $is_available;
    }
    // end of check_access_to_menu_link()
    
    
    private function can_user_view( $nav_menu_item ) {
        
        $is_available = true; 
        // Look if we should hide front-end menu item, if linked page is not visible to a current user
        $hide_fe_menu = apply_filters( 'ure_hide_fe_menu_if_content_view_prohibited', true );
        if ( $hide_fe_menu ) {
            // Check if "Content view restrictions" add-on is active
            $active = $this->lib->get_option( 'activate_content_for_roles', false );
            if ( $active ) {                
                $is_available = $this->check_access_to_menu_link( $nav_menu_item );
            }
        }
        
        return $is_available;
    }
    // end of can_user_view()
    
    
    private function check_show_menu_item_to( $restrictions ) {
                        
        if ( $restrictions['whom']==1 ) { // Everyone
            $is_available = true;
        } elseif ( $restrictions['whom']==2 && is_user_logged_in() ) {    // Any logged-in user
            $is_available = true;
        } elseif ( $restrictions['whom']==3 && is_user_logged_in() ) {    // Logged-in user with role(s)            
            if ( empty( $restrictions['roles'] ) ) {
                $is_available = true;
            } else {
                $is_available = $this->current_user_can_role_from( $restrictions['roles'] );
            }
        } elseif ( $restrictions['whom']==4 && !is_user_logged_in() ) {   // Not logged-in only
            $is_available = true;
        } elseif ( $restrictions['whom']==5 ) { // Not logged-in or logged-in user with selected role
            if ( !is_user_logged_in() ) {
                $is_available = true;
            } else {  // check current user roles  
                if ( empty($restrictions['roles'] ) ) {
                    $is_available = true;
                } else {
                    $is_available = $this->current_user_can_role_from( $restrictions['roles'] );
                }   
            }
        } else {
            $is_available = false;
        }
        
        return $is_available;
        
    }
    // end of check_show_menu_item_to()


    private function check_hide_menu_item_from( $restrictions ) {
                        
        if ( $restrictions['whom']==1 ) { // Everyone
            $is_available = false;
        } elseif ( $restrictions['whom']==2 ) {   // Any logged-in user
            if ( is_user_logged_in() ) {    
                $is_available = false; 
            } else {
                $is_available = true;
            }
        } elseif ( $restrictions['whom']==3 ) {   // Logged-in user with role(s)
            if ( is_user_logged_in() ) {    
                if ( empty( $restrictions['roles'] ) ) {
                    $is_available = true;
                } else {
                    $is_available = !$this->current_user_can_role_from( $restrictions['roles'] );
                }
            } else {
                $is_available = true;
            }
        } elseif ( $restrictions['whom']==4 ) {    // Not logged-in only
            if ( !is_user_logged_in() ) {   
                $is_available = false;
            } else {
                $is_available = true;
            }
        } elseif ( $restrictions['whom']==5 ) { // Not logged-in or logged-in user with selected role
            if ( !is_user_logged_in() ) {
                $is_available = false;
            } else {  // check current user roles  
                if ( empty($restrictions['roles'] ) ) {
                    $is_available = true;
                } else {
                    $is_available = !$this->current_user_can_role_from( $restrictions['roles'] );
                }   
            }
        } else {
            $is_available = false;
        }
        
        return $is_available;
        
    }
    // end of check_hide_menu_item_from()
    
    
    private function check_menu_item( $nav_menu_item ) {
        
        $restrictions = URE_Front_End_Menu_Controller::get( $nav_menu_item->ID ); 
        // TRUE by default
        $is_available = apply_filters( 'ure_show_front_end_menu_item', true, $nav_menu_item, $restrictions );
        if ( !$is_available ) {
            return false;
        }
         
        if ( empty( $restrictions ) || !is_array( $restrictions ) || empty( $restrictions['whom'] ) ) {
            // There are no restrictions data for "Front end menu access" add-on
            return true;
        }
        
        if ( $restrictions[ 'what_todo']==1 ) { // Show to
            $is_available = $this->check_show_menu_item_to( $restrictions );
        } else if ( $restrictions[ 'what_todo']==2 ) { // Hide from
            $is_available = $this->check_hide_menu_item_from( $restrictions );
        }         
        
        if ( $is_available) {   
            // Check if current user has access to this menu item link
            $is_available = $this->can_user_view( $nav_menu_item );
        }
                
        
        return $is_available;
    }
    // end of check_menu_item();

    
    public function hide_menu_items($nav_menu_items) {

        $nav_menu_items = array_filter( $nav_menu_items, array($this, 'check_menu_item'));
		
        return $nav_menu_items;
    }
    // end of hide_menu_items()
    
}
// end of URE_Front_End_Menu_Access class
