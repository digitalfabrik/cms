<?php
/*
 * Stuff specific for User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * 
*/

class URE_Lib_Pro extends URE_Lib {
    
    public static function get_instance($options_id = '') {
        
        if (self::$instance === null) {
            if (empty($options_id)) {
                throw new Exception('URE_Lib_Pro::get_inctance() - Error: plugin options ID string is required');
            }
            // new static() will work too
            self::$instance = new URE_Lib_Pro($options_id);
        }

        return self::$instance;
    }
    // end of get_instance()
    
    
    /**
     * Is this the Pro version?
     * @return boolean
     */ 
    public function is_pro() {
        return true;
    }
    // end of is_it_pro()
    
    
    public function get_bbpress() {
        
        if ($this->bbpress===null) {
            $this->bbpress = new URE_bbPress_Pro();
        }
        
        return $this->bbpress;
        
    }
    // end of get_bbpress()
                            
    
    /**
     * Return WP_User object
     * 
     * @param mix $user
     * @return WP_User
     */
    public function get_user($user) {
        if ($user instanceof WP_User) {
            return $user;
        }    

        if (is_int($user)) {    // user ID
            $user = get_user_by('id', $user);
        } else {        // user login
            $user = get_user_by('login', $user);
        }                
        
        return $user;
    }
    // end of get_user()
                                   
    
    /**
     * Returns 1st capability from the list of capabilities $caps which is granted to user $user
     * Returns empty string if not one capability from the list is granted to user
     * @param WP_User $user
     * @param array $caps
     * @return string
     */
    public function user_can_which($user, $caps) {
    
        foreach($caps as $cap){
            if ($this->user_has_capability($user, $cap)) {
                return $cap;
            }
        }

        return '';        
    }
    // end of user_can_which()
 
    
    /**
     * Returns true if user has role $role
     * Return false in other case
     * @param WP_User $user
     * @param string $role
     * @return boolean
     */
    public function user_can_role( $user, $role ) {
        
        if ( empty( $user ) || !is_a( $user, 'WP_User' ) || empty( $user->roles ) ) {
            return false;
        }
        
        foreach( $user->roles as $user_role ) {
            if ( $user_role===$role ) {
                return true;
            }
        }
        
        return false;
    }
    // end of user_can_role()                            

    
    /**
     * Helper for building JSON response on AJAX queries
     * Initiates stdClass variable with 'success' and 'message' properties
     * @return \stdClass
     */
    public function init_result() {
        
        $result = new stdClass();
        $result->success = false;
        $result->message = '';
        
        return $result;
    }
    // end of init_result()
                                         
          
    /**
     * Returns the list of edit capabilities for all custom post type, including WordPress built-in post and page
     * 
     * @return array
     */
    public function get_edit_custom_post_type_caps() {
        
        $caps = get_transient('ure_edit_custom_post_type_caps');
        if (empty($caps)) {
            // Such CPT as a WooCommerce shop_order has public set to false, but show_ui to true
            $post_types = get_post_types(array(/*'public'=>true,*/ 'show_ui'=>true), 'objects');
            $caps = array();
            foreach($post_types as $post_type) {
                if (!in_array($post_type->cap->edit_post, $caps)) {
                    $caps[] = $post_type->cap->edit_post;
                }
                if (!in_array($post_type->cap->edit_posts, $caps)) {
                    $caps[] = $post_type->cap->edit_posts;
                }
            }
            set_transient('ure_edit_custom_post_type_caps', $caps, 15);
        }
        
        return $caps;
    }
    // end of get_edit_custom_post_type_caps()        
        
    
    /**
     * Returns a list of post IDs for provided terms ID list
     * @param array or string (of comma separated integers) $terms_list
     * @return array (of integers) posts ID
     */
    public function get_posts_by_terms($terms_list) {
        global $wpdb;        
        
        if (!is_array($terms_list)) {
            $terms_list = URE_Utils::filter_int_array_from_str($terms_list);            
        }
        if (empty($terms_list)) {
            return array();
        }

        $terms_list_csv = URE_Base_Lib::esc_sql_in_list('int', $terms_list);
        
        // SQL command was built on the base of command from wp-includes/taxonomy.php function get_objects_in_term(), line #653 (WP version 4.7.3)
        $query = "SELECT a.object_id
                    FROM {$wpdb->term_relationships} a 
                        INNER JOIN {$wpdb->term_taxonomy} b ON a.term_taxonomy_id=b.term_taxonomy_id
                    WHERE b.term_id IN ($terms_list_csv)";
        $post_ids = $wpdb->get_col($query);
        if (!is_array($post_ids)) {
            $post_ids = array();
        } else {
            $post_ids = array_unique($post_ids);
        }
        
        return $post_ids;
    }
    // end of get_posts_by_terms()
            
    
    public function is_new_post() {
        global $pagenow;
        
        if ($pagenow=='post-new.php') {
            $result = true;
        } else {
            $result = false;
        }
        
        return $result;
    }
    // end of is_new_post()
    
    
    /**
     * Determines if a post, identified by the specified ID, exist
     * within the WordPress database.
     * 
     * Note that this function uses the 'acme_' prefix to serve as an
     * example for how to use the function within a theme. If this were
     * to be within a class, then the prefix would not be necessary.
     *
     * @param    int    $id    The ID of the post to check
     * @return   bool          True if the post exists; otherwise, false.
     * @since    1.0.0
     */
    public function post_exists($post_id) {
                
        $post_id = (int) $post_id;
        if ( empty($post_id) ) {
            return false;
        }
        
        $post = WP_Post::get_instance($post_id);
        if (is_object($post) && $post->ID>0) {
            $result = true;
        } else {
            $result = false;
        }
        
        return $result;
    }
    // end of post_exists()
    
    
    public function get_all_editable_roles() {
        
        $roles = get_editable_roles();  // WordPress roles
        $bbpress = $this->get_bbpress();        
        $bbp_roles = $bbpress->get_bbp_editable_roles();    // bbPress roles
        $roles = array_merge($roles, $bbp_roles);        
        
        return $roles;
    }
    // end of get_all_editable_roles()
    
    
    /**
     * Check if current visitor is a logged in user
     * Wraps WordPress built-in is_user_logged_in() and - extends it with custom filter
     * @return bool
     */
    public function is_user_logged_in() {
        
        $result = is_user_logged_in();
        $result = apply_filters('ure_is_user_logged_in', $result);
    
        return $result;
    }
    // end of is_user_logged_in()


