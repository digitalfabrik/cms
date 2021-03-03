<?php

/*
 * User Role Editor WordPress plugin
 * Class URE_Nav_Menus_Admin_Controller - data update/load for Navigaton menus admin access add-on
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Nav_Menus_Admin_Controller {

    const ACCESS_DATA_KEY = 'ure_nav_menus_access_data';
            
        
    /**
     * Load Nav. Menus access data for role
     * @param string $role_id
     * @return array
     */
    public static function load_data_for_role( $role_id ) {
        
        $access_data = get_option( self::ACCESS_DATA_KEY );
        if ( is_array( $access_data ) && array_key_exists( $role_id, $access_data ) ) {
            $result = $access_data[$role_id];            
        }
        if ( !is_array( $result ) ) {
            $result = array();
        }
                
        return $result;
    }
    // end of load_data_for_role()
    
    
    public static function load_data_for_user( $user ) {
    
        if ( is_object( $user ) ) {
            $id = $user->ID;
        } else if ( is_int( $user ) ) {
            $id = $user;
            $user = get_user_by( 'id', $user );
        } else {
            $user = get_user_by( 'login', $user );
            $id = $user->ID;
        }
        
        $blocked = get_user_meta( $user->ID, self::ACCESS_DATA_KEY, true );
        if ( !is_array( $blocked ) ) {
            $blocked = array();
        }
        
        $access_data = get_option( self::ACCESS_DATA_KEY );
        if ( empty( $access_data ) ) {
            $access_data = array();
        }
        
        foreach ( $user->roles as $role ) {
            if ( isset( $access_data[$role] ) && is_array( $access_data[$role] ) ) {
                $blocked = array_merge( $blocked, $access_data[$role] );
            }
        }        
        
        $blocked = apply_filters( 'ure_nav_menus_edit_access_user', $blocked, $user );
        
        return $blocked;
    }
    // end of load_data_for_user()

    
    private static function get_access_data_from_post() {
        
        $keys_to_skip = array('action', 'ure_nonce', '_wp_http_referer', 'ure_object_type', 'ure_object_name', 'user_role');
        $access_data = array();
        foreach ( $_POST['values'] as $key=>$value ) {
            if ( in_array($key, $keys_to_skip) ) {
                continue;
            }
            $access_data[] = $key;           
        }
        
        return $access_data;
    }
    // end of get_access_data_from_post()
        
    
    private static function save_access_data_for_role( $role_id ) {
        
        $access_for_role = self::get_access_data_from_post();
        $access_data = get_option( self::ACCESS_DATA_KEY );
        if ( !is_array( $access_data ) ) {
            $access_data = array();
        }
        if ( count( $access_for_role )>0 ) {
            $access_data[$role_id] = $access_for_role;
        } else {
            unset( $access_data[$role_id] );
        }
        update_option( self::ACCESS_DATA_KEY, $access_data );
        
    }
    // end of save_access_data_for_role()
    
    
    private static function save_access_data_for_user( $user_login ) {
        
//      $access_for_user = $this->get_access_data_from_post();
        // TODO ...
        
    }
    // end of save_access_data_for_role()   
                    
    
    public static function get_allowed_roles($user) {
        $allowed_roles = array();
        if ( empty( $user ) ) {   // request for Role Editor - work with currently selected role
            $current_role = filter_input( INPUT_POST, 'current_role', FILTER_SANITIZE_STRING );
            $allowed_roles[] = $current_role;
        } else {    // request from user capabilities editor - work with that user roles
            $allowed_roles = $user->roles;
        }
        
        return $allowed_roles;
    }
    // end of get_allowed_roles()
                    
    /**
     * Code was built on the base wp-insludes/nav-menu.php: wp_get_nav_menus()
     * @return array
     */
    public static function get_all_nav_menus() {
        
        $args = array(
		'hide_empty' => false,
		'orderby'    => 'name',
	);
        $menus = get_terms( 'nav_menu', $args );
                
        return $menus;        
    }
    // end of get_all_widgets()
        
    
    public static function update_data() {
    
        $answer = array('result'=>'error', 'message'=>'');                
        
        if ( !current_user_can('ure_nav_menus_access') ) {
            $answer['message'] = esc_html__('URE: Insufficient permissions to use this add-on','user-role-editor');
            return $answer;
        }
        
        $ure_object_type = ( isset( $_POST['values']['ure_object_type'] ) ) ? filter_var( $_POST['values']['ure_object_type'], FILTER_SANITIZE_STRING ) : false;
        if ( $ure_object_type!=='role' && $ure_object_type!=='user' ) {
            $answer['message'] = esc_html__( 'URE: Nav Menus access: Wrong object type. Data was not updated.', 'user-role-editor' );
            return $answer;
        }
        $ure_object_name = isset( $_POST['values']['ure_object_name'] ) ? filter_var( $_POST['values']['ure_object_name'], FILTER_SANITIZE_STRING ) : false;
        if ( empty( $ure_object_name ) ) {
            $answer['message'] = esc_html__( 'URE: Nav Menus access: Empty object name. Data was not updated', 'user-role-editor' );
            return $answer;
        }
                        
        if ( $ure_object_type=='role' ) {
            self::save_access_data_for_role( $ure_object_name );
        } else {
            self::save_access_data_for_user( $ure_object_name );
        }
        
        $answer['result'] = 'success';
        $answer['message'] = esc_html__( 'Nav. menus access: data was updated successfully', 'user-role-editor' );
        
        return $answer;
    }
    // end of update_data()
        
}
// end of URE_Nav_Menus_Admin_Controller class
