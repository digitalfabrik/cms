<?php
/**
 * Exclude prohibited posts/pages and other post types from listings
 * 
 */
class URE_Content_View_Restrictions_Posts_List {

    protected static $instance = null; // object exemplar reference
    private $lib = null;
    private $prohibited_posts = null;
    private $restrict_even_if_can_edit = false;
											
				
    // List of post ID which current user can edit
    public static $checked_posts = null;
          
    
    /**
     * Private clone method to prevent cloning of the instance of the *Singleton* instance.
     *
     * @return void
     */
    private function __clone() {
        
    }
    // end of __clone()
    
    
    /**
     * Private unserialize method to prevent unserializing of the *Singleton* instance.
     *
     * @return void
     */
    private function __wakeup() {
        
    }
    // end of __wakeup()
    
    
    public static function get_instance() {
        if (self::$instance===null) {        
            self::$instance = new URE_Content_View_Restrictions_Posts_List();
        }
        
        return self::$instance;
    }
    // end of get_instance()
    
    
    protected function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
	if (empty(self::$checked_posts)) {
            self::$checked_posts = array();
        }

        add_action('pre_get_posts', array($this, 'hide_prohibited_posts'), 999);    // to fire as late as possible
        add_filter('posts_where', array($this, 'hide_prohibited_posts2'), 999, 2);
        add_filter('get_previous_post_where', array($this, 'update_adjacent_post_where'), 999, 5);
        add_filter('get_next_post_where', array($this, 'update_adjacent_post_where'), 999, 5);
        add_filter('get_pages', array($this, 'hide_prohibited_pages'), 999);
        if (class_exists('EM_Events')) {    // Events Manager plugin ( https://wordpress.org/plugins/events-manager )
            add_filter('em_events_output_events', array($this, 'hide_prohibited_events'), 999, 2);
        }        
        if (class_exists('WC_Shortcode_Products')) {    // exclude restricted products, which may become available via WooCommerce cache/transients
            add_filter('wc_get_template_part', array($this, 'hide_prohibited_products'), 999, 3);
        }
    }
    // end of __construct()

				
    public static function clear_can_edit_cache( $post_id = 0 ) {
        
        if ( !empty( $post_id ) ) {
            unset( self::$checked_posts[$post_id] );
        } else {
            self::$checked_posts = array();
        }
    }
    // end of clear_can_edit_cache()
    
    
    public static function can_edit( WP_Post $post ) {
        
        if (isset(self::$checked_posts[$post->ID])) {
            $can_it = self::$checked_posts[$post->ID];
        } else {
            $post_type_obj = get_post_type_object( $post->post_type );
            if ( empty( $post_type_obj ) ) {
                return false;
            }

            $can_it = current_user_can( $post_type_obj->cap->edit_post, $post->ID );
            self::$checked_posts[$post->ID] = $can_it;
        }

        return $can_it;
    }
    // end of can_edit()

				    
    private function do_not_restrict_editors( $post ) {
        if ( !is_a( $post, 'WP_Post' ) ) {
            $post = get_post( $post );
            if ( empty($post) ) {
                return false;
            }
        }                
                
        $can_edit_post = self::can_edit( $post );
        // no restrictions for users who can edit this post/page
        if ( $can_edit_post && !$this->restrict_even_if_can_edit ) {
            return true;
        }
        
        return false;
    }
    // end of do_not_restrict_editors()
    
    
    private function validate_restriction( $restriction ) {
        
        if ( !isset( $restriction[URE_Content_View_Restrictions::PROHIBIT_ALLOW_FLAG] ) ) {
            // Retrieve default value or use 2-"Allow" instead
            $restriction[URE_Content_View_Restrictions::PROHIBIT_ALLOW_FLAG] = $this->lib->get_option( 'content_view_allow_flag', 2 );
        }
        if ( !in_array( $restriction[URE_Content_View_Restrictions::PROHIBIT_ALLOW_FLAG], array(1, 2 ) ) ) {
            $restriction[URE_Content_View_Restrictions::PROHIBIT_ALLOW_FLAG] = 2;
        }
        
        if ( !isset( $restriction[URE_Content_View_Restrictions::CONTENT_VIEW_WHOM] ) ) {
            // Retrieve default value or use 3-"for selected roles only" instead
            $restriction[URE_Content_View_Restrictions::CONTENT_VIEW_WHOM] = $this->lib->get_option( 'content_view_whom', 3 );                    
        }
        if ( !in_array( $restriction[URE_Content_View_Restrictions::CONTENT_VIEW_WHOM], array(1, 2, 3 ) ) ) {
            // Set default value 3
            $restriction[URE_Content_View_Restrictions::CONTENT_VIEW_WHOM] = 3;
        }
        
        if ( !isset( $restriction[URE_Content_View_Restrictions::POST_ACCESS_ERROR_ACTION] ) ) {
            // Retrieve default value or use 2 - "show access error message"
            $restriction[URE_Content_View_Restrictions::POST_ACCESS_ERROR_ACTION] = $this->lib->get_option( 'content_view_access_error_action', 2 );
        }
        if ( !in_array( $restriction[URE_Content_View_Restrictions::POST_ACCESS_ERROR_ACTION], array(1, 2, 3, 4) ) ) {
            // Set default value 2
            $restriction[URE_Content_View_Restrictions::POST_ACCESS_ERROR_ACTION] = 2;
        }
        
        if ( !isset( $restriction[URE_Content_View_Restrictions::CONTENT_FOR_ROLES] ) ) {
            $restriction[URE_Content_View_Restrictions::CONTENT_FOR_ROLES] = '';
        }
        
        return $restriction;
        
    }
    // end of validate_restrictions()    
    
    
    /**
     * Check general restrict criteria
     * @param array $restriction
     * @param int $post_id : post ID or 0 in case function is called for term, not for the post
     * @return boolean
     */
    private function check_restriction( $restriction, $post_id = 0 ) {
        
        $result = new stdClass();
        $result->restricted = false;        
        $result->check_other_criteria = false;
                
        $restriction = $this->validate_restriction( $restriction );        
        $prohibit_allow_flag = $restriction[URE_Content_View_Restrictions::PROHIBIT_ALLOW_FLAG];         
        $content_view_whom = $restriction[URE_Content_View_Restrictions::CONTENT_VIEW_WHOM];        
        $access_error_action = $restriction[URE_Content_View_Restrictions::POST_ACCESS_ERROR_ACTION];
        $content_for_roles = $restriction[URE_Content_View_Restrictions::CONTENT_FOR_ROLES];
        
        if ( $prohibit_allow_flag == 2 ) { // Allow
            if ( $content_view_whom == 1 ) {    // For All
                $result->restricted = false;
                $result->hide = false;
                return $result;
            }

            if ( $content_view_whom == 2 && $this->lib->is_user_logged_in() ) {    // Logged in only (any role) 
                $result->restricted = false;
                $result->hide = false;
                return $result;
            }
        }
        if ( $prohibit_allow_flag == 1 ) { // Prohibit
            if ( $content_view_whom == 1 ) {    // All
                if ( $post_id>0 ) {
                    $this->prohibited_posts[] = $post_id;
                }
                // View prohibited, result cached, no need to check further
                $result->restricted = true;
                $result->hide = true;
                return $result;
            }
            if ( $content_view_whom == 2 && $this->lib->is_user_logged_in() ) {    // Logged in only (any role)
                if ( $post_id>0 ) {
                    $this->prohibited_posts[] = $post_id;
                }
                // View prohibited, result cached, no need to check further
                $result->restricted = true;                
                $result->hide = true;
                return $result;
            }
        }

        if ( $content_view_whom == 3 && empty( $content_for_roles ) ) {     // For selected roles, but roles list is empty
            $result->restricted = false;
            $result->hide = false;
            return $result;
        }
                
        if ( $access_error_action !=1 // 404 HTTP error - fully exclude
             && $access_error_action !=4 // redirect to URL
            ) { // Should show access error message instead of post content, do not hide a post from the listings
            $result->restricted = true;  // may be restricted or may be not really
            $result->hide = false;
            return $result;
        }
        
        // View may be prohibed by other criteria (roles), further investigation is needed        
        $result->restricted = true;
        $result->hide = true;
        $result->check_other_criteria = true;
        
        return $result;
    }
    // end of check_restriction()
    
    
    private function get_post_restriction( $post_id ) {
        
        $restriction = array();
        $restriction[URE_Content_View_Restrictions::PROHIBIT_ALLOW_FLAG] = get_post_meta( $post_id, URE_Content_View_Restrictions::PROHIBIT_ALLOW_FLAG, true );
        $restriction[URE_Content_View_Restrictions::CONTENT_VIEW_WHOM] = get_post_meta( $post_id, URE_Content_View_Restrictions::CONTENT_VIEW_WHOM, true );
        $restriction[URE_Content_View_Restrictions::CONTENT_FOR_ROLES] = get_post_meta( $post_id, URE_Content_View_Restrictions::CONTENT_FOR_ROLES, true );
        $restriction[URE_Content_View_Restrictions::POST_ACCESS_ERROR_ACTION] = get_post_meta( $post_id, URE_Content_View_Restrictions::POST_ACCESS_ERROR_ACTION, true );
        
        return $restriction;
        
    }
    // end of get_post_restriction()
    

    private function do_not_hide_at_post_level( $post ) {

        if (!is_a( $post, 'WP_Post' )) {
            $post = get_post($post);
            if (empty($post)) {
                return false;
            }
        }
        
        $restriction = $this->get_post_restriction( $post->ID );
        $result = $this->check_restriction( $restriction );
        if ( !$result->restricted || !$result->hide ) {
            return true;
        }

        $roles = URE_Content_View_Restrictions::extract_roles_from_string( $restriction[URE_Content_View_Restrictions::CONTENT_FOR_ROLES] );
        if ( count($roles) == 0) {
            return true;
        }

        $ure_prohibit_allow_flag = $restriction[URE_Content_View_Restrictions::PROHIBIT_ALLOW_FLAG];
        if ( $ure_prohibit_allow_flag == 1 ) {   // Prohibited
            $prohibited = $this->check_roles_for_prohibited( $roles );
            $result = !$prohibited;
        } else {
            $allowed = $this->check_roles_for_allowed( $roles );
            $result = $allowed;
        }
        
        return $result;

    }
    // end of do_not_hide_at_post_level()

    
    private function do_not_hide_at_term_level( $post ) {

        if (!is_a( $post, 'WP_Post' )) {
            $post = get_post($post);
            if (empty($post)) {
                return false;
            }
        }
                
        if ( URE_Content_View_Restrictions::blocked_at_terms_level( $post->ID ) ) {
            $value = URE_Content_View_Restrictions::get_access_error_action();
            if ( in_array( $value, array(2, 3) ) ) {
                return true;    // do not hide, show error message instead of a real content
            } else {
                return false;   // hide, exclude from listing
            }
        }
                
        return true;

    }
    // end of do_not_hide_at_term_level()        
    

    private function do_not_hide_at_role_level($post) {
        
        $current_user = wp_get_current_user();
        $blocked = URE_Content_View_Restrictions_Controller::load_access_data_for_user($current_user);
        if (empty($blocked['data'])) {
            return true;
        }

        if ( !is_a( $post, 'WP_Post' ) ) {
            $post = get_post($post);
            if (empty($post)) {
                return false;
            }
        }
                
        $result = in_array( $blocked['access_error_action'], array(2, 3) ) ? true : false;        
        if ( URE_Content_View_Restrictions::is_object_restricted_for_role( $post->ID, $blocked, 'posts') ) {            
            return $result;
        }
        
        if ( URE_Content_View_Restrictions::is_author_restricted_for_role( $post->post_author, $blocked ) ) {
            return $result;
        }

        if ( URE_Content_View_Restrictions::is_term_restricted_for_role($post->ID, $blocked ) ) {
            return $result;
        }
        
        if ( URE_Content_View_Restrictions::is_page_template_restricted_for_role($post->ID, $blocked ) ) {
            return $result;
        }

        return true;
    }

    // end of do_not_hide_at_role_level()


    public function hide_prohibited_pages( $pages ) {
        
        if ( is_admin() ) {   // execute for front-end only
            return $pages;
        }
        
        if ( count( $pages )==0 ) {
            return $pages;
        }
        
        if ( $this->lib->is_super_admin() ) {
            return $pages;
        }
                
	$this->restrict_even_if_can_edit = apply_filters( 'ure_restrict_content_view_for_authors_and_editors', false );        

        // to exclude recursion call by WP_Query, when query posts available for editing
        remove_action('pre_get_posts', array($this, 'hide_prohibited_posts'), 999);

        $pages1 = array();
        foreach($pages as $page) {
            if ( $this->do_not_restrict_editors( $page ) ) {
                $pages1[] = $page;
                continue;
            }
            if ( $this->do_not_hide_at_post_level( $page ) &&
                 $this->do_not_hide_at_term_level( $page ) &&   
                 $this->do_not_hide_at_role_level( $page ) ) {
                $pages1[] = $page;
            }
        }
             
	// restore hide prohibited posts hook
        add_action( 'pre_get_posts', array($this, 'hide_prohibited_posts'), 999 );

        return $pages1;
    }
    // end of hide_prohibited_pages()

    
    /*
     * Filter events from the Events Manager plugin
     * https://wordpress.org/plugins/events-manager/ 
     */
    public function hide_prohibited_events($events) {
        
        if ( count( $events )==0 ) {
            return $events;
        }
        
        $this->restrict_even_if_can_edit = apply_filters( 'ure_restrict_content_view_for_authors_and_editors', false );

        $events1 = array();
        foreach( $events as $event ) {
            $post = get_post( $event->post_id );
            if ( $this->do_not_restrict_editors( $post ) ) {
                 $events1[] = $event;
                 continue;
            }
            if ( $this->do_not_hide_at_post_level( $post ) &&
                 $this->do_not_hide_at_term_level( $post ) &&       
                 $this->do_not_hide_at_role_level( $post ) ) {
                $events1[] = $event;
            }
        }
        
        return $events1;
    }
    // end of hide_prohibited_events()

    
    private function get_object_level_data_from_db( $object_type ) {
        global $wpdb;
        
        if ( $object_type=='post' ) {
            $table_name = $wpdb->postmeta;
            $id_field = 'post_id';
        } elseif ( $object_type=='term' ) {
            $table_name = $wpdb->termmeta;
            $id_field = 'term_id';
        } else {
            return array();
        }
        
        $where = $wpdb->prepare(
                    " WHERE {$table_name}.meta_key=%s OR
                        {$table_name}.meta_key=%s OR
                        {$table_name}.meta_key=%s OR
                        {$table_name}.meta_key=%s",
                    array(
                        URE_Content_View_Restrictions::CONTENT_FOR_ROLES,
                        URE_Content_View_Restrictions::PROHIBIT_ALLOW_FLAG,
                        URE_Content_View_Restrictions::CONTENT_VIEW_WHOM,
                        URE_Content_View_Restrictions::POST_ACCESS_ERROR_ACTION
                        )
                            );
        $query = "SELECT {$id_field}, meta_key, meta_value
                    FROM {$table_name}" . $where .' ORDER BY 1'; 
        $data = $wpdb->get_results($query);
        
        return $data;
    }
    // end of get_object_level_data_from_db()
    
    
    private function load_post_level_data() {
        
        $records = $this->get_object_level_data_from_db( 'post' );
        if (empty($records)) {
            return false;
        }
        $data = array();
        foreach($records as $record) {
            if (!isset($data[$record->post_id])) {
                $data[$record->post_id] = array();
            }
            $data[$record->post_id][$record->meta_key] = $record->meta_value;
        }
                
        return $data;
    }
    // end of load_post_level_data()
    

    /**
     * Loop through the roles for 'prohibited' flag and add post ID to the prohibited list if user has prohibited role
     * @param array $roles
     * @param array $prohibited
     * @param int $post_id : post ID or 0 in case function is called for term, not for the post
     */
