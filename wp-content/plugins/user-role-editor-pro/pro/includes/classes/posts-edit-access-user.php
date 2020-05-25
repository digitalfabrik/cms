<?php
/*
 * Class: Access restrictions to posts/pages for user
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: vladimir@shinephp.com
 * 
 */

class URE_Posts_Edit_Access_User {
    
    const CONTENT_EDIT_ACCESS = 'ure_content_edit_access';
    const TRANSIENT_EXPIRE = 15;
    private $lib = null;    // URE_Lib_Pro class instance
    private $pea = null;    // URE_Posts_Edit_Access class instance
    private $user_meta = null;    // user meta keys
    private $attachments_list = null;       
    private $roles_data = null;
    
    
    public function __construct(URE_Posts_Edit_Access $pea) {
        
        $this->lib = URE_Lib_Pro::get_instance();  
        $this->pea = $pea;
        $this->user_meta = new URE_Posts_Edit_Access_User_Meta();
                
        add_action('edit_user_profile', array($this, 'show_profile'), 10, 2);
        add_action('profile_update', array($this, 'save_user_restrictions'), 10);                
        
    }
    // end of __construct()
        
    
    /**
     * Returns true if user can edit posts or page or any custom post type
     * 
     * @param WP_User $user
     * @return boolean
     */
    private function can_edit_content($user) {
            
        $caps = $this->lib->get_edit_custom_post_type_caps();
        $min_cap = $this->lib->user_can_which($user, $caps);
        if (empty($min_cap)) {
            return false;
        }
        
        return true;
    }
    // end of can_edit_content()
    
    
    public function is_restriction_applicable() {
        
        $show_full_list = apply_filters('ure_posts_show_full_list', false);
        if ($show_full_list) { // show full list of post/pages/custom post types
            return false;
        }
        
        $current_user = wp_get_current_user();        
        // do not restrict administrators
        if ( $this->lib->user_is_admin($current_user->ID) ) {
            return false;
        }                                
    
        // do not restrict users without edit posts/pages/custom post types capabilities
        if (!$this->can_edit_content($current_user)) {
            return false;
        }
        
        // do not apply restrictions if the posts/pages edit restriction is not set for this user
        if (!$this->is_restricted()) {
            return false;
        }
  
        return true;
    }
    // end of is_restriction_applicable()
    
    
    public function show_profile($user) {

        $result = stripos($_SERVER['REQUEST_URI'], 'network/user-edit.php');
        if ($result !== false) {  // exit, this code just for single site user profile only, not for network admin center
            return;
        }
        
        // do not restrict administrators
        if ( $this->lib->user_is_admin($user->ID) ) {
            return false;
        }
        
        if (!$this->can_edit_content($user)) {
            return false;
        }
        
        if (!current_user_can('ure_edit_posts_access')) {
            return;
        }

        $caps = $this->lib->get_edit_custom_post_type_caps();
        if ( !$this->lib->user_can_which( $user, $caps) ) {        
            return;
        }
        
        $restriction_type = $this->user_meta->get_restriction_type($user->ID); 
        if (empty($restriction_type)) {
            $restriction_type = 0;  // No restrictions ?!
        }
        $own_data_only = $this->user_meta->get_own_data_only($user->ID);        
        // by post ID
        $posts_list = $this->user_meta->get_posts_list($user->ID);        
        // be category/taxonomy
        $categories_list = $this->user_meta->get_post_categories_list($user->ID);        
        // by post author
        $show_authors = $this->lib->user_can_which( $user, array('edit_others_posts', 'edit_others_pages') );
        if ($show_authors) {
            $post_authors_list = $this->user_meta->get_post_authors_list($user->ID);
        } else {
            $post_authors_list = '';
        }
        $user_profile = true;
        
        $args = compact(array(
            'restriction_type', 
            'own_data_only',
            'posts_list', 
            'categories_list', 
            'post_authors_list',
            'show_authors',
            'user_profile'));
        $html = URE_Posts_Edit_Access_View::get_html($args);
        echo $html;
        
    }
    // end of show_profile()    
 
    
    // update posts edit by post ID restriction: comma separated posts IDs list
    private function update_posts_list($user_id) {
        
        if (!empty($_POST['ure_posts_list'])) {
            $posts_list = explode(',', trim($_POST['ure_posts_list']));
            if (count($posts_list)>0) {
                $posts_list_str = URE_Utils::filter_int_array_to_str($posts_list);
                $this->user_meta->set_posts_list($user_id, $posts_list_str);
            }            
        } else {
            $this->user_meta->delete_posts_list($user_id);
        }        
        
    }
    // end of update_posts_list()
    
    
    // update comma separated categories/taxonomies ID list 
    private function update_categories_list($user_id) {
        
        if (!empty($_POST['ure_categories_list'])) {
            $categories_list = explode(',', trim($_POST['ure_categories_list']));
            if (count($categories_list)>0) {
                $categories_list_str = URE_Utils::filter_int_array_to_str($categories_list);
                $this->user_meta->set_post_categories_list($user_id, $categories_list_str);                                
            }            
        } else {
            $this->user_meta->delete_post_categories_list($user_id);
        }
        
    }
    // end of update_categories_list()    
    
    
    // update posts edit by author ID restriction: comma separated authors IDs list
    private function update_authors_list($user_id) {
        
        if (!empty($_POST['ure_post_authors_list'])) {
            $authors_list = explode(',', trim($_POST['ure_post_authors_list']));
            if (count($authors_list)>0) {
                $post_authors_list_str = URE_Utils::filter_int_array_to_str($authors_list);
                $this->user_meta->set_post_authors_list($user_id, $post_authors_list_str);
            }            
        } else {
            $this->user_meta->delete_post_authors_list($user_id);
        }
        
    }
    // end of update_authors_list()
    
    
    // save posts edit restrictions when user profile page is updated, as WordPress itself doesn't know about it
    public function save_user_restrictions($user_id) {
        
        if (!isset($_POST['ure_posts_list'])) {
            // 'profile_update' action was fired not from the 'user profile' page, so there is no data to update
            return;
        }
        
        if (!current_user_can('edit_users', $user_id) || !current_user_can('ure_edit_posts_access')) {
            // not enough permissions for this action
            return;
        }
        
        $restriction_type = $_POST['ure_posts_restriction_type'];
        if ($restriction_type!=1 && $restriction_type!=2 && $restriction_type!=0) {  // sanitize user input
            $restriction_type = 0;
        }
        $this->user_meta->set_restriction_type($user_id, $restriction_type);
        
        $own_data_only = $this->lib->get_request_var('ure_own_data_only', 'post', 'checkbox');
        $this->user_meta->set_own_data_only($user_id, $own_data_only);        
        
        $this->update_posts_list($user_id);
        $this->update_categories_list($user_id);
        $this->update_authors_list($user_id);                                                                
        
        do_action('ure_save_user_edit_content_restrictions', $user_id);
        
    }
    // end of save_user_restrictions()    


