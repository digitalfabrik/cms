<?php
/*
 * Class: Access restrict to posts/pages on per site - per user - per post/page basis 
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */

class URE_Posts_Edit_Access {
    
    private $lib = null;
    private $user = null;   // URE_Posts_Edit_Access_User class instance     
    private $screen = null;

    
    public function __construct() {
    
        $this->lib = URE_Lib_Pro::get_instance();        
        new URE_Posts_Edit_Access_Role();
        new URE_Posts_Edit_Access_Bulk_Action();
        $this->user = new URE_Posts_Edit_Access_User($this);                        
        
        add_action( 'init', array($this, 'set_hooks_general') );
        add_action( 'admin_init', array($this, 'set_hooks_admin') );
        add_filter( 'map_meta_cap', array($this, 'block_edit_post'), 10, 4 );
                
    }
    // end of __construct()                

    // apply restrictions to the post query
    public function pre_get_posts_hook() {
        
        add_action('pre_get_posts', array($this, 'restrict_posts_list' ), 55);
        
    }
    // end pre_get_posts_hooks()
    
    
    public function set_hooks_admin() {
                
        $wc_bookings_active = URE_Plugin_Presence::is_active('woocommerce-bookings');   // Woocommerce Bookings plugin 
        if ($wc_bookings_active) {
            URE_WC_Bookings::separate_user_transients();            
        }
                                
        // apply restrictions to the post query
        $this->pre_get_posts_hook();

        // apply restrictions to the pages list from stuff respecting get_pages filter
        add_filter('get_pages', array($this, 'restrict_pages_list'));

        // Refresh counters at the Views by post statuses
        add_filter('wp_count_posts', array($this, 'recount_wp_posts'), 10, 3);        
        add_action('current_screen', array($this, 'add_views_filter'));                
                        
        // Auto assign to a new created post the 1st from the allowed terms
        add_filter('wp_insert_post', array($this, 'auto_assign_term'), 10, 3);
        
        if ($wc_bookings_active) {  
            new URE_WC_Bookings($this->user);
        }                
        
        if (URE_Plugin_Presence::is_active('duplicate-post')) {
            add_action('dp_duplicate_post', 'URE_Duplicate_Post::prevent_term_remove', 45, 2);
            add_action('dp_duplicate_page', 'URE_Duplicate_Post::prevent_term_remove', 45, 2);
        }
    }
    // end of set_hooks_admin()
        

    public function set_hooks_general() {
        
        // restrict categories available for selection at the post editor
        add_filter('list_terms_exclusions', array($this, 'exclude_terms'));        
        
    }
    // end of set_hooks_front_end()
    
            
    public function recount_wp_posts($counts, $type, $perm) {
        global $wpdb;

        if (!post_type_exists($type)) {
            return new stdClass;
        }        
        if (!$this->should_apply_restrictions_to_wp_page()) {
            return $counts;
        }                                
        // do not limit user with Administrator role or the user for whome posts/pages edit restrictions were not set
        if (!$this->user->is_restriction_applicable()) {
            return $counts;
        }    
        
        $restrict_it = apply_filters('ure_restrict_edit_post_type', $type);
        if (empty($restrict_it)) {
            return $counts;
        }

        $cache_key = 'ure_'._count_posts_cache_key($type, $perm);
        $counts = wp_cache_get($cache_key, 'counts');
        if (false !== $counts) {
            return $counts;
        }

        $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s";
        if ('readable' == $perm && is_user_logged_in()) {
            $post_type_object = get_post_type_object($type);
            if (!current_user_can($post_type_object->cap->read_private_posts)) {
                $query .= $wpdb->prepare(" AND (post_status != 'private' OR ( post_author = %d AND post_status = 'private' ))", get_current_user_id());
            }
        }
        $restriction_type = $this->user->get_restriction_type();
        $posts_list = $this->user->get_posts_list();
        if ($restriction_type==1) {   // Allow
            if (count($posts_list)==0) {
                $query = false;
            } else {
                $posts_list_str = URE_Base_Lib::esc_sql_in_list('int', $posts_list);
                $query .= " AND ID IN ($posts_list_str)";
            }
        } elseif ($restriction_type==2) {    // Prohibit
            if (count($posts_list)>0) {
                $posts_list_str = URE_Base_Lib::esc_sql_in_list('int', $posts_list);
                $query .= " AND ID NOT IN ($posts_list_str)";
            }
        }                
        if (!empty($query)) {
            $query .= ' GROUP BY post_status';
            $results = (array) $wpdb->get_results($wpdb->prepare($query, $type), ARRAY_A);
        } else {
            $results = array();
        }
        $counts = array_fill_keys(get_post_stati(), 0);
        foreach ($results as $row) {
            $counts[$row['post_status']] = $row['num_posts'];
        }
        $counts = (object) $counts;
        wp_cache_set($cache_key, $counts, 'counts');

        return $counts;
    }
    // end of recount_wp_posts()