private function check_roles_for_prohibited($roles, $post_id=0 ) {
        
        if ( !is_array( $roles ) || count( $roles )==0 ) {
            return false;
        }
        
        $prohibited = false;
        $logged_in = $this->lib->is_user_logged_in();        
        if ($logged_in) {
            $user = wp_get_current_user();
            foreach( $roles as $role ) {
                if ( $this->lib->user_can_role( $user, $role ) ) {
                    if ( $post_id>0 ) {
                        $this->prohibited_posts[] = $post_id;
                    }
                    $prohibited = true;
                    break;
                }
            }
        } else {
            foreach( $roles as $role ) {
                if ( $role=='no_role' ) {
                    if ( $post_id>0 ) {
                        $this->prohibited_posts[] = $post_id;
                    }
                    $prohibited = true;
                    break;
                }
            }    
        }
        
        return $prohibited;
    }
    // end of check_roles_for_prohibited()
    
    
    /**
     * Loop through the roles for 'allowed' flag and add post ID to the phohibited list if user does not have allowed role
     * @param array $roles
     * @param array $prohibited
     * @param int $post_id : post ID or 0 in case function is called for term, not for the post
     * @return boolean : false - if nothing prohibited, true - if object is prohibited for current user
     */    
    private function check_roles_for_allowed($roles, $post_id = 0 ) {        
        
        if ( count( $roles )==0 ) {
            return false;
        }
        
        $logged_in = $this->lib->is_user_logged_in();        
        $allowed = false;
        if ($logged_in) {
            $user = wp_get_current_user();
            foreach($roles as $role) {
                if ($this->lib->user_can_role($user, $role)) {
                    $allowed = true;
                    break;
                }
            }
        } else {
            foreach($roles as $role) {
                if ($role=='no_role') {
                    $allowed = true;
                    break;
                }
            }
        }
        if (!$allowed ) {
            if ( $post_id>0 ) {
                $this->prohibited_posts[] = $post_id;
            }
        }
        
        return $allowed;
    }
    // end of check_roles_for_allowed()
                    
    
    /**
     * Build the list of posts prohibited for current user at the post level
     * 
     */
    private function get_post_level_restrictions() {        
        
        $data = $this->load_post_level_data();
        if (empty($data)) {
            return;
        }        
	    
        foreach( $data as $post_id => $restriction ) {
            if ( $this->do_not_restrict_editors( $post_id ) ) {
               continue;
            }
            $result = $this->check_restriction( $restriction, $post_id );
            if ( !$result->restricted || !$result->hide ) {
                continue;
            }                                                
                        
            if ( !isset( $restriction[URE_Content_View_Restrictions::CONTENT_FOR_ROLES] ) ) {
                $restriction[URE_Content_View_Restrictions::CONTENT_FOR_ROLES] = '';
            }
            if ( !isset( $restriction[URE_Content_View_Restrictions::CONTENT_VIEW_WHOM] ) ) {
                $restriction[URE_Content_View_Restrictions::CONTENT_VIEW_WHOM] = 3;
            }
            $roles = URE_Content_View_Restrictions::extract_roles_from_string( $restriction[URE_Content_View_Restrictions::CONTENT_FOR_ROLES] );
            if ( $restriction[URE_Content_View_Restrictions::PROHIBIT_ALLOW_FLAG]==1 ) {   // Prohibited
                if ( $restriction[URE_Content_View_Restrictions::CONTENT_VIEW_WHOM]==1) {   // To All
                    $this->prohibited_posts[] = $post_id;
                } else {
                    $this->check_roles_for_prohibited( $roles, $post_id );
                }
            } else {
                $this->check_roles_for_allowed( $roles, $post_id );
            }
        }   // foreach(...								
        
    }
    // end of get_post_level_restrictions()
    
            
    private function load_term_level_data() {
        
        $records = $this->get_object_level_data_from_db( 'term' );
        if ( empty( $records ) ) {
            return false;
        }
        $data = array();
        foreach( $records as $record ) {
            if ( !isset( $data[$record->term_id] ) ) {
                $data[ $record->term_id ] = array();
            }
            $data[ $record->term_id][$record->meta_key] = $record->meta_value;
        }
                
        return $data;
    }
    // end of load_term_level_data()
    
    
    private function get_posts_for_term( $term_id ) {
    
        $term = get_term( $term_id );
        if (empty( $term ) ) {
            return array();
        }
        
        $posts = get_posts( array(
            'numberposts' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => $term->taxonomy,
                    'field' => 'id',
                    'terms' => $term->term_id, // Where term_id of Term 1 is "1".
                    'include_children' => false
                )
            )
        ));
        
        return $posts;
    }
    // end of get_posts_for_term()
            
    
    /**
     * Build the list of posts prohibited for current user at the term/category level
     * 
     */
    private function get_term_level_restrictions() {
        
        $data = $this->load_term_level_data();
        if ( empty( $data ) ) {
            return;
        }
                
        $prohibited_posts = array();
        foreach( $data as $term_id=>$restriction ) {
            $result = $this->check_restriction( $restriction );
            if ( !$result->restricted || !$result->hide ) {
                continue;
            }                    
                       
            // get posts for the term_id
            $posts = $this->get_posts_for_term( $term_id );
            if ( empty( $posts ) ) {
                continue;
            }                                    
                        
            if ( !isset( $restriction[URE_Content_View_Restrictions::CONTENT_FOR_ROLES] ) ) {
                $restriction[URE_Content_View_Restrictions::CONTENT_FOR_ROLES] = '';
            }
            $roles = URE_Content_View_Restrictions::extract_roles_from_string( $restriction[URE_Content_View_Restrictions::CONTENT_FOR_ROLES] );
            if ($restriction[URE_Content_View_Restrictions::PROHIBIT_ALLOW_FLAG]==1) {   // Prohibited
                if ( $restriction[URE_Content_View_Restrictions::CONTENT_VIEW_WHOM]==1) {   // To All
                    $prohibited = true;
                } else {
                    $prohibited = $this->check_roles_for_prohibited( $roles );
                }
            } else {
                $prohibited = !$this->check_roles_for_allowed( $roles );
            }

            if ( !$prohibited ) {
                // term is not restricted for view for current user
                continue;     
            }
                    
            foreach( $posts as $post ) {
                if ( $this->do_not_restrict_editors( $post ) ) {
                    continue;
                }                
                // check roles restrictions
                $prohibited_posts[] = $post->ID;
            }
                        
        }   // foreach(...        
        
        if ( !empty( $prohibited_posts ) ) {
            if ( !is_array( $this->prohibited_posts ) ) {
             $this->prohibited_posts = array();
            }
            $this->prohibited_posts = array_merge( $this->prohibited_posts, $prohibited_posts );
        }
               
    }
    // end of get_term_level_restrictions()
    
    
    /**
     * Return full list of posts ID except of ID included into $posts0
     * @param array $posts0
     */
    private function reverse_posts_list($posts0) {
        global $wpdb;
        
        if (count($posts0)==0) {    // nothing to reverse - prohibit nothing
            return array();
        }
        
        $do_not_select = URE_Base_Lib::esc_sql_in_list('int', $posts0);
        $query = "select ID from {$wpdb->posts} where post_status='publish' and ID NOT IN ({$do_not_select})";
        $posts = $wpdb->get_col($query);
        if (!is_array($posts)) {
            $posts = array();
        }

        return $posts;
    }
    // end of reverse_posts_list()
    
    
    private function get_posts_by_authors($data) {
        global $wpdb;
        
        $authors = array();
        if (isset($data['authors']) && count($data['authors'])>0) {
            $authors = $data['authors'];
        }
        if (isset($data['own_data_only']) && $data['own_data_only']==1) {
            $user = wp_get_current_user();
            if (!in_array($user->ID, $authors)) {
                $authors[] = $user->ID;
            }
        }        
        if (count($authors)==0) {
            return array();
        }
        
        $authors_list = URE_Base_Lib::esc_sql_in_list('int', $authors);
        $query = "SELECT ID
                    FROM {$wpdb->posts}
                    WHERE post_author in ($authors_list) and post_type!='revision'";
        $posts = $wpdb->get_col($query);
        if (!is_array($posts)) {
            return array();
        }
        
        return $posts;
    }
    // end of get_posts_by_authors()
    

    /**
     * Get the list of posts prohibited for current user at the roles level
     * 
     */    
    private function get_role_level_restrictions() {
        
        $user = wp_get_current_user();        
        if ($user->ID==0) {
            return;
        }
        
        $blocked = URE_Content_View_Restrictions_Controller::load_access_data_for_user($user);
        if (empty($blocked)) {
            return;
        }
        if ($blocked['access_error_action']==2) {   // Show access error message, not hide post from the listings
            return;
        }
        
        $posts0 = array();
        if (isset($blocked['data']['posts']) && count($blocked['data']['posts'])>0) {
            $posts0 = $blocked['data']['posts'];
        }
        
        $posts1 = $this->get_posts_by_authors($blocked['data']);
        if (count($posts1)) {
            $posts0 = array_merge($posts0, $posts1);
        }
        
        if (isset($blocked['data']['terms']) && count($blocked['data']['terms'])>0) {
            $posts1 = $this->lib->get_posts_by_terms($blocked['data']['terms']);
            if (count($posts1)) {
                $posts0 = array_merge($posts0, $posts1);
            }
        }
        
        if ($blocked['access_model']==2) {  // Block not selected
            $posts = $this->reverse_posts_list($posts0);                        
        } else {
            $posts = $posts0;
        }
        
        $posts1 = array();
        foreach($posts as $post_id) {
            if ($this->do_not_restrict_editors($post_id)) {
                continue;
            }
            $posts1[] = $post_id;
        }
        
        if ( count($posts1) ) {
            if ( !is_array($this->prohibited_posts) ) {
                $this->prohibited_posts = array();
            }
            $this->prohibited_posts = array_merge( $this->prohibited_posts, $posts1 );
        }
    }
    // end of get_role_level_restrictons()
    
    
    public function get_current_user_prohibited_posts() {
        
	$user = wp_get_current_user();
        $transient_key = 'ure_posts_view_access_prohibited_posts_' . $user->ID;
        $this->prohibited_posts = get_transient($transient_key);
        if ( !is_array($this->prohibited_posts) ) {
            $this->restrict_even_if_can_edit = apply_filters('ure_restrict_content_view_for_authors_and_editors', false);
            // to exclude recursion call by WP_Query, when query posts available for editing
            remove_action('pre_get_posts', array($this, 'hide_prohibited_posts'), 999);

            // Take the list of post which current user can edit ( self::$checked_posts ) from the cache if exists
            $transient_key2 = 'ure_posts_view_access_checked_posts_'. $user->ID;
            self::$checked_posts = get_transient( $transient_key2 );
            if ( !is_array( self::$checked_posts ) ) {
                self::$checked_posts = array();
            }

            $this->prohibited_posts = array();
            $this->get_post_level_restrictions();
            $this->get_term_level_restrictions();
            $this->get_role_level_restrictions();
            
            set_transient( $transient_key, self::$checked_posts, 30 );
            

            // restore hide prohibited posts hook
            add_action('pre_get_posts', array($this, 'hide_prohibited_posts'), 999);

            $this->prohibited_posts = array_unique( $this->prohibited_posts );
            set_transient($transient_key, $this->prohibited_posts, 30);
        }
        
        return $this->prohibited_posts;
    }
    // end of get_current_user_prohibited_posts()
        
    
    private function is_term_action_redirection( $post_id ) {
        
        $terms = wp_get_post_categories( $post_id );
        if ( empty( $terms ) ) {
            return false;
        }
        foreach( $terms as $term_id ) {
            $access_error_action = (int) get_term_meta( $term_id, URE_Content_View_Restrictions::POST_ACCESS_ERROR_ACTION, true);
            if ( $access_error_action==4 ) {
                return true;
            }
        }
        
        return false;        
    }
    // end of is_term_action_redirection() 
    
    
    /**
     * Exclude prohibited post with redirection on access error from the list of prohibited post for the single page query
     */
    private function exclude_redirected_from_prohibited($prohibited_posts, $wp_query) {
        global $wpdb;
        
        if (!empty($wp_query->query_vars['name'])) {
            $name = $wp_query->query_vars['name'];  // for post
        } elseif (!empty($wp_query->query_vars['pagename'])) {
            $name = $wp_query->query_vars['pagename'];  // for query
        } else {
            return $prohibited_posts;
        }
        $query = $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_name=%s AND post_status='publish' LIMIT 0,1",
                    array($name));
        $post_id = $wpdb->get_var($query);
        if (empty($post_id)) {
            return $prohibited_posts;
        }        
        if (!in_array($post_id, $prohibited_posts)) {
            return $prohibited_posts;
        }
        
        $post_access_error_action = (int) get_post_meta($post_id, URE_Content_View_Restrictions::POST_ACCESS_ERROR_ACTION, true);
        
        if ( $post_access_error_action!=4 && !$this->is_term_action_redirection( $post_id ) ) {
            return $prohibited_posts;
        }
        
        foreach($prohibited_posts as $key=>$value) {
            if ($value==$post_id) {
                unset($prohibited_posts[$key]);
                break;
            }
        }
        
        return $prohibited_posts;
    }
    // end of exclude_redirected_from_prohibited()
    
    
    private function exclude_prohibited_posts_from_post_in($wp_query, $post_not_in) {
        $modified = false;
        $post_in = $wp_query->get('post__in');
        foreach($post_not_in as $post_id) {
            foreach($post_in as $key=>$value) {
                if ($post_id==$value) {
                    unset($post_in[$key]);
                    $modified = true;
                    break;
                }
            }
        }
        if ($modified) {
            $wp_query->set('post__in', $post_in);
        }
    }
    // end of exclude_prohibited_posts_from_post_in()
    
    
    /**
     *  Check if it's a bbPress topic and reply query
     *  Fix for conflict with bbpress/includes/core/filters.php, #232
     *  add_filter( 'posts_request', '_bbp_has_replies_where', 10, 2 ); 
     *  which is not applied to a query in case it contains 'post__not_in' or 'post__in' parameter
     */
    protected function is_bbpress_topic_reply_query( $query_post_type ) {

        $bbpress = $this->lib->get('bbpress');
        if ( empty( $bbpress ) ) {  // URE_bbPress class is not initialized
            return false;
        }
        if ( !$bbpress->is_active() ) { // bbPress plugin is not installed or active
            return false;
        }
                
        $expected_post_type = array( bbp_get_topic_post_type(), bbp_get_reply_post_type() );
        if ( $expected_post_type !== $query_post_type ) {   // It's not a bbPress topic and reply query
            return false;
        }        
        
        return true;
    }
    // end of is_bbpress_topic_reply_query()
    
    
    public function hide_prohibited_posts( $wp_query ) {
                
        if (is_admin()) {   // execute for front-end only
            return;
        }        
        if ($this->lib->is_super_admin()) { // no limits for super admin
            return;
        }
        $query_post_type = $wp_query->get( 'post_type' );
        if ( $this->is_bbpress_topic_reply_query( $query_post_type ) ) {
            return;
        }
        
        $prohibited_posts = $this->get_current_user_prohibited_posts();
        $prohibited_posts = $this->exclude_redirected_from_prohibited($prohibited_posts, $wp_query);
        if (count($prohibited_posts)>0) {
            $post_not_in = $wp_query->get('post__not_in');
            if (!empty($post_not_in)) {
                if (is_array($post_not_in)) {
                    $post_not_in = array_merge($post_not_in, $prohibited_posts);
                } else {
                    $post_not_in = $prohibited_posts;
                }                
            } else {
                $post_not_in = $prohibited_posts;
            }
            $post_in = $wp_query->get('post__in');
            if (!empty($post_in)) { // remove prohibited posts from posts ID list, if there are any
                $this->exclude_prohibited_posts_from_post_in($wp_query, $post_not_in);                
            } else {
                $wp_query->set('post__not_in', $post_not_in);
            }
        }
                        
    }
    // end of hide_prohibited_posts()
    
    
    /**
     * Modify where clause in case query contains 'p' parameter and 'post__not_in' parameter was ignored for that reason
     * 
     * @param array $args
     * @return array
     */
    public function hide_prohibited_posts2($where, $query) {
        global $wpdb;        
        
        if ( is_admin() ) {   // execute for front-end only
            return $where;
        }        
        if ( $this->lib->is_super_admin() ) { // no limits for super admin
            return $where;
        }
                
        if ( empty( $query->query_vars['p'] ) ) {
            return $where;
        }
        
        if ( !isset( $query->query_vars['post__not_in'] ) || empty( $query->query_vars['post__not_in'] ) ) {
            return $where;
        }
                                        
        $post_not_in = URE_Base_Lib::esc_sql_in_list( 'int',  $query->query_vars['post__not_in'] );
        $where .= " AND {$wpdb->posts}.ID NOT IN ($post_not_in)";
                        
        return $where;
    }
    // end of hide_prohibited_posts2()

    
    public function update_adjacent_post_where($where, $in_same_term, $excluded_terms, $taxonomy=null, $post=null) {
        
        if ( is_admin() ) {   // execute for front-end only
            return $where;
        }        
        if ( $this->lib->is_super_admin() ) { // no limits for super admin
            return $where;
        }

        $prohibited_posts = $this->get_current_user_prohibited_posts();
        if ( is_array( $prohibited_posts ) && count( $prohibited_posts )>0 ) {
            $posts = URE_Base_Lib::esc_sql_in_list( 'int', $prohibited_posts );
            $where .= ' AND p.ID not IN ( '. $posts .' )';
        }
        
        return $where;
    }
    // end of update_adjacent_post_where()
    

    /**
     * Separate restriction for the wlbdash custom post type as it does not use standard WordPress query for posts
     */
    public function wlb_dashboard_restrict() {        
        global $wp_meta_boxes;
        
	$this->restrict_even_if_can_edit = apply_filters('ure_restrict_content_view_for_authors_and_editors', false);
        // to exclude recursion call by WP_Query, when query posts available for editing
        remove_action('pre_get_posts', array($this, 'hide_prohibited_posts'), 999);

        foreach($wp_meta_boxes['dashboard'] as $widgets) {
            foreach($widgets['core'] as $key=>$widget) {
                if (strpos($key, 'wlbdash_')!==false) {
                    $data = explode('_', $key);
                    $post_id = (int) $data[1];
                    $post = get_post($post_id);
                    if ( $this->do_not_restrict_editors( $post ) ) {
                        continue;
                    }
                    if ( $this->do_not_hide_at_post_level($post) && 
                         $this->do_not_hide_at_role_level($post) ) {
                        continue;
                    }
                    remove_meta_box($widget['id'], 'dashboard', 'normal');
                }
            }
        }
								
	// restore hide prohibited posts hook
        add_action('pre_get_posts', array($this, 'hide_prohibited_posts'), 999);
        
    }
    // end of wlb_dashboard_restrict    
    
    
    /**
     * WooCommerce [products] shortcode - class WC_Shortcode_Products::get_products_ids() uses global transient to cache result 
     * of products query for 30 days. 
     * This function is hooked to WC_Shortcode_Products::product_loop() - wc_get_template_part() via 'wc_get_template_part' filter, 
     * in order to differentiate access for the different users
     * 
     * @global WP_Post $post
     * @param string $template
     * @param string $slug
     * @param string $name
     * @return string/boolean
     */
    public function hide_prohibited_products($template, $slug, $name) {
        global $post;
        
        if ($slug!=='content' && $name!=='product') {
            return $template;
        }
        if (is_admin()) {   // execute for front-end only
            return $template;
        }        
        if ($this->lib->is_super_admin()) { // no limits for super admin
            return $template;
        }
        
        $prohibited_posts = $this->get_current_user_prohibited_posts();        
        if (count($prohibited_posts)==0) {
            return $template;
        }
        foreach($prohibited_posts as $prohibited_id) {
            if ($post->ID==$prohibited_id) {
                return false;
            }
        }
        
        return $template;
    }
    // end of hide_prohibited_products()
    
}
// end of URE_Content_View_Restrictions_Posts_List