    private function get_data_from_user_roles($user_id) {
                
        if ($this->roles_data===null) {
            $this->roles_data = array();
        }
        if (isset($this->roles_data[$user_id])) {
            return $this->roles_data[$user_id];
        }
        
        $this->roles_data[$user_id] = array();
        $user = $this->lib->get_user($user_id);        
        if (empty($user->roles)) {
            return array();
        }
        
        $access_data = get_option(URE_Posts_Edit_Access_Role::ACCESS_DATA_KEY);
        if (!is_array($access_data)) {
            return array();
        }        
                
        foreach(array_values($user->roles) as $role_id) {
            if (array_key_exists($role_id, $access_data)) {
                $this->roles_data[$user_id][] = $access_data[$role_id];
            }
        }
        
        return $this->roles_data[$user_id];
    }
    // end of get_data_from_user_roles()

    
    public function delete_transient() {
        
        $user_id = get_current_user_id();        
        if ( $user_id===0 ) {
            return;
        }
        
        $key = self::CONTENT_EDIT_ACCESS .'-'. $user_id;
        delete_transient( $key );
    }
    // end of delete_transient()
    
    
    private function get_transient($item_id) {
        
        $user_id = get_current_user_id();
        if ( $user_id===0 ) {
            return null;
        }
        
        $key = self::CONTENT_EDIT_ACCESS .'-'. $user_id;
        $data = get_transient( $key );
        if (!empty($data) && is_array($data) && isset($data[$item_id])) {
            $value = $data[$item_id];
        } else {
            $value = null;
        }
        
        return $value;
    }
    // end of get_transient()
    
    
    private function set_transient($item_id, $item_value) {
        
        $user_id = get_current_user_id();
        if ( $user_id===0 ) {
            return;
        }
        
        $key = self::CONTENT_EDIT_ACCESS .'-'. $user_id;
        $data = get_transient( $key );
        if ( !is_array($data) ) {
            $data = array();
        }
        if ( !is_array( $data ) ) {
            $data = array();
        }
        
        $data[$item_id] = $item_value;
        set_transient($key, $data, self::TRANSIENT_EXPIRE);
        
    }
    // end of set_transient()