    /**
     * Returns post-new.php from URL like https://domain.com/wp-admin/post-new.php
     * 
     * @param string $url
     * @return string
     */
    public function extract_command_from_url( $url, $with_query=true ) {
        
        $path = parse_url( $url, PHP_URL_PATH );
        $path_parts = explode( '/', $path );
        $url_script = end( $path_parts );
        $url_query = parse_url( $url, PHP_URL_QUERY );
        
        $command = $url_script;
        if ( !empty( $url_query ) && $with_query ) {
            $command .= '?'. $url_query;
        }
        if ( !empty( $command ) ) {
            $command = str_replace( '&', '&amp;', $command );
        } elseif( $this->is_right_admin_path() ) {
            $command = 'index.php';
        }
        
        return $command;
        
    }
    // end of extract_command_from_url()

    

    public function about() {
?>       
        <h2>User Role Editor Pro</h2>
        <table>
            <tr>
                <td>
                    <strong>Version:</strong>
                </td> 
                <td>
                    <?php echo URE_VERSION ;?>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Plugin URL:</strong>
                </td> 
                <td>
                    <a href="https://www.role-editor.com">www.role-editor.com</a>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Dowload URL:</strong>
                </td> 
                <td>
                    <a href="https://www.role-editor.com/download-plugin">www.role-editor.com/download-plugin</a>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Author:</strong>
                </td> 
                <td>
                    <a href="https://www.role-editor.com/about">Vladimir Garagulya</a>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <a href="mailto:support@role-editor.com" target="_top">Send support question</a>
                </td>
            </tr>
        </table>        
<?php        
    }
    // end of about()

}
// end of URE_Lib_Pro()
