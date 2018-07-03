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
    
    
    public function reset_active_addons() {
        $this->active_addons = array();
    }
    // end of init_active_addons()
    
    
    public function add_active_addon($addon_id) {
        $this->active_addons[$addon_id] = 1;
    }
    // end of add_active_addon()
    
    
    public function get_active_addons() {
        
        return $this->active_addons;
    }
    // end of get_active_addon()
    
    
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
     * if returns true - make full synchronization of roles for all sites with roles from the main site
     * else - only currently selected role update is replicated
     * 
     * @return boolean
     */
    public function is_full_network_synch() {
        
        if (is_network_admin()) {
            $result = true;
        } else {
            $result = parent::is_full_network_synch();
        }
        
        return $result;
    }
    // end of is_full_network_synch()
       
    
    public function user_can_which($user, $caps) {
    
        foreach($caps as $cap){
            if ($this->user_has_capability($user, $cap)) {
                return $cap;
            }
        }

        return '';        
    }
    // end of user_can_which()
 
    
    public function user_can_role($user, $role) {
        
        if (empty($user) || !is_a($user, 'WP_USER') || empty($user->roles)) {
            return false;
        }
        
        foreach($user->roles as $user_role) {
            if ($user_role===$role) {
                return true;
            }
        }
        
        return false;
    }
    // end of user_can_role()
    
    
    /**
     * if existing user was not added to the current blog - add him
     * @global type $blog_id
     * @param type $user
     * @return bool
     */
    protected function check_blog_user($user) {
        global $blog_id;
        
        $result = true;
        if (is_network_admin()) {
            if (!array_key_exists($blog_id, get_blogs_of_user($user->ID)) ) {
                $result = add_existing_user_to_blog( array( 'user_id' => $user->ID, 'role' => 'subscriber' ) );
            }
        }

        return $result;
    }
    // end of check_blog_user()
    
    
    /** Get user roles and capabilities from the main blog
     * 
     * @param int $user_id
     * @return boolean
     */
    protected function get_user_caps_from_main_blog($user_id) {
        global $wpdb;
        
        $meta_key = $wpdb->prefix.'capabilities';
        $query = $wpdb->prepare(
                    "SELECT meta_value
                        FROM {$wpdb->usermeta}
                        WHERE user_id=%d and meta_key=%s
                        LIMIT 0, 1",
                    array($user_id, $meta_key)
                            );
        $user_caps = $wpdb->get_var($query);
        if (empty($user_caps)) {
            return false;
        }
        return $user_caps;      
     
    }
    // end of get_user_caps_from_main_blog()
    
    
    protected function update_user_caps_for_blog($blog_id, $user_id, $user_caps) {
        global $wpdb;
        
        $meta_key = $wpdb->prefix.$blog_id.'_capabilities';
        $query = $wpdb->prepare(
                    "UPDATE {$wpdb->usermeta}
                        SET meta_value=%s
                        WHERE user_id=%d and meta_key=%s
                        LIMIT 1",
                    array($user_caps, $user_id, $meta_key)
                            );
        $result = $wpdb->query($query);
        
        return $result;
    }
    // end of update_user_caps_for_blog()
    
    
    protected function network_update_user($user) {        
                        
        $user_caps = $this->get_user_caps_from_main_blog($user->ID);
        $user_blogs = get_blogs_of_user($user->ID); // list of blogs, where user was registered           
        $blog_ids = $this->blog_ids;    // full list of blogs
        unset($blog_ids[0]);  // do not touch the main blog, it was updated already
        foreach($blog_ids as $blog_id) {
            if (!array_key_exists($blog_id, $user_blogs)) {
                $result = add_user_to_blog($blog_id, $user->ID, 'subscriber');
                if ($result!==true) {
                   return false;
                }
                do_action('added_existing_user', $user->ID, $result);                
            }
            $result = $this->update_user_caps_for_blog($blog_id, $user->ID, $user_caps);
            if ($result===false) {
                return false;
            }
        }
        
        return true;
    }
    // end of network_update_user()

    
    public function init_result() {
        
        $result = new stdClass();
        $result->success = false;
        $result->message = '';
        
        return $result;
    }
    // end of init_result()
                    
         
    /**
     * Initializes roles and capabiliteis list if it is not done yet
     * 
     */
    protected function init_caps() {
        if (empty($this->full_capabilities)) {
            $this->roles = $this->get_user_roles();
            $this->init_full_capabilities();
        }        
    }
    // end of init_caps()
    
    
    public function build_html_caps_blocked_for_single_admin() {
        $this->init_caps();
        $allowed_caps = $this->get_option('caps_allowed_for_single_admin', array());
        $html = '';
        // Core capabilities list
        foreach ($this->full_capabilities as $capability) {
            if (!$capability['wp_core']) { // show WP built-in capabilities 1st
                continue;
            }
            if (!in_array($capability['inner'], $allowed_caps)) {
                $html .= '<option value="' . $capability['inner'] . '">' . $capability['inner'] . '</option>' . "\n";
            }
        }
        // Custom capabilities
        $quant = count($this->full_capabilities) - count($this->get_built_in_wp_caps());
        if ($quant > 0) {            
            // Custom capabilities list
            foreach ($this->full_capabilities as $capability) {
                if ($capability['wp_core']) { // skip WP built-in capabilities 1st
                    continue;
                }
                if (!in_array($capability['inner'], $allowed_caps)) {
                    $html .= '<option value="' . $capability['inner'] . '" style="color: blue;">' . $capability['inner'] . '</option>' . "\n";
                }
            }
        }

        return $html;
    }
    // end of build_html_caps_blocked_for_single_admin()


    public function build_html_caps_allowed_for_single_admin() {
        $allowed_caps = $this->get_option('caps_allowed_for_single_admin', array());
        if (count($allowed_caps)==0) {
            return '';
        }
        $this->init_caps();
        $build_in_wp_caps = $this->get_built_in_wp_caps();
        $html = '';
        // Core capabilities list
        foreach ($allowed_caps as $cap) {
            if (!isset($build_in_wp_caps[$cap])) { // show WP built-in capabilities 1st
                continue;
            }
            $html .= '<option value="' . $cap . '">' . $cap . '</option>' . "\n";
        }
        // Custom capabilities
        $quant = count($this->full_capabilities) - count($this->get_built_in_wp_caps());
        if ($quant > 0) {
            // Custom capabilities list
            foreach ($allowed_caps as $cap) {
                if (isset($build_in_wp_caps[$cap])) { // skip WP built-in capabilities 1st
                    continue;
                }
                $html .= '<option value="' . $cap . '" style="color: blue;">' . $cap . '</option>' . "\n";
            }
        }

        return $html;
    }
    // end of build_html_caps_allowed_for_single_admin()
    

    /**
     * Exclude unexisting capabilities
     * @param string $user_caps_array - name of POST variable with array of capabilities from user input
     */
    public function filter_existing_caps_input($user_caps_array) {
        
        if (isset($_POST[$user_caps_array]) && is_array($_POST[$user_caps_array])) {
            $user_caps = $_POST[$user_caps_array];
        } else {
            $user_caps = array();
        }
        if (count($user_caps)) {
            $this->init_caps();            
            foreach ($user_caps as $cap) {
                if (!isset($this->full_capabilities[$cap])) {
                    unset($user_caps[$cap]);
                }
            }
        }

        return $user_caps;
    }
    // end of filter_existing_caps_input()
            
    
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
     * Update roles for all network using direct database access - quicker in several times
     * 
     * @global wpdb $wpdb
     * @return boolean
     */
    public function direct_network_roles_update() {
        $result = parent::direct_network_roles_update();
        if (!$result) {
            return false;
        }
        
        // replicate addons access data from the main site to the whole network
        $replicator = new URE_Network_Addons_Data_Replicator();
        $result = $replicator->replicate_for_all_network();
        
        return $result;
    }
    // end of direct_network_roles_update()
    
    
