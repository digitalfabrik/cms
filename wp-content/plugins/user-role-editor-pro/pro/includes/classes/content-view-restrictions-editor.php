<?php
/**
 * 
 * User Role Editor Pro
 * Content View Restrictions add-on
 * Show/update data for Post meta box / Category / Term section
 * 
 */
class URE_Content_View_Restrictions_Editor {
    

    /**
     * Builds HTML to show roles list with checkboxes in the modal dialog window
     * 
     * @param string $content_for_roles
     * @return string
     */
    public static function get_roles_list_html( $content_for_roles ) {
        
        $wp_roles = wp_roles();
        
        $selected_roles = explode(', ', $content_for_roles);
        $roles_list = '<input type="checkbox" id="ure_roles_auto_select" name="ure_roles_selector" value="1"><hr/>';
        $roles = array_keys($wp_roles->roles);
        asort($roles);
        foreach($roles as $role_id) {
            if (in_array($role_id, $selected_roles)) {
                $role_selected = 'checked';
            } else {
                $role_selected = '';
            }
            $roles_list .= '<input type="checkbox" id="'. $role_id .'" name="'. $role_id .'" class="ure_role_cb" value="1" '. $role_selected .'>&nbsp'.
                           '<label for="'. $role_id .'">' .$wp_roles->roles[$role_id]['name'] .' ('. $role_id .')</label><br>'."\n";
        }
        $roles_list .= '<input type="checkbox" id="no_role" name="no_role" class="ure_role_cb" value="1" '. (in_array('no_role', $selected_roles) ? 'checked' : '') . '>&nbsp'.
                           '<label for="no_role">No role for this site</label><br>'."\n";        
        
        return $roles_list;
    }
    // end of get_roles_list_html()
    
    
    
    public static function get_object_meta( $object_type, $object_id, $meta_key ) {
    
        if ( $object_type=='post' ) {
            $result = get_post_meta( $object_id, $meta_key, true );
        } elseif ( $object_type=='term') {
            $result = get_term_meta( $object_id, $meta_key, true );
        } else {
            $result = '';
        }
        
        return $result;
    }
    // end of get_object_meta()
    
    
    public static function build_form( $object_type, $object_id ) {
        
        $lib = URE_Lib_Pro::get_instance();        
        
        $prohibit_allow_flag = self::get_object_meta( $object_type, $object_id, URE_Content_View_Restrictions::PROHIBIT_ALLOW_FLAG );
        if ( empty( $prohibit_allow_flag ) ) {
            $prohibit_allow_flag = $lib->get_option( 'content_view_allow_flag', 2 );
        }
        
        $content_view_whom = self::get_object_meta( $object_type, $object_id, URE_Content_View_Restrictions::CONTENT_VIEW_WHOM );
        if ( empty( $content_view_whom ) ) {
            if ( $lib->is_new_post() ) {
                $content_view_whom = $lib->get_option( 'content_view_whom', 3 );
            } else {
                $content_view_whom = 3; // selected roles
            }
        }
        
        $content_for_roles = self::get_object_meta( $object_type, $object_id, URE_Content_View_Restrictions::CONTENT_FOR_ROLES );
        $roles_list = self::get_roles_list_html( $content_for_roles );
        
        $content_view_access_error_action = self::get_object_meta( $object_type, $object_id, URE_Content_View_Restrictions::POST_ACCESS_ERROR_ACTION );
        if ( empty( $content_view_access_error_action ) ) {
            $content_view_access_error_action = $lib->get_option( 'content_view_access_error_action', 2 );
            // It's possible to modify default value for the post view access error action: 1 - 404 HTTP error or 2 - show error message
            $content_view_access_error_action = apply_filters( 'ure_default_post_access_error_action', $content_view_access_error_action );
        }
        
        $post_access_error_message = self::get_object_meta( $object_type, $object_id, URE_Content_View_Restrictions::POST_ACCESS_ERROR_MESSAGE );
        
        $view_access_error_url =  self::get_object_meta( $object_type, $object_id, URE_Content_View_Restrictions::ACCESS_ERROR_URL );
        // Add an nonce field so we can check for it later.
        wp_nonce_field( 'ure_content_view_restrictions_box', 'ure_content_view_restrictions_box_nonce' );
        
        require_once( URE_PLUGIN_DIR . 'pro/includes/content-view-restrictions-template.php' );
        
    }
    // end of build_form()    
    
    
    /**
     * Output needed HTML for post meta box
     * 
     */
    public static function render_post_meta_box( $post ) {
        
        self::build_form( 'post', $post->ID );        
        
    }
    // end of render_post_meta_box()
    

    /**
     * Output needed HTML for post meta box
     * 
     */
    public static function render_term_box( $term ) {
        
?>
    <div id="ure_content_view_restrictions_box">
        <hr/>        
        <h2><?php esc_html_e( 'Content View Restricitions', 'user-role-editor' ) ; ?></h2>
<?php                
        self::build_form( 'term', $term->term_id );
?>        
        <hr/>
    </div>  
<?php     

    }
    // end of render_post_meta_box()

    
    private static function check_security( $object_type, $object_id ) {

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return false;
        }

        // Verify that the nonce is valid.
        $nonce = filter_input( INPUT_POST, 'ure_content_view_restrictions_box_nonce', FILTER_SANITIZE_STRING );
        if ( empty( $nonce ) || !wp_verify_nonce( $nonce, 'ure_content_view_restrictions_box' ) ) {
            return false;
        }

        if ( !current_user_can( URE_Content_View_Restrictions::VIEW_POSTS_ACCESS_CAP ) )  {
            return false;
        }
    
