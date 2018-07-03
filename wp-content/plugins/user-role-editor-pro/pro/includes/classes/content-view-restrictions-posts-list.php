<?php
/**
 * Exclude prohibited posts/pages and other post types from listings
 * 
 */
class URE_Content_View_Restrictions_Posts_List {

    protected static $instance = null; // object exemplar reference
    private $lib = null;
    private $prohibited_posts = null;
          
    
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

    
    private function do_not_restrict_editors($post) {
        if (!is_a( $post, 'WP_Post' )) {
            $post = get_post($post);
            if (empty($post)) {
                return false;
            }
        }
        
        $restrict_even_if_can_edit = apply_filters('ure_restrict_content_view_for_authors_and_editors', false);
        
        remove_action('pre_get_posts', array($this, 'hide_prohibited_posts'), 999); // to exclude recursion call by WP_Query, when query posts available for editing
        $can_edit_post = $this->lib->can_edit($post);
        add_action('pre_get_posts', array($this, 'hide_prohibited_posts'), 999);    // restore content view restrictions
        
        // no restrictions for users who can edit this post/page
        if ($can_edit_post && !$restrict_even_if_can_edit) {
            return true;
        }
        
        return false;
    }
    // end of do_not_restrict_editors()
    

    private function do_not_hide_at_post_level($post) {

        if (!is_a( $post, 'WP_Post' )) {
            $post = get_post($post);
            if (empty($post)) {
                return false;
            }
        }
        
        $ure_content_for_roles = get_post_meta($post->ID, URE_Content_View_Restrictions::content_for_roles, true);
        if (empty($ure_content_for_roles)) {
            return true;
        }

        $roles = URE_Content_View_Restrictions::extract_roles_from_string($ure_content_for_roles);
        if (count($roles) == 0) {
            return true;
        }

        if ($this->do_not_restrict_editors($post)) {
            return true;
        }

        $ure_prohibit_allow_flag = get_post_meta($post->ID, URE_Content_View_Restrictions::prohibit_allow_flag, true);
        $post_access_error_action = get_post_meta($post->ID, URE_Content_View_Restrictions::post_access_error_action, true);
        // Checks $post_access_error_action==2 to prohibit access in case $post_access_error_action do not set at all yet 
        // and has empty value.
        $result3 = $post_access_error_action == 2 ? true : false;
        if (!$this->lib->is_user_logged_in()) {
            $result = $result3;    // for prohibited access
            return $result;
        } elseif ($ure_prohibit_allow_flag == 1) {
            $result0 = true;
            $result1 = $result3;    // for prohibited access
        } else {
            $result0 = $result3;
            $result1 = true;     // for allowed access
        }

        foreach ($roles as $role) {
            if (current_user_can($role)) {
                return $result1;
            }
        }

        return $result0;
    }
    // end of do_not_hide_at_post_level()


    private function do_not_hide_at_role_level($post) {
        
        $current_user = wp_get_current_user();
        $blocked = URE_Content_View_Restrictions_Controller::load_access_data_for_user($current_user);
        if (empty($blocked['data'])) {
            return true;
        }

        if (!is_a( $post, 'WP_Post' )) {
            $post = get_post($post);
            if (empty($post)) {
                return false;
            }
        }
        
        if ($this->do_not_restrict_editors($post)) {
            return true;
        }
        
        if (URE_Content_View_Restrictions::is_object_restricted_for_role($post->ID, $blocked, 'posts')) {
            $result = $blocked['access_error_action'] == 2 ? true : false;
            return $result;
        }
        
        if (URE_Content_View_Restrictions::is_author_restricted_for_role($post->post_author, $blocked)) {
            $result = $blocked['access_error_action'] == 2 ? true : false;
            return $result;
        }

        if (URE_Content_View_Restrictions::is_term_restricted_for_role($post->ID, $blocked)) {
            $result = $blocked['access_error_action'] == 2 ? true : false;
            return $result;
        }
        
        if (URE_Content_View_Restrictions::is_page_template_restricted_for_role($post->ID, $blocked)) {
            $result = $blocked['access_error_action'] == 2 ? true : false;
            return $result;
        }

        return true;
    }

    // end of do_not_hide_at_role_level()