    private function get_restriction_type_from_roles() {
        
        $user = wp_get_current_user();
        if (empty($user->roles)) {
            return false;   
        }
        
        $data = $this->get_data_from_user_roles($user->ID);
        if (empty($data)) {
            return false;
        }

        $value = $data[0]['restriction_type'];
        
        return $value;
    }
    // end of get_restriction_type_from_roles()
    
    
    public function get_restriction_type() {        
        
        $user_id = get_current_user_id();
        if ( $user_id===0 ) {
            return false;
        }        
        $value = $this->get_transient( 'restriction_type' );
        if ( $value!==null ) {        
            return $value;
        }        
        
        // get from user meta
        $value = $this->user_meta->get_restriction_type( $user_id );
        if (empty($value)) {
            $value = $this->get_restriction_type_from_roles();            
            if ( empty($value) ) {
               $value = 1; // Allow by default
            }
        }    
        $value = apply_filters( 'ure_edit_posts_access_restriction_type', $value );
        
        $this->set_transient( 'restriction_type', $value );
        
        return $value;
    }
    // end of get_restriction_type()


    private function get_own_data_only_from_roles() {
        
        $current_user = wp_get_current_user();        
        if (empty($current_user->roles)) {
            return false;   
        }
        
        $data = $this->get_data_from_user_roles($current_user->ID);        
        if (empty($data)) {
            return false;
        }

        $value = false;
        foreach($data as $role_data) {
            $value = isset($role_data['own_data_only']) ? $role_data['own_data_only'] : false;
            if ($value) {
                break;
            }
        }
        
        return $value;
    }
    // end of get_own_data_only_from_roles()
    
                            
    /**
     * Concat restricted data for restriction type set for current user for all user's roles
     * @param string $item_id : posts, terms, authors
     * @return string
     */
    private function get_restricted_items_from_roles($item_id) {
        
        $current_user = wp_get_current_user();
        if (empty($current_user->roles)) {
            return '';
        }
        
        $data = $this->get_data_from_user_roles($current_user->ID);        
        if (empty($data)) {
            return '';
        }
        
        $restriction_type = $this->get_restriction_type();        
        $items = array();
        foreach($data as $item) {
            if ($item['restriction_type']==$restriction_type && !empty($item['data'][$item_id])) {
                $items[] = $item['data'][$item_id];
            }
        }
        
        $data1 = array();
        foreach($items as $item) {              
            $data0 = URE_Utils::filter_int_array($item);
            $data1 = array_merge($data1, $data0);
        }
        
        $value1 = array_unique($data1);
        $value = implode(',', $value1);
        
        return $value;
    }
    // enf of get_restricted_items_from_roles()
    