        if ( $object_type=='post' ) {
            $post = get_post( $object_id );
            if ( empty( $post ) ) {
                return false;
            }

            if ( !URE_Content_View_Restrictions_Posts_List::can_edit( $post ) ) {
                return false;
            }
        } else {
            $term = get_term( $object_id );
            if ( empty( $term ) ) {
                return false;
            }
            $tax_obj = get_taxonomy( $term->taxonomy );
            if ( !current_user_can( $tax_obj->cap->manage_terms ) || !current_user_can( $tax_obj->cap->edit_terms ) ) {
                return false;
            }
        }
        
        return true;        
    }
    // end of check_security()
    
    
    private static function update_object_meta( $object_type, $object_id, $meta_key, $meta_value ) {
        
        if ( $object_type=='post' ) {
            update_post_meta( $object_id, $meta_key, $meta_value  );
        } elseif ( $object_type=='term' ) {
            update_term_meta( $object_id, $meta_key, $meta_value  );
        }
        
    }
    // end of save_object_meta()
    
    
    private static function delete_object_meta(  $object_type, $object_id, $meta_key ) {
        
        if ( $object_type=='post' ) {
            delete_post_meta( $object_id, $meta_key );
        } elseif ( $object_type=='term' ) {
            delete_term_meta( $object_id, $meta_key );
        }
        
    }
    // end of delete_object_meta()
    
    
    private static function esc_roles( $roles_in ) {
    
        $roles_to_check = explode( ',', $roles_in );
        $roles_to_save = array();
        $wp_roles = wp_roles();
        foreach( $roles_to_check as $role ) {
            $role = trim( $role );
            if ( $role=='no_role' || isset( $wp_roles->roles[$role] ) ) {
                $roles_to_save[] = $role;
            }
        }
        $roles_out = implode(', ', $roles_to_save);
        
        return $roles_out;
    }
    // end of esc_content_for_roles()
    
    
    /** 
     * Save meta data with post/term data save event together
     * @param string $object_type - 'post' or 'term'
     * @param int $object_id - post or term ID
     */
    private static function save_meta_data( $object_type, $object_id) {

        if ( !self::check_security( $object_type, $object_id ) ) {
            return $object_id;
        }

        $lib = URE_Lib_Pro::get_instance();
        $ure_prohibit_allow_flag = (int) $lib->get_request_var( 'ure_prohibit_allow_flag', 'post', 'int' );
        if ( $ure_prohibit_allow_flag!=1 && $ure_prohibit_allow_flag!=2 ) {
            $ure_prohibit_allow_flag = $lib->get_option('content_view_allow_flag', 2);    // take default value
        }
        self::update_object_meta( $object_type, $object_id, URE_Content_View_Restrictions::PROHIBIT_ALLOW_FLAG, $ure_prohibit_allow_flag );        

        $content_view_whom = (int) $lib->get_request_var( 'ure_content_view_whom', 'post', 'int' );
        if ($content_view_whom<1 || $content_view_whom>3) {
           $content_view_whom = $lib->get_option('content_view_whom', 3); // take default value 
        }
        self::update_object_meta( $object_type, $object_id, URE_Content_View_Restrictions::CONTENT_VIEW_WHOM, $content_view_whom );
        
        if ($content_view_whom==3) {    // for selected roles
            $ure_content_for_roles0 = $lib->get_request_var( 'ure_content_for_roles', 'post' );
            $ure_content_for_roles1 = self::esc_roles( $ure_content_for_roles0 );            
            self::update_object_meta( $object_type, $object_id, URE_Content_View_Restrictions::CONTENT_FOR_ROLES, $ure_content_for_roles1 );
        }
        
        $ure_post_access_error_action = (int) $lib->get_request_var( 'ure_post_access_error_action', 'post', 'int' );
        if ( $ure_post_access_error_action<1 || $ure_post_access_error_action>4 ) {
            $ure_post_access_error_action = $lib->get_option( 'content_view_access_error_action', 2 );  // take default value
        }
        self::update_object_meta( $object_type, $object_id, URE_Content_View_Restrictions::POST_ACCESS_ERROR_ACTION, $ure_post_access_error_action );
        
        if ( $ure_post_access_error_action==3 ) { // custom access error message
            $ure_post_access_error_message = $lib->get_request_var( 'ure_post_access_error_message', 'post' );
            self::update_object_meta( $object_type, $object_id, URE_Content_View_Restrictions::POST_ACCESS_ERROR_MESSAGE, $ure_post_access_error_message );
        } elseif ( $ure_post_access_error_action==4 ) { // Redirect to URL
            $view_access_error_url = $lib->get_request_var( 'ure_view_access_error_url', 'post' );
            if ( !empty( $view_access_error_url ) ) {
                self::update_object_meta( $object_type, $object_id, URE_Content_View_Restrictions::ACCESS_ERROR_URL, $view_access_error_url );
            } else {
                self::delete_object_meta( $object_type, $object_id, URE_Content_View_Restrictions::ACCESS_ERROR_URL );
            }
        }
        
    }
    // end of save_meta_data()

    
    // Save meta data with post/page data save event together
    public static function save_post_meta_data( $post_id ) {
        
        self::save_meta_data( 'post', $post_id );
    
    }
    // end of save_post_meta_data()
    
    
    // Save meta data with post/page data save event together
    public static function save_term_meta_data( $term_id ) {
        
        self::save_meta_data( 'term', $term_id );
    
    }
    // end of save_post_meta_data()    
    
        
    
}
// end of URE_Content_View_Restrictions_Editor class