    public function hide_prohibited_pages($pages) {
        
        if (is_admin()) {   // execute for front-end only
            return $pages;
        }
        
        if (count($pages)==0) {
            return $pages;
        }

        if ($this->lib->is_super_admin()) {
            return $pages;
        }
        
        $pages1 = array();
        foreach($pages as $page) {
            if ($this->do_not_hide_at_post_level($page) &&
                $this->do_not_hide_at_role_level($page)) {
                $pages1[] = $page;
            }
        }
                
        return $pages1;
    }
    // end of hide_prohibited_pages()

    
    /*
     * Filter events from the Events Manager plugin
     * https://wordpress.org/plugins/events-manager/ 
     */
    public function hide_prohibited_events($events) {
        if (count($events)==0) {
            return $events;
        }
        
        $events1 = array();
        foreach($events as $event) {
            $post = get_post($event->post_id);
            if ($this->do_not_hide_at_post_level($post) &&
                $this->do_not_hide_at_role_level($post)) {
                $events1[] = $event;
            }
        }
        
        return $events1;
    }
    // end of hide_prohibited_events()

    
    private function get_post_level_data_from_db() {
        global $wpdb;
        
        $where = $wpdb->prepare(
                    " WHERE {$wpdb->postmeta}.meta_key=%s OR
                        {$wpdb->postmeta}.meta_key=%s OR
                        {$wpdb->postmeta}.meta_key=%s OR
                        {$wpdb->postmeta}.meta_key=%s",
                    array(
                        URE_Content_View_Restrictions::content_for_roles,
                        URE_Content_View_Restrictions::prohibit_allow_flag,
                        URE_Content_View_Restrictions::content_view_whom,
                        URE_Content_View_Restrictions::post_access_error_action
                        )
                            );
        $query = "SELECT post_id, meta_key, meta_value
                    FROM {$wpdb->postmeta}" . $where .' ORDER BY 1'; 
        $data = $wpdb->get_results($query);
        
        return $data;
    }
    // end of get_post_level_data_from_db()
    
    
    private function load_post_level_data() {
        
        $records = $this->get_post_level_data_from_db();
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
     * @param int $post_id
     */
    private function check_roles_for_prohibited($roles, $post_id) {                
        
        if (!is_array($roles) || count($roles)==0) {
            return;
        }
        
        $current_user = wp_get_current_user();
        $logged_in = $this->lib->is_user_logged_in();        
        if ($logged_in) {
            foreach($roles as $role) {
                if ($this->lib->user_can_role($current_user, $role)) {
                    $this->prohibited_posts[$current_user->ID][] = $post_id;
                    break;
                }
            }
        } else {
            foreach($roles as $role) {
                if ($role=='no_role') {
                    $this->prohibited_posts[0][] = $post_id;
                    break;
                }
            }    
        }
        
    }
    // end of check_roles_for_prohibited()
    
    
    /**
     * Loop through the roles for 'allowed' flag and add post ID to the phohibited list if user does not have allowed role
     * @param array $roles
     * @param array $prohibited
     * @param int $post_id
     */    
    private function check_roles_for_allowed($roles, $post_id) {        
        
        if (count($roles)==0) {
            return;
        }
        
        $current_user = wp_get_current_user();
        $logged_in = $this->lib->is_user_logged_in();        
        $allowed = false;
        if ($logged_in) {
            foreach($roles as $role) {
                if ($this->lib->user_can_role($current_user, $role)) {
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
        if (!$allowed) {
            $this->prohibited_posts[$current_user->ID][] = $post_id;
        }
        
    }
    // end of check_roles_for_allowed()
            
    
    /**
     * Get the list of posts prohibited for current user at the post level
     * 
     */
    private function get_post_level_restrictions() {        
        
        $data = $this->load_post_level_data();
        if (empty($data)) {
            return false;
        }
        
        $current_user_id = get_current_user_id();
        foreach($data as $post_id => $restriction) {
            if (!isset($restriction[URE_Content_View_Restrictions::content_view_whom])) {
                $restriction[URE_Content_View_Restrictions::content_view_whom] = 3; // Use "for selected roles only" as the default value
            }
            if ($restriction[URE_Content_View_Restrictions::prohibit_allow_flag]==2) { // Allow
                if ($restriction[URE_Content_View_Restrictions::content_view_whom]==1) {    // For All
                    continue;
                }
                if ($restriction[URE_Content_View_Restrictions::content_view_whom]==2 && $this->lib->is_user_logged_in()) {    // Logged in only (any role)
                    continue;
                }
            }
            if ($restriction[URE_Content_View_Restrictions::prohibit_allow_flag]==1) { // Prohibit
                if ($restriction[URE_Content_View_Restrictions::content_view_whom]==1) {    // All
                    $this->prohibited_posts[$current_user_id][] = $post_id;
                    continue;
                }
                if ($restriction[URE_Content_View_Restrictions::content_view_whom]==2 && $this->lib->is_user_logged_in()) {    // Logged in only (any role)
                    $this->prohibited_posts[$current_user_id][] = $post_id;
                    continue;
                }
            }
            
            if ($restriction[URE_Content_View_Restrictions::content_view_whom]==3 && empty($restriction[URE_Content_View_Restrictions::content_for_roles])) { 
                continue;
            }
            if (isset($restriction[URE_Content_View_Restrictions::post_access_error_action]) && 
                $restriction[URE_Content_View_Restrictions::post_access_error_action]!=1 && 
                $restriction[URE_Content_View_Restrictions::post_access_error_action]!=4) { // show access error message
                continue;
            }            
            if ($this->do_not_restrict_editors($post_id)) {
                continue;
            }
            
            $roles = URE_Content_View_Restrictions::extract_roles_from_string($restriction[URE_Content_View_Restrictions::content_for_roles]);
            if ($restriction[URE_Content_View_Restrictions::prohibit_allow_flag]==1) {   // Prohibited
                $this->check_roles_for_prohibited($roles, $post_id);
            } else {
                $this->check_roles_for_allowed($roles, $post_id);                
            }
        }
        
        return true;
    }
    // end of get_post_level_restrictions()
    
    
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
            $current_user_id = get_current_user_id();
            if (!in_array($current_user_id, $authors)) {
                $authors[] = $current_user_id;
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
        
        $current_user = wp_get_current_user();        
        if ($current_user->ID==0) {
            return;
        }
        
        $blocked = URE_Content_View_Restrictions_Controller::load_access_data_for_user($current_user);
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
        
        if (count($posts1)) {
            if (!isset($this->prohibited_posts[$current_user->ID]) || !is_array($this->prohibited_posts[$current_user->ID])) {
                $this->prohibited_posts[$current_user->ID] = array();
            }
            $this->prohibited_posts[$current_user->ID] = array_merge($this->prohibited_posts[$current_user->ID], $posts1);
        }
    }
    // end of get_role_level_restrictons()
    
    
    public function get_current_user_prohibited_posts() {
        
        $current_user_id = get_current_user_id();
        $this->prohibited_posts = get_transient('ure_posts_view_access_prohibited_posts');
        if (!is_array($this->prohibited_posts) || !isset($this->prohibited_posts[$current_user_id])) {
            $this->prohibited_posts = array($current_user_id => array());
            $this->get_post_level_restrictions();
            $this->get_role_level_restrictions();
            set_transient('ure_posts_view_access_prohibited_posts', $this->prohibited_posts, 30);
        }
        
        return $this->prohibited_posts[$current_user_id];
    }
    // end of get_current_user_prohibited_posts()
        
    
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
        
        $access_error_action = (int) get_post_meta($post_id, URE_Content_View_Restrictions::post_access_error_action, true);
        if ($access_error_action!=4) {
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
    
    
    public function hide_prohibited_posts($wp_query) {
                
        if (is_admin()) {   // execute for front-end only
            return;
        }        
        if ($this->lib->is_super_admin()) { // no limits for super admin
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
        
        if (is_admin()) {   // execute for front-end only
            return $where;
        }        
        if ($this->lib->is_super_admin()) { // no limits for super admin
            return $where;
        }
                
        if (empty($query->query_vars['p'])) {
            return $where;
        }
        
        if (!isset($query->query_vars['post__not_in']) || empty($query->query_vars['post__not_in'])) {
            return $where;
        }
                                        
        $post_not_in = URE_Base_Lib::esc_sql_in_list('int',  $query->query_vars['post__not_in']);
        $where .= " AND {$wpdb->posts}.ID NOT IN ($post_not_in)";
                        
        return $where;
    }
    // end of hide_prohibited_posts2()

    
    public function update_adjacent_post_where($where, $in_same_term, $excluded_terms, $taxonomy=null, $post=null) {
        
        if (is_admin()) {   // execute for front-end only
            return $where;
        }        
        if ($this->lib->is_super_admin()) { // no limits for super admin
            return $where;
        }

        $prohibited_posts = $this->get_current_user_prohibited_posts();
        if (is_array($prohibited_posts) && count($prohibited_posts)>0) {
            $posts = URE_Base_Lib::esc_sql_in_list('int', $prohibited_posts);
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
        
        foreach($wp_meta_boxes['dashboard'] as $widgets) {
            foreach($widgets['core'] as $key=>$widget) {
                if (strpos($key, 'wlbdash_')!==false) {
                    $data = explode('_', $key);
                    $post_id = (int) $data[1];
                    $post = get_post($post_id);
                    if ($this->do_not_hide_at_post_level($post) && $this->do_not_hide_at_role_level($post)) {
                        continue;
                    }
                    remove_meta_box($widget['id'], 'dashboard', 'normal');
                }
            }
        }

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