    public function add_views_filter() {
        
        $this->screen = get_current_screen();
        if (!empty($this->screen)) {
            add_filter("views_{$this->screen->id}", array($this, 'update_mine_view_counter'), 10, 1);
        }
        
    }
    // end of add_views_filter()
    
    
    /**
     * Helper to create links to edit.php with params.
     *
     * Taken from class-wp-posts-list-table.php, as it's declared as protected.
     *
     * @param array  $args  URL parameters for the link.
     * @param string $label Link text.
     * @param string $class Optional. Class attribute. Default empty string.
     * @return string The formatted link string.
     */
    private function get_edit_link($args, $label, $class = '') {
        
        $url = add_query_arg($args, 'edit.php');
        $class_html = '';
        if (!empty($class)) {
            $class_html = sprintf(' class="%s"', esc_attr($class));
        }

        return sprintf('<a href="%s"%s>%s</a>', esc_url($url), $class_html, $label);
    }
    // end of get_edit_link()
    

    /**
     * Code was built on a case of WP_Posts_List_Table::get_views() (wp-admin/includes/class-wp-posts-list-table.php), WordPress v. 4.7.5
     * 
     * @global WPDB $wpdb
     * @param array $views
     * @return array
     */
    public function update_mine_view_counter($views) {
        global $wpdb;
        
        if (!isset($views['mine'])) {
            return $views;
        }
        
        $current_user_id = get_current_user_id();
        if ($current_user_id==0) {
            return $views;
        }
        
        // do not limit user with Administrator role or the user for whome posts/pages edit restrictions were not set
        if (!$this->user->is_restriction_applicable()) {
            return $views;
        }
        
        $post_type = $this->screen->post_type;
        $exclude_states   = get_post_stati( array('show_in_admin_all_list' => false) );
        $post_statuses = URE_Base_Lib::esc_sql_in_list('string', $exclude_states);
        
        $query = $wpdb->prepare(
                    "SELECT COUNT(1)
                        FROM {$wpdb->posts}
                        WHERE post_type=%s AND post_author=%d",
                    array($post_type, $current_user_id));                        
        $query .= " AND post_status NOT IN ( $post_statuses )";
        
        $restriction_type = $this->user->get_restriction_type();        
        $posts_list = $this->user->get_posts_list();
        if (count($posts_list)>0) {
            $posts_list_str = URE_Base_Lib::esc_sql_in_list('int', $posts_list);
            if ($restriction_type==1) {   // Allow
                $query .= " AND ID IN ($posts_list_str)";
            } elseif ($restriction_type==2) {    // Prohibit
                $query .= " AND ID NOT IN ($posts_list_str)";
            }
        }
                                                
        $user_posts_count = intval($wpdb->get_var($query));
        if ($user_posts_count==0) {
            unset($views['mine']);
            return $views;
        }
        
     			$mine_args = array(
        				'post_type' => $post_type,
            'author' => $current_user_id
        );

        $mine_inner_html = sprintf(
            _nx(
             'Mine <span class="count">(%s)</span>',
             'Mine <span class="count">(%s)</span>',
             $user_posts_count,
             'posts'
            ),
            number_format_i18n($user_posts_count)
       );
       
        if (isset($_GET['author']) && ($_GET['author']==$current_user_id)) {
           $class = 'current';
        } else {
            $class = '';
        }

        $mine = $this->get_edit_link( $mine_args, $mine_inner_html, $class);
        $views['mine'] = $mine;        
    
        return $views;
    }
    // end of update_mine_view_counter()
            
    
    public function block_edit_post($caps, $cap='', $user_id=0, $args=array()) {
        
        // return $caps;   // Debugging!!!
        
        $current_user_id = get_current_user_id();
        if ($current_user_id==0) {
            return $caps;
        }
        
        if (count($args)>0) {
            $post_id = $args[0];
        } else {
            $post_id = filter_input(INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT);
        }
        if (empty($post_id)) {
            return $caps;
        }
        
        remove_filter('map_meta_cap', array($this, 'block_edit_post'), 10); //  exclude possible recursion
        $is_super_admin = $this->lib->is_super_admin();
        add_filter('map_meta_cap', array($this, 'block_edit_post'), 10, 4);
        if ($is_super_admin) {
            return $caps;
        }
         
        $post = get_post($post_id);
        if (empty($post)) {
            return $caps;
        }
        if (!post_type_exists($post->post_type)) {
            return $caps;
        }
        
        $custom_caps = $this->lib->get_edit_custom_post_type_caps();
        if (!in_array($cap, $custom_caps)) {
            return $caps;
        }
        
        if (!empty($_POST['original_post_status']) && $_POST['original_post_status']=='auto-draft' &&
            !empty($_POST['auto_draft']) && $_POST['auto_draft']==1) {  
            // allow to save new post/page
            // that's admin responsibility if user with 'create_posts' 
            // then will not can edit a new created post due to existing editing restrictions
            return $caps;
        }
        
        $posts_list = $this->user->get_posts_list();                                   
        if (count($posts_list)==0) {        
            return $caps;
        }                                
                        
        remove_filter('map_meta_cap', array($this, 'block_edit_post'), 10, 4);  // do not allow endless recursion
        $restrict_it = apply_filters('ure_restrict_edit_post_type', $post->post_type);
        add_filter('map_meta_cap', array($this, 'block_edit_post'), 10, 4);     // restore filter
        if (empty($restrict_it)) {            
            return $caps;
        }        
        
        // automatically add related attachments to the list of posts
        $attachments_list = $this->user->get_attachments_list();
        if (!empty($attachments_list)) {
            $posts_list = array_merge($posts_list, $attachments_list);
        }

        
        if ($post->post_type=='revision') { // Check access to the related post, not to the revision
            $post_id = $post->post_parent;
        }
                        
        $do_not_allow = in_array($post_id, $posts_list);    // not edit these
        $restriction_type = $this->user->get_restriction_type();
        if ($restriction_type==1) {
            $do_not_allow = !$do_not_allow;   // not edit others
        }
        if ($do_not_allow) {
            $caps[] = 'do_not_allow';
        }                    
        
        return $caps;
    }
    // end of block_edit_post()
                               
    
    private function update_post_query($query) {
        
        $restriction_type = $this->user->get_restriction_type();
        $posts_list = $this->user->get_posts_list();
        
        if ($restriction_type==1) {   // Allow
            if (count($posts_list)==0) {
                $query->set('p', -1);   // return empty list
            } else {
                $query->set('post__in', $posts_list);
            }
        } elseif ($restriction_type==2) {    // Prohibit
            if (count($posts_list)>0) {
                $query->set('post__not_in', $posts_list);
            }
        }
    }
    // end of update_post_query()
    
             
    private function should_apply_restrictions_to_wp_page() {
    
        global $pagenow;
        
        if (!($pagenow == 'edit.php' || $pagenow == 'upload.php' || 
            ($pagenow=='admin-ajax.php' && !empty($_POST['action']) && $_POST['action']=='query-attachments'))) {
            if (!function_exists('cms_tpv_get_options')) {   // if  "CMS Tree Page View" plugin is not active
                return false;
            } elseif ($pagenow!=='index.php') { //  add Dashboard page for "CMS Tree Page View" plugin widget
                return false;
            }            
        }
        
        return true;
        
    }
    // end of should_apply_restrictions_to_wp_page()
    