/*    
    // create assign_role object
    public function get_assign_role() {
        $assign_role = new URE_Assign_Role_Pro();
        
        return $assign_role;
    }
    // end of get_assign_role()
*/
    
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
        
    
    /**
     * Check if current user can edit a post
     * 
     * @param int/WP_Post $post
     * @return boolean
     */
    public function can_edit($post) {        
                
        if (!is_a( $post, 'WP_Post' )) {
            $post = get_post($post);
            if (empty($post)) {
                return false;
            }
        }
        
        $current_user_id = get_current_user_id();
        $checked_posts = get_transient('ure_checked_posts');
        if (!is_array($checked_posts)) { 
            $checked_posts = array();
        }
        if (!isset($checked_posts[$post->ID]) || !isset($checked_posts[$post->ID][$current_user_id])) {
            $post_type_obj = get_post_type_object($post->post_type);
            if (empty($post_type_obj)) {
                return false;
            }
            $can_it = current_user_can($post_type_obj->cap->edit_post, $post->ID);            
            $checked_posts[$post->ID][$current_user_id] = $can_it;
            set_transient('ure_checked_posts', $checked_posts, 30);
        }
        
        return $checked_posts[$post->ID][$current_user_id];
    }
    // end of can_edit()

    
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
        if (!empty($this->bbpress)) {
            $bbp_roles = $this->bbpress->get_bbp_editable_roles();    // bbPress roles
            $roles = array_merge($roles, $bbp_roles);
        }
        
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