<?php
/*
 * Project: User Role Editor Pro WordPress plugin
 * Content view access by selected roles management - at post level
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Content_View_Restrictions {
    
    const VIEW_POSTS_ACCESS_CAP = 'ure_view_posts_access';
    const CONTENT_FOR_ROLES = 'ure_content_for_roles';
    const PROHIBIT_ALLOW_FLAG = 'ure_prohibit_allow_flag';
    const CONTENT_VIEW_WHOM = 'ure_content_view_whom';
    const POST_ACCESS_ERROR_ACTION = 'ure_post_access_error_action';
    const POST_ACCESS_ERROR_MESSAGE = 'ure_post_access_error_message';
    const ACCESS_ERROR_URL = 'ure_view_access_error_url';
    
    private $lib = null;    
    
    // What action to take, if view is prohibited
    private static $access_error_action = 0;
    // To what URL to redirect, if view is prohibited
    private static $access_error_url = '';
    
    // used for testing only
    private $pva = null;
    private $cvrpl = null;
    
    
    public function __construct() {        
        
        $this->lib = URE_Lib_Pro::get_instance();
        
        $this->pva = new URE_Posts_View_Access();
        $this->cvrpl = URE_Content_View_Restrictions_Posts_List::get_instance();

        add_action( 'add_meta_boxes', array($this, 'add_post_meta_box') );
        add_action( 'admin_enqueue_scripts', array($this, 'admin_css_action') );
        add_action( 'admin_enqueue_scripts', array($this, 'admin_load_js') );
        
        add_action( 'save_post', array('URE_Content_View_Restrictions_Editor', 'save_post_meta_data') );
        add_action( 'add_attachment', array('URE_Content_View_Restrictions_Editor', 'save_post_meta_data') );
        add_action('edit_attachment', array('URE_Content_View_Restrictions_Editor', 'save_post_meta_data'));
                                
        // set content view restrictions
        add_filter('the_content', array($this, 'restrict'), 999);
        add_filter('get_the_excerpt', array($this, 'restrict'), 999);
        add_filter('the_excerpt', array($this, 'restrict'), 999);
        add_filter('the_content_feed', array($this, 'restrict'), 999);
        add_filter('the_excerpt_rss', array($this, 'restrict'), 999);
        add_filter('comment_text_rss', array($this, 'restrict'), 999);
        
        // Apply WordPress formatting filters for the post access error message.
        add_filter('ure_post_access_error_message', 'wptexturize');
        add_filter('ure_post_access_error_message', 'convert_smilies');
        add_filter('ure_post_access_error_message', 'convert_chars');
        add_filter('ure_post_access_error_message', 'wpautop');
        add_filter('ure_post_access_error_message', 'shortcode_unautop');
        add_filter('ure_post_access_error_message', 'do_shortcode');


        // this filter is applied at map_meta_cap() for 'edit_post_meta' capability
        add_filter('auth_post_meta_'. self::CONTENT_FOR_ROLES, array($this, 'auth_post_meta') );
        add_filter('auth_post_meta_'. self::PROHIBIT_ALLOW_FLAG, array($this, 'auth_post_meta') );
        add_filter('auth_post_meta_'. self::CONTENT_VIEW_WHOM, array($this, 'auth_post_meta') );
        add_filter('auth_post_meta_'. self::POST_ACCESS_ERROR_ACTION, array($this, 'auth_post_meta') );
        add_filter('auth_post_meta_'. self::POST_ACCESS_ERROR_MESSAGE, array($this, 'auth_post_meta') );
        add_filter('auth_post_meta_'. self::ACCESS_ERROR_URL, array($this, 'auth_post_meta') );
        
        global $wlb_plugin;
        if (!empty($wlb_plugin)) {
            add_action('wp_dashboard_setup', array($this, 'wlb_dashboard_restrict'), 1000000);
        }
        
        // WooCommerce registers its taxonomies using 'init' action. So we should execute code only after it        
        add_action( 'init', array($this, 'do_on_init'), 99 );
        
        add_action( 'template_redirect', array($this, 'redirect'), 9 );
        
    }
    // end of __construct()
    
    
    public function do_on_init() {
        
        $this->plugins_compatibility();

        // Do not move to action executed earlier!
        // WooCommerce registers its taxonomies using 'init' action. So we should execute code only after it
        $taxonomies = get_taxonomies(
                array('public'=>true,
                      'show_ui'=>true), 
                'names');
        foreach( $taxonomies as $taxonomy ) {
            add_action( "{$taxonomy}_edit_form", array('URE_Content_View_Restrictions_Editor', 'render_term_box') );
            add_action( "edited_{$taxonomy}", array('URE_Content_View_Restrictions_Editor', 'save_term_meta_data') );
        }
        
    }
    // end of add_init_hooks()


    public static function get_access_error_action() {
        
        return URE_CONTENT_VIEW_RESTRICTIONS::$access_error_action;
    }
    
    // block access to URE's post meta (custom) fields, if user does not have enough permissions
    public function auth_post_meta() {
        
        $allowed = current_user_can( self::VIEW_POSTS_ACCESS_CAP );
        
        return $allowed;
        
    }
    // end of auth_post_meta()    
				
    
    public function add_post_meta_box() {

        if ( !current_user_can( self::VIEW_POSTS_ACCESS_CAP ) ) {
            return false;
        }
        
        $post_types = $this->lib->_get_post_types();
        
        //  Post types to exclude, as they never shown independently, only as a part of a post or page.
        // Custom post types from Advanced Custom Fields plugin (https://wordpress.org/plugins/advanced-custom-fields/)
        $exclude_cpt = array('acf-field-group', 'acf-field');
        $exclude_cpt = apply_filters( 'ure_cvr_exclude_cpt', $exclude_cpt );
        
        foreach ( $post_types as $post_type ) {
            if ( in_array( $post_type, $exclude_cpt ) ) {
                continue;
            }
            add_meta_box(
                    'ure_content_view_restrictions_meta_box', 
                    esc_html__( 'Content View Restrictions', 'user-role-editor' ), 
                    array('URE_Content_View_Restrictions_Editor', 'render_post_meta_box'),
                    $post_type, 
                    'normal', 
                    'low',
                    array('__back_compat_meta_box' => false)
            );
        }
        
        return true;
    }
    // end of add_meta_box()
            

   /**
     * Load plugin javascript stuff
     * 
     * @param string $hook_suffix
     */
    public function admin_load_js($hook_suffix) {
        if ( !in_array( $hook_suffix, array('post.php', 'post-new.php', 'term.php') ) ) {
            return false;
        }   
                
        if ( !current_user_can(self::VIEW_POSTS_ACCESS_CAP) ) {
            return false;
        }
        wp_enqueue_script( 'jquery-ui-dialog', '', array( 'jquery-ui-core', 'jquery-ui-button', 'jquery') );
        wp_register_script( 'ure-pro-content-view-restrictions', plugins_url( '/pro/js/content-view-restrictions.js', URE_PLUGIN_FULL_PATH ) );
        wp_enqueue_script( 'ure-pro-content-view-restrictions' );
        wp_localize_script( 'ure-pro-content-view-restrictions', 'ure_data_pro', array(
            'wp_nonce' => wp_create_nonce('user-role-editor'),
            'edit_content_for_roles' => esc_html__('Edit Roles List', 'user-role-editor'),
            'edit_content_for_roles_title' => esc_html__('Roles List restrict/allow content view', 'user-role-editor'),
            'save_roles_list' => esc_html__('Save', 'user-role-editor'),
            'close' => esc_html__('Close', 'user-role-editor')
        ));

        return true;
    }
    // end of admin_load_js()
    
    
    public function admin_css_action( $hook_suffix ) {        
        if ( !in_array( $hook_suffix, array('post.php', 'post-new.php', 'term.php') ) ) {
            return false;
        }
        if ( !current_user_can( self::VIEW_POSTS_ACCESS_CAP ) ) {
            return false;
        }
        
        wp_enqueue_style('wp-jquery-ui-dialog');

        return true;
    }
    // end of admin_css_action()                
    
    
    private static function get_object_access_error_message( $object_type, $object_id ) {
        
        $message = '';
        $action = (int) URE_Content_View_Restrictions_Editor::get_object_meta( $object_type, $object_id, self::POST_ACCESS_ERROR_ACTION );
        if ($action===3) { // Show custom access error message for this post
            $message = URE_Content_View_Restrictions_Editor::get_object_meta( $object_type, $object_id, self::POST_ACCESS_ERROR_MESSAGE );
        }
            
        if (empty($message)) { // Show global access error message
            $lib = URE_Lib_Pro::get_instance();
            $message = stripslashes( $lib->get_option( 'post_access_error_message' ) );
        }        
        
        $message = apply_filters( 'ure_post_access_error_message', $message );
        
        return $message;
    }
    // end of get_object_access_error_message()
    
    
    private static function check_object_permissions( $content, $object_type, $object_id ) {
        
        $lib = URE_Lib_Pro::get_instance();
        $data = array('object_restricted'=>false, 'content'=>$content);
        $prohibit_allow_flag = URE_Content_View_Restrictions_Editor::get_object_meta( $object_type, $object_id, self::PROHIBIT_ALLOW_FLAG );
        $content_view_whom = URE_Content_View_Restrictions_Editor::get_object_meta( $object_type, $object_id, self::CONTENT_VIEW_WHOM );
        if ( empty( $content_view_whom ) ) {
            $content_view_whom = 3;    // Use as the default value
        }
        
        if ( $prohibit_allow_flag==2 ) {  // Allow
            if ( $content_view_whom==1 ) { // For All
                // Allowed for everybody - no restrictions set at the object level
                return $data;
            }
            if ( $content_view_whom==2 && $lib->is_user_logged_in()) {  //  Allow to logged in only (any role)
                // Restrictions set at object level and access allowed
                $data['object_restricted'] = true;
                return $data;
            }
        }
        
        if ( $content_view_whom==3 ) { //  Selected roles only
            $ure_content_for_roles = URE_Content_View_Restrictions_Editor::get_object_meta( $object_type, $object_id, self::CONTENT_FOR_ROLES, true );
            if ( empty( $ure_content_for_roles ) ) {    
                // Allowed for the selected roles only, but no roles selected => Allowed for everybody => not restricted
                return $data;
            }
            $roles = explode( ', ', $ure_content_for_roles );
            if ( count( $roles )==0 ) {
                // Allowed for the selected roles only, but no roles selected => Allowed for everybody
                return $data;
            }
        } else {
            $roles = array();
        }

        // permissions are applied at the object (post/term) level
        $data['object_restricted'] = true;        
        $post_access_error_message = self::get_object_access_error_message( $object_type, $object_id );                
        if ( $prohibit_allow_flag==1 ) { // Prohibit
            if ( $content_view_whom==1 ) {  // For All
                // Prohibited
                $data['content'] = $post_access_error_message;
                return $data;
            }
            if ( $content_view_whom==2 ) {
                if ( $lib->is_user_logged_in() ) { // For logged in only (any role)
                    // Prohibited
                    $data['content'] = $post_access_error_message;
                }
                return $data;
            }
        } else {    // Allow
            if ( $content_view_whom==2 ) { // For logged in only (any role)
                if ( !$lib->is_user_logged_in() ) {
                    // Prohibited
                    $data['content'] = $post_access_error_message;
                }
                return $data;
            }
        }
        
        // For selected roles only        
        if ( !$lib->is_user_logged_in() ) { // No role for this site
            if ( $prohibit_allow_flag==1 ) {  // Prohibit
                if ( in_array( 'no_role', $roles ) ) {
                    // Prohibited
                    $data['content'] = $post_access_error_message;
                }
            } elseif ( !in_array( 'no_role', $roles ) ) {
                // Prohibited
                $data['content'] = $post_access_error_message;                    
            }
            return $data;
        }
        
        if ( $prohibit_allow_flag==1 ) {  // Prohibit  
            $result0 = $content;
            $result1 = $post_access_error_message;    // for prohibited access
        } else { // Allow
            $result0 = $post_access_error_message;
            $result1 = $content;     // for allowed access
        }
        
        foreach( $roles as $role ) {
            if ( current_user_can( $role ) ) {
                $data['content'] = $result1;
                return $data;
            }
        }
        
        $data['content'] = $result0;
        
        return $data;
    }
    // end of check_object_permissions()
                
        
    private static function check_terms_level_permissions( $content, $post_id ) {
      
        $data = array('object_restricted'=>false, 'content'=>$content);
        $taxonomies = get_taxonomies( array('public'=>'true', 'show_ui'=>true) );
        $post_terms = wp_get_object_terms( array($post_id), $taxonomies );        
        foreach( $post_terms as $term ) {
            $data = self::check_object_permissions( $content, 'term', $term->term_id );  
            if ( $data['object_restricted'] ) {
                break;
            }
        }
        
        return $data;
    }
    // end of check_terms_level_permissions()    
    
    
    public static function is_object_restricted_for_role($id_to_check, $blocked, $entity) {
        $blocked_list = isset($blocked['data'][$entity]) ? $blocked['data'][$entity] : array();
        if (count($blocked_list)==0) {
            return false;
        }
        
        $restricted = false;
        if ($blocked['access_model']==1) { // Selected
            if (in_array($id_to_check, $blocked_list)) {
                $restricted = true;
            }
        } else {
            if (!in_array($id_to_check, $blocked_list)) {
                $restricted = true;
            }
        }
        
        return $restricted;
    }
    // end of is_object_restricted_for_role()


    public static function is_author_restricted_for_role($author_id, $blocked) {
                
        $blocked_list = isset($blocked['data']['authors']) ? $blocked['data']['authors'] : array();
        if (isset($blocked['data']['own_data_only']) && $blocked['data']['own_data_only']==1) {
            $current_user_id = get_current_user_id();
            if (!in_array($current_user_id, $blocked_list)) {
                $blocked_list[] = $current_user_id;
            }
        }
        if (count($blocked_list)==0) {
            return false;
        }
        
        $restricted = false;
        if ($blocked['access_model']==1) { // Selected
            if (in_array($author_id, $blocked_list)) {
                $restricted = true;
            }
        } else {
            if (!in_array($author_id, $blocked_list)) {
                $restricted = true;
            }
        }
        
        return $restricted;
    }
    // end of is_author_restricted_for_role()    
    
    
    public static function is_term_restricted_for_role($post_id, $blocked) {
        $blocked_terms = isset($blocked['data']['terms']) ? $blocked['data']['terms'] : array();
        if (count($blocked_terms)==0) {
            return false;
        }
        
        $restricted = false;
        $taxonomies = get_taxonomies(array('public'=>'true', 'show_ui'=>true));
        $post_terms = wp_get_object_terms(array($post_id), $taxonomies);
        foreach($post_terms as $term) {
            if (self::is_object_restricted_for_role($term->term_id, $blocked, 'terms')) {
                $restricted = true;
                break;
            }
        }        
        
        return $restricted;
    }
    // end of is_term_restricted_for_role()
    

    public static function is_page_template_restricted_for_role($post_id, $blocked) {
        $blocked_templates = isset($blocked['data']['page_templates']) ? $blocked['data']['page_templates'] : array();
        if (count($blocked_templates)==0) {
            return false;
        }
        
        $restricted = false;
        $post_template = get_post_meta( $post_id, '_wp_page_template', true );
        if (self::is_object_restricted_for_role($post_template, $blocked, 'page_templates')) {
            $restricted = true;
        }
        
        return $restricted;
    }
    // end of is_page_template_restricted_for_role()
        
    
    /**
     * For "Block not selected" access model only!
     * Returns true if post is not included into the list of selected posts (by ID or by terms/categories)
     * 
     * @param int $post_id
     * @param array $blocked
     */
    public static function is_post_restricted_for_role($post_id, $blocked) {

        $lib = URE_Lib_Pro::get_instance();
        $selected_posts = isset($blocked['data']['posts']) ? $blocked['data']['posts'] : array();
        if (isset($blocked['data']['terms']) && count($blocked['data']['terms']) > 0) {
            $post_by_terms = $lib->get_posts_by_terms($blocked['data']['terms']);
            if (count($post_by_terms) > 0) {
                $selected_posts = array_merge($selected_posts, $post_by_terms);
            }
        }
        if (self::is_page_template_restricted_for_role($post_id, $blocked)) {
            $selected_posts[] = $post_id;
        }

        $result = !in_array($post_id, $selected_posts);

        return $result;
    }
    // end of is_post_restricted_for_role()
        

    private static function check_roles_level_permissions( $content, $post_id ) {
    
        $current_user = wp_get_current_user();        
        if ($current_user->ID==0) {
            return $content;
        }        
        $blocked = URE_Content_View_Restrictions_Controller::load_access_data_for_user($current_user);
        if (empty($blocked['data'])) {
            return $content;
        }

        $restricted = false;
        if ($blocked['access_model']==1) {
            if (self::is_object_restricted_for_role($post_id, $blocked, 'posts')) {
                $restricted = true;
            } elseif (self::is_term_restricted_for_role($post_id, $blocked)) {
                $restricted = true;
            } elseif (self::is_page_template_restricted_for_role($post_id, $blocked)) {
                $restricted = true;
            }
        } elseif (self::is_post_restricted_for_role($post_id, $blocked)) {
            $restricted = true;            
        } 
        if ($restricted) {
            $content = self::get_object_access_error_message( 'post', $post_id );
        }
                        
        return $content;
    }
    // end of check_roles_level_permissions()       
           
    
    private static function get_post_from_last_query() {
        global $wpdb;
        
        if ( empty($wpdb->last_query) ) {
            return null;
        }
        
        $post_id = URE_Utils::get_int_after_key( 'WHERE ID=', $wpdb->last_query );                
        if ( empty( $post_id ) ) {
            return null;
        }
        
        $post = get_post( $post_id );
        
        return $post;
    }
    // end of get_post_from_last_query()
    
    
    public function restrict( $content ) {
        global $post;
        
        $post1 = $post; // do not touch global variable, work with its copy
        if ( empty( $post1 ) && !in_the_loop() ) {
            $post1 = self::get_post_from_last_query();
            if ( empty( $post1 ) ) {
                return $content;
            }
        }
        if ( empty( $post1->ID ) ) { 
            return $content;
        }        
        if ( !is_a( $post1, 'WP_Post' ) ) {
            $post1 = get_post( $post1->ID );
            if ( empty( $post1 ) ) {
                return $content;
            }
        }
        
        $restrict_even_if_can_edit = apply_filters( 'ure_restrict_content_view_for_authors_and_editors', false );
        // no restrictions for users who can edit this post
        if ( URE_Content_View_Restrictions_Posts_List::can_edit( $post1 ) && !$restrict_even_if_can_edit ) {
            return $content;
        }
        
        // Check Post level permissions
        $result = self::check_object_permissions( $content, 'post', $post1->ID );
        if ( $result['object_restricted'] ) {
            $content = $result['content'];
        }

        $result = self::check_terms_level_permissions( $content, $post1->ID );
        if ( $result['object_restricted'] ) {
            $content = $result['content'];
        }
                
        $current_user_id = get_current_user_id();
        if ($current_user_id===0) {
            return $content;
        }

        $content = self::check_roles_level_permissions( $content, $post1->ID );

        return $content;
    }
    // end of restrict()
    
        
    /**
     * Returns object with data about view access restrictions applied to the post with ID $post_id or
     * false in case there are not any view access restrictions for this post
     * 
     * @param int $post_id  Post ID
     * @return \stdClass|boolean
     */
    public static function get_post_view_access_users($post_id) {
        global $wpdb;
        
        $ure_content_for_roles = get_post_meta($post_id, URE_Content_View_Restrictions::CONTENT_FOR_ROLES, true);
        if (empty($ure_content_for_roles)) {
            return false;
        }
        $restricted_roles = explode(', ', $ure_content_for_roles);
        if (count($restricted_roles)==0) {
            return false;
        }
        
        $ure_prohibit_allow_flag = get_post_meta($post_id, self::PROHIBIT_ALLOW_FLAG, true);
        $restriction = ($ure_prohibit_allow_flag==1) ? 'prohibited' : 'allowed';

        $id = get_current_blog_id();
        $blog_prefix = $wpdb->get_blog_prefix($id);
        $meta_key = $blog_prefix .'capabilities';
        $query = $wpdb->prepare(
                    "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key=%s",
                    array($meta_key)
                );
        $users = $wpdb->get_results($query);

        $restricted_users = array();
        foreach ($users as $user) {
            $user_roles = maybe_unserialize($user->meta_value);
            if (!is_array($user_roles)) {
                continue;
            }
            foreach (array_keys($user_roles) as $user_role) {
                if (in_array($user_role, $restricted_roles)) {
                    $restricted_users[] = $user->user_id;
                }
            }
        }

        $result = new stdClass();
        $result->restriction = $restriction;  // restriction kind: allowed or prohibited
        $result->roles = $restricted_roles;   // the list of roles ID, for which this content view access restriction is applied 
        $result->users = $restricted_users;   // the list of users ID, for which this content view access restriction is applied 
        
        return $result;
    }
    // end of get_post_view_access_users()
    
    
    public static function is_active_restriction( $prohibit_allow_flag, $content_view_whom, $content_for_roles ) {
        
        if ( !isset( $prohibit_allow_flag ) || !in_array( $prohibit_allow_flag, array(1,2) ) ) {
            return false;
        }
        if ( !isset( $content_view_whom ) || !in_array( $content_view_whom, array(1,2,3) ) ) {
            return false;
        }
        if ( $content_view_whom==3 && empty($content_for_roles) ) {
            return false;
        }
        
        return true;
    }
    // end of is_active_restriction()
    
    
    /**
     * Converts comma separated list of roles to the array with trimmed roles ID inside
     * 
     * @param string $roles_str
     * @return array
     */
    public static function extract_roles_from_string( $roles_str ) {
        
        $roles = explode( ',', $roles_str );
        foreach( $roles as $key=>$role ) {
            $roles[$key] = trim( $role );            
        }
        
        return $roles;
    }
    // end of extract_roles_from_string()
    
            
    /*
     * Return true if current user has at least one role from a list of roles
     * @param array $roles
     * @param array $prohibited
     * @param int $post_id
     */
    public static function current_user_can_role($roles_str) {        
                
        $roles = self::extract_roles_from_string($roles_str);
        if (count($roles)==0) {
            return false;
        }
                
        $lib = URE_Lib_Pro::get_instance();
        $logged_in = $lib->is_user_logged_in();        
        if ($logged_in) {
            $lib = URE_Lib_Pro::get_instance();
            $current_user = wp_get_current_user();
            foreach($roles as $role) {
                if ($lib->user_can_role($current_user, $role)) {
                    return true;
                }
            }
        } else {
            foreach($roles as $role) {
                if ($role=='no_role') {
                    return true;
                }
            }    
        }
        
        return false;
    }
    // end of current_user_can_role()

    
    private static function object_view_prohibited( $prohibit_allow_flag, $content_view_whom, $roles_str ) {
        
        $result = false;
        $lib = URE_Lib_Pro::get_instance();
        if ( $prohibit_allow_flag == 2 ) {  // Allow
            if ($content_view_whom == 1) { // For All
                $result = false;
            } elseif ($content_view_whom == 2) {  //  Allow to logged-in only (any role)
                $result = !$lib->is_user_logged_in();
            } elseif ( $content_view_whom == 3 ) {  // For selected roles only
                $result = !self::current_user_can_role( $roles_str );
            }
        } elseif ($prohibit_allow_flag == 1 ) {  // Prohibit
            if ( $content_view_whom == 1 ) {  // For All
                $result = true;
            } elseif ( $content_view_whom == 2 ) {
                $result = $lib->is_user_logged_in();  //  Allow to logged-in only (any role)
            } elseif ( $content_view_whom == 3 ) {  // For selected roles only
                $result = self::current_user_can_role( $roles_str );
            }
        }

        return $result;
    }
    // end of object_view_prohibited()
    
        
    private static function blocked_at_object_level( $object_type, $object_id ) {
                
        $prohibit_allow_flag = URE_Content_View_Restrictions_Editor::get_object_meta( $object_type, $object_id, self::PROHIBIT_ALLOW_FLAG );
        $content_view_whom = URE_Content_View_Restrictions_Editor::get_object_meta( $object_type, $object_id, self::CONTENT_VIEW_WHOM );
        $roles_str = URE_Content_View_Restrictions_Editor::get_object_meta( $object_type, $object_id, self::CONTENT_FOR_ROLES );
            
        self::$access_error_action = 0;
        self::$access_error_url = '';
        
        if ( !self::is_active_restriction( $prohibit_allow_flag, $content_view_whom, $roles_str ) ) {
            return false;
        }        
        if ( self::object_view_prohibited( $prohibit_allow_flag, $content_view_whom, $roles_str ) ) {
            self::$access_error_action = URE_Content_View_Restrictions_Editor::get_object_meta( $object_type, $object_id, self::POST_ACCESS_ERROR_ACTION );
            self::$access_error_url = URE_Content_View_Restrictions_Editor::get_object_meta( $object_type, $object_id, self::ACCESS_ERROR_URL );
            return true;
        }
        
        
        return false;
    }
    // end of blocked_at_object_level()
    
    
    public static function blocked_at_post_level( $post_id ) {
        
        $result = self::blocked_at_object_level( 'post', $post_id );
        
        return $result;
    }
    // end of blocked_at_post_level()
    
    
    public static function blocked_at_terms_level( $post_id ) {
    
        $taxonomies = get_taxonomies( array('public'=>'true', 'show_ui'=>true) );
        $post_terms = wp_get_object_terms( array($post_id), $taxonomies );        
        foreach( $post_terms as $term ) {
            if ( self::blocked_at_object_level( 'term', $term->term_id ) ) {
                return true;
            }
        }
        
        return false;
    }
    // end of blocked_at_terms_level()
    
    
    private static function blocked_at_roles_level($post_id) {
        
        $restrict_even_if_can_edit = apply_filters('ure_restrict_content_view_for_authors_and_editors', false);        
        // no restrictions for users who can edit this post
        $post = get_post($post_id);
	if (empty($post)) {
            return false;
        }

        if ( URE_Content_View_Restrictions_Posts_List::can_edit($post) && !$restrict_even_if_can_edit ) {
            return false;
        }
                
        $value = self::check_roles_level_permissions(1000, $post_id);
        if ($value==1000) {
            $result = false;
        } else {
            $result = true;
            if ( self::POST_ACCESS_ERROR_ACTION==0 ) {
                $current_user = wp_get_current_user();
                $blocked = URE_Content_View_Restrictions_Controller::load_access_data_for_user( $current_user );
                if ( !empty( $blocked ) ) {
                    self::$access_error_action = $blocked['access_error_action'];
                }
            }
        }
        
        return $result;
    }
    // end of blocked_at_roles_level()
    
    
    public static function current_user_can_view( $post_id ) {
                
        $lib = URE_Lib_Pro::get_instance();
        $activated = $lib->get_option( 'activate_content_for_roles', false );
        if ( !$activated ) {
            return true;
        }
        if ( current_user_can('administrator') ) {
            return true;
        }
        
        // Check post permissions
        if ( self::blocked_at_post_level( $post_id ) ) {
            return false;
        }
        
        // Check post terms permissions
        if ( self::blocked_at_terms_level( $post_id ) ) {
            return false;
        }
        
        // Check roles permissions
        if ( self::blocked_at_roles_level( $post_id ) ) {
            return false;
        }
                     
        return true;
    }
    // end of current_user_can_view()
                
    
    private static function get_page_path_from_url() {

        $url = substr( untrailingslashit( parse_url( $_SERVER['REQUEST_URI'] , PHP_URL_PATH) ), 1);
        $home_url = basename( untrailingslashit( parse_url( home_url(), PHP_URL_PATH ) ) );
        $path = substr( $url, strlen( $home_url ) );
        if (substr( $path, 0, 1)==DIRECTORY_SEPARATOR) {
            $path = substr( $path, 1);
        }        
        
        return $path;
    }
    // end of get_page_path_from_url()
    
    
    private static function get_redirect_url() {
        
        //  self::access_error_url contains value from object ( post/term/role ) for which blocking restriction was found
        $url =  self::$access_error_url;
        if ( empty( $url ) ) {
            $lib = URE_Lib_Pro::get_instance();
            $url = $lib->get_option( 'content_view_access_error_url', '' );
            if ( empty( $url ) ) {
                // Redirect to current URL after login
                $url = wp_login_url( $lib->get_current_url(), true );
            }
        }           
        
        return $url;
    }
    // end of get_redirect_url()
    
    
    public function redirect() {        
        global $post;
                                 
        if ( empty( $post ) || empty( $post->ID ) ) {
            $page_path = self::get_page_path_from_url();
            if ( empty( $page_path ) ) {
                $object_id = get_option( 'page_on_front' );   //  Take Front/Home page ID
            } else {  
                $post_types = apply_filters( 'ure_view_redirect_get_page_by_path_post_types', array('post', 'page', 'attachment') );
                $page = get_page_by_path( $page_path, OBJECT, $post_types );
                if ( empty( $page ) || empty( $page->ID ) ) {
                    return;
                }
                $object_id = $page->ID;
            }
        } else {
            $object_id = $post->ID;
        }       
        if ( self::current_user_can_view( $object_id ) ) {
            return;
        }
        
        if ( self::$access_error_action!=4 ) {   // redirect                
            return;
        }
        
        $url = self::get_redirect_url();
        if ( headers_sent() ) {
?>
<script>
    document.location.href = '<?php echo $url; ?>';
</script>    
<?php
        } else {
            wp_redirect( $url );
        }
        die;
        
    }
    // end of redirect()
            
    /**
     * BuddyPress uses its own router for pages which are linked to its components.
     * We have to switch this router off in order to apply content view restrictions to these pages
     */
    private function fix_buddypress_router() {
        
        if (!class_exists('BuddyPress')) {
            return;
        }
        
        $bp_pages = bp_core_get_directory_page_ids('all');
        // Activity
        if (!empty($bp_pages['activity']) && !$this->current_user_can_view($bp_pages['activity'])) {
            remove_action('bp_screens', 'bp_activity_screen_index', 10);
        }
        
        // Groups
        if (!empty($bp_pages['groups']) && !$this->current_user_can_view($bp_pages['groups'])) {
            remove_action('bp_screens', 'groups_directory_groups_setup', 2);
        }
        
        // Members
        if (!empty($bp_pages['members']) && !$this->current_user_can_view($bp_pages['members'])) {
            remove_action('bp_screens', 'bp_members_screen_index', 10);
        }
    }
    // end of fix_buddypress_router()
    
    
    private function plugins_compatibility() {
        
        if (is_admin()) {
            return;
        }
                
        $this->fix_buddypress_router();        
        
    }
    // end of plugins_compatibility()
    
}
// end of URE_Content_View_Restrictions class