    /**
     * Exclude from $query->query['post__in'] ID, which is not included into $list
     * @param WP_Query $query
     * @param array $list
     */
    private function leave_just_allowed($query, $list) {        
        $list1 = array();
        foreach ($query->query['post__in'] as $id) {
            if (in_array($id, $list)) {
                $list1[] = $id;
            }
        }
        if (empty($list1)) {
            $list1[] = -1;
        }
        $query->set('post__in', $list1);
    }
    // end of leave_just_allowed()
    
    
    private function _restrict_posts_list($query) {
        
        if (!$this->should_apply_restrictions_to_wp_page()) {
            return;
        }                        
        
        // do not limit user with Administrator role or the user for whome posts/pages edit restrictions were not set
        if (!$this->user->is_restriction_applicable()) {
            return;
        }

        $suppressing_filters = $query->get('suppress_filters'); // Filter suppression on?
        if ($suppressing_filters) {
            return;
        }                   
        
        if (!empty($query->query['post_type'])) {
            $restrict_it = apply_filters('ure_restrict_edit_post_type', $query->query['post_type']);
            if (empty($restrict_it)) {
                return;
            }         
        }
        
        if ($query->query['post_type']=='attachment') {             
            $show_full_list = apply_filters('ure_attachments_show_full_list', false);
            if ($show_full_list) { // show full list of attachments
                return;
            }            
            $restriction_type = $this->user->get_restriction_type();
            $attachments_list = $this->user->get_attachments_list();
            if ($restriction_type==1) {   // Allow
                if (count($attachments_list)==0) {
                    $attachments_list[] = -1;
                    $query->set('post__in', $attachments_list);
                } elseif (empty($query->query['post__in'])) {
                    $query->set('post__in', $attachments_list);
                } else {
                    $this->leave_just_allowed($query, $attachments_list);
                }
            } else {    // Prohibit
                $query->set('post__not_in', $attachments_list);
            }            
        } else {
            $this->update_post_query($query);
        }
        
        
    }
    // end of _restrict_posts_list()
    
    
    public function restrict_posts_list($query) {                

        // In order to exclude possible recursion calls
        remove_action('pre_get_posts', array($this, 'restrict_posts_list' ), 55);
        
        $this->_restrict_posts_list($query);
        
        // restore removed pre_get_posts hook
        $this->pre_get_posts_hook();
    }
    // end of restrict_posts_list()

            
    public function restrict_pages_list($pages) {
                
        if (!$this->should_apply_restrictions_to_wp_page()) {
            return $pages;
        }                        
        
        // do not limit user with Administrator role
        if (!$this->user->is_restriction_applicable()) {
            return $pages;
        }
        
        $restrict_it = apply_filters('ure_restrict_edit_post_type', 'page');
        if (empty($restrict_it)) {
            return $pages;
        }
        
        $posts_list = $this->user->get_posts_list();
        if (count($posts_list)==0) {
            return $pages;
        } 
        
        $restriction_type = $this->user->get_restriction_type();
        
        $pages1 = array();
        foreach($pages as $page) {
            if ($restriction_type==1) { // Allow: not edit others
                if (in_array($page->ID, $posts_list)) {    // not edit others
                    $pages1[] = $page;
                    
                }
            } else {    // Prohibit: Not edit these
                if (!in_array($page->ID, $posts_list)) {    // not edit these
                    $pages1[] = $page;                    
                }                
            }
        }
        
        return $pages1;
    }
    // end of restrict pages_list()
        
    
    private function calc_post_type( $current_page, $http_ref ) {
        
        if ( $current_page=='post-new.php') {
            $key_pos = strpos( $http_ref, 'post_type=');
            if ( $key_pos===false ) {
                $post_type = 'post';
            } else {
                $str = substr( $http_ref, $key_pos + 10 );
                $parts = explode( '&', $str );
                $post_type = $parts[0];
            }
        } else {
            $matches = array();
            preg_match( '/post=([0-9]+)\&/', $http_ref, $matches);
            $post_id = !empty( $matches[1] ) ? (int) $matches[1] : 0;
            if ( empty( $post_id ) ) {
                return false;
            }
            $post = get_post( $post_id );
            if ( empty( $post ) ) {
                return false;
            }
            $post_type = $post->post_type;
        }
        
        return $post_type;
    }
    // end of calc_post_type()
    
    
    private function calc_terms_to_exclude( $terms_list_str ) {
        
        $restriction_type = $this->user->get_restriction_type();
        if ($restriction_type == 1) {   // allow
            // exclude all except included to the list
            remove_filter('list_terms_exclusions', array($this, 'exclude_terms'));  // delete our filter in order to avoid recursion when we call get_all_category_ids() function            
            $taxonomies = array_keys(get_taxonomies(array('public'=>true, 'show_ui'=>true), 'names')); // get array of registered taxonomies names (public only)
            $all_terms = get_terms($taxonomies, array('fields'=>'ids', 'hide_empty'=>0)); // take full categories list from WordPress
            add_filter('list_terms_exclusions', array($this, 'exclude_terms'));  // restore our filter back            
            $terms_list = explode(',', str_replace(' ', '', $terms_list_str));                        
            $terms_to_exclude = array_diff($all_terms, $terms_list); // delete terms ID, to which we allow access, from the full terms list                        
        } else {    // prohibit
            $terms_to_exclude = explode(',', str_replace(' ', '', $terms_list_str));
        }
        $terms_to_exclude_str = URE_Base_Lib::esc_sql_in_list('int', $terms_to_exclude);
        
        return $terms_to_exclude_str;
    }
    // end of calc_terms_to_exclude()
    