    public function get_post_categories_list() {
                        
        $value = $this->get_transient( 'terms' );
        if ( $value!==null ) {
            return $value;
        }
        
        $user_id = get_current_user_id();
        $value = $this->user_meta->get_post_categories_list( $user_id );
        $value1 = $this->get_restricted_items_from_roles( 'terms' );
        $value = URE_Utils::concat_with_comma( $value, $value1 );  
        
        $value = URE_Utils::validate_int_values_unique( $value );
        $value = apply_filters( 'ure_post_edit_access_terms_list', $value );
        $this->set_transient( 'terms', $value );
                
        return $value;
    }
    // end of get_post_categories_list()
        
    
    public function get_post_authors_list() {
                
        $value = $this->get_transient( 'authors' );
        if ( $value!==null ) {
            return $value;
        }
        
        $user_id = get_current_user_id();
        $value = $this->user_meta->get_post_authors_list( $user_id );
        $value1 = $this->get_restricted_items_from_roles( 'authors' );
        $value = URE_Utils::concat_with_comma( $value, $value1 );
        
        $own_data_only = $this->user_meta->get_own_data_only( $user_id );
        $value2 = null;
        if ($own_data_only) {
            $value2 = $user_id;
        } else {
            $own_data_only = $this->get_own_data_only_from_roles();
            if ($own_data_only) {
                $value2 = $user_id;
            }
        }        
        $value = URE_Utils::concat_with_comma( $value, $value2 );
        $value = apply_filters( 'ure_post_edit_access_authors_list', $value );
        $value = URE_Utils::validate_int_values_unique( $value );
        
        $this->set_transient( 'authors', $value );
        
        return $value;
    }
    // end of get_post_authors_list()
    
    
    private function get_posts_list_by_ids() {
                
        $value = $this->get_transient( 'posts' );
        if ( $value!==null ) {
            return $value;
        }                
        
        $user_id = get_current_user_id();
        $value = $this->user_meta->get_posts_list( $user_id );
        if ( !is_string( $value ) ) {
            $value = '';
        }
        $value1 = $this->get_restricted_items_from_roles( 'posts' );
        $value = URE_Utils::concat_with_comma( $value, $value1 );
        $value = URE_Utils::validate_int_values_unique( $value );
        $value = apply_filters( 'ure_edit_posts_access_id_list', $value );
        
        $this->set_transient( 'posts', $value );
        
        return $value;
    }
    // end of get_posts_list_by_ids()

    
    private function add_wc_orders_by_product_owner($posts) {
        global $wpdb;
        
        $order_items = $wpdb->prefix .'woocommerce_order_items';
        $order_itemmeta = $wpdb->prefix .'woocommerce_order_itemmeta';
        $orders = array();
        foreach($posts as $post) {
            if ($post->post_type==='product') {
                $query = $wpdb->prepare(
                            "SELECT order_id FROM {$order_items} 
                                WHERE order_item_id IN (
                                    SELECT order_item_id FROM {$order_itemmeta} 
                                        WHERE meta_key='_product_id' and meta_value=%d)",
                            array($post->ID)
                                        );
                $list = $wpdb->get_col($query);
                foreach($list as $order_id) {
                    $item = new stdClass();
                    $item->ID = $order_id;
                    $item->post_type = 'shop_order';
                    $orders[] = $item;
                }
            }
        }        
        $posts = array_merge($posts, $orders);
        
        return $posts;
    }
    // end of add_wc_orders_by_product_owner()
    
    
    private function add_wc_orders_by_customer( $posts ) {
        global $wpdb;
        
        $postmeta_table = $wpdb->prefix .'postmeta';        
        $orders = array();
        $current_user = wp_get_current_user();
        
        $query = $wpdb->prepare(
                    "SELECT post_id FROM {$postmeta_table} 
                                WHERE meta_key='_customer_user' and meta_value=%d",
                    array($current_user->ID)
                                );
        $list = $wpdb->get_col($query);
        foreach($list as $order_id) {
            $item = new stdClass();
            $item->ID = $order_id;
            $item->post_type = 'shop_order';
            $orders[] = $item;
        }
                
        $posts = array_merge($posts, $orders);
        
        return $posts;
    }
    // end of add_wc_orders_by_customer()
    
    
    private function get_posts_list_by_authors() {        
        global $wpdb;
                
        $post_authors_list = $this->get_post_authors_list();
        if (empty($post_authors_list)) {
            return array();
        }
        $restriction_type = $this->get_restriction_type();
        if ($restriction_type==1) {   // allow
            $current_user_id = get_current_user_id();
            $authors = explode(',', $post_authors_list);
            if (!in_array($current_user_id, $authors)) {
                // add user himself to the authors list to allow him edit his own posts/pages
                $post_authors_list .= ', '. $current_user_id;
            }
        }        
        $query = "select ID, post_type
                    from {$wpdb->posts}
                    where post_author in ($post_authors_list) and post_type!='revision'";
        $posts = $wpdb->get_results($query);
        if (!is_array($posts)) {
            $posts = array();
        }
        
        if (URE_Plugin_Presence::is_active('woocommerce')) {
            // Add WooCommerce orders linked to the products by theirs authors/owners
            if ( !empty( $posts ) ) {
                $add_orders_by_product_owner = apply_filters( 'ure_edit_posts_access_add_orders_by_product_owner', true );
                if ($add_orders_by_product_owner) {
                    $posts = $this->add_wc_orders_by_product_owner( $posts );
                }
            }
            
            // Add orders where current user is a customer
            // Useful for case when logged in user (not admin) is allowed to edit his own orders
            $add_orders_by_customer = apply_filters( 'ure_edit_posts_access_add_orders_by_customer', false );
            if ( $add_orders_by_customer ) {
                $posts = $this->add_wc_orders_by_customer( $posts );
            }
        }
        
        
        $posts = URE_WC_Bookings::add_related_wc_bookings( $posts );
        
        
        $post_ids = array();
        foreach($posts as $post) {
            $post_ids[] = $post->ID;
        }
        $post_ids = array_unique($post_ids);
        
        return $post_ids;
    }
    // end of get_posts_list_by_authors()
            
    
    private function get_posts_list_by_categories() {
                
        $categories_list_str = $this->get_post_categories_list();
        if (empty($categories_list_str)) {
            return array();
        }
        
        $post_ids = $this->lib->get_posts_by_terms($categories_list_str);
        
        return $post_ids;
    }
    // end of get_posts_list_by_categories()
                            