    public function exclude_terms( $exclusions ) {
        
        global $pagenow, $post_type;
        
        $restricted_pages = array('edit.php', 'post.php', 'post-new.php');
        if ( !in_array( $pagenow, $restricted_pages ) ) {
            if ( !isset( $_SERVER['HTTP_REFERER'] ) ) {
                return $exclusions;
            }
            $http_referer_page = $this->lib->extract_command_from_url( $_SERVER['HTTP_REFERER'], false );
            if ( !in_array( $http_referer_page, $restricted_pages ) ) {
                return $exclusions;
            }
            $current_page = $http_referer_page;
        } else {
            $current_page = $pagenow;
        }
        
        if ( !$this->user->is_restriction_applicable() ) {
            return $exclusions;
        }
        
        if ( empty( $post_type ) ) {
            if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
                return $exclusions;
            }
            $post_type = $this->calc_post_type( $current_page, $_SERVER['HTTP_REFERER'] );
            if ( empty( $post_type ) ) {
                return $exclusions;
            }
        }
        
        $restrict_it = apply_filters('ure_restrict_edit_post_type', $post_type);
        if ( empty( $restrict_it ) ) {
            return $exclusions;
        }
        
        $terms_list_str = $this->user->get_post_categories_list();
        if ( empty( $terms_list_str ) ) {
            return $exclusions;
        }
    
        $terms_to_exclude_str = $this->calc_terms_to_exclude( $terms_list_str );
        if ( !empty( $exclusions ) ) {
            $exclusions .= ' AND ';
        }
        $exclusions .= "(t.term_id not IN ($terms_to_exclude_str))";   // build WHERE expression for SQL-select command
        
        return $exclusions;
    }
    // end of exclude_terms()


    private function assign_term_from_allowed($post_id, $terms_list_str, $registered_taxonomies) {
        global $wpdb;
        
        $terms_list = explode(',', str_replace(' ', '', $terms_list_str));
        foreach($terms_list as $term_id) {        
            $query = $wpdb->prepare('SELECT taxonomy FROM '. $wpdb->term_taxonomy .' WHERE term_id=%d', $term_id);
            $taxonomy = $wpdb->get_var($query);
            if (empty($taxonomy)) {
                continue;
            }
            if (in_array($taxonomy, $registered_taxonomies)) {
                // use as a default the 1st taxonomy from the allowed list, available for this post type
                wp_set_post_terms( $post_id, $term_id, $taxonomy);
                break;
            }
        }        
        
    }
    // end of assign_term_with_allowed()
    
    
    private function assign_term_having_not_allowed($post_id, $not_allowed_terms_list_str, $registered_taxonomies) {
        global $wpdb;
                
        $terms_list = explode(',', $not_allowed_terms_list_str);
        $term_list_str = URE_Base_Lib::esc_sql_in_list('int', $terms_list);        
        $query = "SELECT term_id FROM {$wpdb->terms} WHERE term_id NOT IN ($term_list_str)";
        $terms = $wpdb->get_results($query);
        if (empty($terms)) {
            return;
        }
        
        foreach($terms as $term) {        
            $query = "SELECT taxonomy FROM {$wpdb->term_taxonomy} WHERE term_id={$term->term_id}";
            $taxonomy = $wpdb->get_var($query);
            if (empty($taxonomy)) {
                continue;
            }
            if (in_array($taxonomy, $registered_taxonomies)) {
                // use as a default the 1st taxonomy from the allowed list, available for this post type
                wp_set_post_terms( $post_id, $term->term_id, $taxonomy);
                break;
            }
        }
        
    }
    // end of assign_term_having_not_allowed()
    
    
    /**
     * Assign to a new created post the 1st available taxonomy term from allowed terms list
     * 
     * @global string $pagenow
     * @param int $post_id
     * @param WP_POST $post
     * @param bool $update
     * @return void
     */
    public function auto_assign_term($post_id, $post, $update) {        
        
        if (empty($post_id)) {
            return;
        }       
        if ($post->post_type=='revision') { // Do nothing with revisions
            return;
        }
        
        // do not limit user with Administrator role or the user for whome posts/pages edit restrictions were not set
        if (!$this->user->is_restriction_applicable()) {
            return;
        }
        
        // Exclude cache conflicts with a new created post
        // Example of a fixed issue - empty list of custom fields, due to post list taken from transient did not include ID of a new created post
        $this->user->delete_transient();    
        
        $terms_list_str = $this->user->get_post_categories_list();
        if (empty($terms_list_str)) {   // There is no restriction by terms - there is no need to auto assign the 1st allowed term
            return;
        }
        
        $registered_taxonomies = get_object_taxonomies($post->post_type, 'names');
        if (empty($registered_taxonomies)) {    // Nothing to assign
            return;
        }        
        
        $post_terms = wp_get_object_terms($post_id, $registered_taxonomies, array('fields'=>'ids'));
        if (!empty($post_terms) && $post_terms[0]!==1) {  // There are some terms assigned to this post already and it's not equal to 'Uncategorized' (ID=1)
            return;
        }        
        
        $restriction_type = $this->user->get_restriction_type();        
        if ($restriction_type==1) {   // allow
            $this->assign_term_from_allowed($post_id, $terms_list_str, $registered_taxonomies);
        } else {
            $this->assign_term_having_not_allowed($post_id, $terms_list_str, $registered_taxonomies);
        }
                                        
    }
    // end of auto_assign_term()
    
}
// end of URE_Posts_Edit_Access