    /**
     * Get page children
     * @param int $page_id
     * @param array $all_pages ('id=>parent')
     * @param array full list of page children ID
     */
    private function get_page_children($page_id, $all_pages, &$children) {
                
        foreach($all_pages as $child_id=>$parent_id) {
            if ($parent_id!=$page_id) {
                continue;
            }
            $children[] = $child_id;
            $this->get_page_children($child_id, $all_pages, $children);            
        }
        
    }
    // end of get_page_children()
    
    
    /**
     * Add child pages to the posts/pages list
     */
    private function add_child_pages($posts_list) {
            
        $auto_access = apply_filters('ure_auto_access_child_pages', true);
        if (empty($auto_access)) {
            return $posts_list;
        }
        
        // remove filter temporally to exclude recursion
        remove_filter('map_meta_cap', array($this->pea, 'block_edit_post'), 10);
        $args = array(
            'post_type' => 'page', 
            'fields' => 'id=>parent',
            'nopaging' => true,
            'suppress_filters'=>true);
        $wp_query = new WP_Query();        
        $all_pages = $wp_query->query($args);
        // restore filter back
        add_filter('map_meta_cap', array($this->pea, 'block_edit_post'), 10, 4);
        
        $children = array();
        foreach($posts_list as $post_id) {
            $post = get_post($post_id);
            if (empty($post) || $post->post_type!=='page') {
                continue;
            }
            
            $page_children = array();
            $this->get_page_children($post_id, $all_pages, $page_children);
            if (count($page_children)>0) {
                $children = array_merge($children, $page_children);
            }                                    
        }   // foreach(...)
        if (count($children)>0) {
            $posts_list = array_merge($posts_list, $children);
        }
        
        return $posts_list;
    }
    // end of add_child_pages()    

    
    private function exclude_posts_which_can_not_edit($posts_list) {        
                
        $post_types = $this->lib->_get_post_types();        
        $list = array();
        if (count($posts_list)==0) {
            return $list;
        }
        
        // Exclude recursion
        remove_filter('map_meta_cap', array($this->pea, 'block_edit_post'), 10);
        
        $current_user_id = get_current_user_id();
        for ($i=0; $i<count($posts_list); $i++) {
            $post = get_post($posts_list[$i]);
            if (empty($post)) {
                continue;
            }            
            // can not check if user can edit this post_type if post_type was not registered yet, so assume that he can in this case
            // example - WooCommerce products
            if (!isset($post_types[$post->post_type]) || 
                user_can($current_user_id, 'edit_post', $posts_list[$i])) {
                $list[] = $posts_list[$i];
            }
        }
        
        // Restore filter we removed earlier 
        add_filter('map_meta_cap', array($this->pea, 'block_edit_post'), 10, 4);
        
        return $list;
    }
    // end of exclude_posts_which_can_not_edit()

    
    public function get_posts_list() {

        $user_id = get_current_user_id();
        if ($user_id===0) {
            return array();
        }
        
        $value = $this->get_transient( 'posts_list' );        
        if ( $value!==null ) {
            return $value;
        }
                           
        $posts_list_str = $this->get_posts_list_by_ids();        
        $posts_list1 = URE_Utils::filter_int_array_from_str( $posts_list_str );
        $posts_list2 = $this->get_posts_list_by_categories();
        $posts_list3 = $this->get_posts_list_by_authors();
        $posts_list = array_values( array_unique( array_merge( $posts_list1, $posts_list2, $posts_list3 ) ) );
        if ( count( $posts_list )===0 ) {
            $this->set_transient( 'posts_list', $posts_list );
            return $posts_list;
        }                
        
        $posts_list = URE_Utils::filter_int_array( $posts_list );        
        $posts_list = $this->add_child_pages( $posts_list );
        
        // apply custom filter for resulting posts ID list
        $posts_list = apply_filters( 'ure_edit_access_posts_list', $posts_list );
                
        $restriction_type = $this->get_restriction_type();
        if ( $restriction_type==1 ) { // for 'Allow' only, do not exclude anything if it's a blocked/prohibited list
            $show_posts_which_can_edit_only = apply_filters( 'ure_show_posts_which_can_edit_only_backend',  true );
            if ( $show_posts_which_can_edit_only ) {
                $posts_list = $this->exclude_posts_which_can_not_edit( $posts_list );
            }
        }
        $this->set_transient( 'posts_list', $posts_list );
        
        return $posts_list;
    }
    // end of get_posts_list()
    
   
    /**
     * Returns true in case edit restrictions are set for this user
     * It's used in case posts list available is empty
     * 
     */
    public function is_restricted() {
                
        $restricted = $this->get_transient( 'restricted' );
        if ( $restricted!==null ) {
            return $restricted;
        }        
        
        $posts_list_str = $this->get_posts_list_by_ids();
        $categories_list_str = $this->get_post_categories_list();
        $post_authors_list_str = $this->get_post_authors_list();
        $restrictions = trim($posts_list_str . $categories_list_str . $post_authors_list_str);
        $restricted = empty($restrictions) ? false : true;
        $this->set_transient('restricted', $restricted);
        
        return $restricted;
    }
    // end of is_restricted()
    
    
    public function get_attachments_list() {
        
        global $wpdb;
    
        if ($this->attachments_list!=null) {
            return $this->attachments_list;
        }
                   
        $posts_list = $this->get_posts_list();
        if (!is_array($posts_list) || count($posts_list)==0) {
            $this->attachments_list = array();
            return $this->attachments_list;
        }                 
        
        $restriction_type = $this->get_restriction_type();
        $parents_list = URE_Base_Lib::esc_sql_in_list('int', $posts_list);
        $query = "SELECT ID FROM {$wpdb->posts} 
                    WHERE post_type='attachment' AND (post_parent in ($parents_list)"; 
        if ($restriction_type==1) {   // Allow
            $current_user_id = get_current_user_id();
            $query .= $wpdb->prepare(" OR (post_parent=0 AND post_author=%d)", array($current_user_id));
        }
        $query .= ')';                                
        $this->attachments_list = $wpdb->get_col($query); 
        if (!is_array($this->attachments_list)) {
            $this->attachments_list = array();
        }            
        
        return $this->attachments_list;
    }
    // end of get_attachments_list()

    
}
// end of URE_Posts_Edit_Access_User class