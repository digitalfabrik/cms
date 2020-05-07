<?php

/*
 * User Role Editor WordPress plugin
 * Class URE_Metaboxes - support stuff for Meta boxes Access add-on
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Meta_Boxes { 
    
    const META_BOXES_LIST_COPY_KEY = 'ure_meta_boxes_list_copy';
    const ACCESS_DATA_KEY = 'ure_meta_boxes_access_data';

    private $lib = null;    
    private $blocked = null;
    private static $meta_boxes_list = null;
    
    
    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        
        add_action('do_meta_boxes', array($this, 'update_meta_boxes_list_copy'), 1);
        $this->hook_to_admin_head();
        if (class_exists('BuddyPress')) {
            // the same for the BuddyPress plugin meta boxes placed at the User Profile Extended tab
            add_action('bp_members_admin_user_metaboxes', array($this, 'update_meta_boxes_list_copy'), 90);
            add_action('bp_members_admin_user_metaboxes', array($this, 'remove_blocked_metaboxes'), 99);
        }
        add_action('add_meta_boxes', array($this, 'hook_to_add_meta_boxes_post_type'), 99, 1);
        add_action('wp_dashboard_setup', array($this, 'remove_blocked_metaboxes'), 99);
        add_action('wp_user_dashboard_setup', array($this, 'remove_blocked_metaboxes'), 99);
        add_action( 'admin_enqueue_scripts', array($this, 'block_gutenberg_components'), 99 );
        
    }
    // end of __construct()

    
    public function hook_to_add_meta_boxes_post_type($post_type) {
        
        add_action('add_meta_boxes_'. $post_type, array($this, 'remove_blocked_metaboxes'), 99);
                
    }
    // end of hook_to_add_meta_boxes_post_type()
    
    
    /*
     * Catch Advanced Custom Fields and WPML plugin meta boxes, as they add them using 'admin_head' instead of 'add_meta_boxes'
     * core/controllers/post.php, core/controllers/input.php
     * 
     */ 
    private function hook_to_admin_head() {
        $acf_exists = class_exists('acf_controller_post');
        $wpml_exists = class_exists('SitePress');
        if ($acf_exists || $wpml_exists) {            
            add_action('admin_head', array($this, 'update_meta_boxes_list_copy'), 90);
            if ($acf_exists) {
                add_filter('acf/get_field_groups', array($this, 'remove_blocked_acf_meta_boxes'), 90);
            }
            if ($wpml_exists) {
                add_action('admin_head', array($this, 'remove_blocked_metaboxes'), 99);
            }
        }
    }
    // end of hook_to_admin_head()
    
    
        /**
     * Load widgets access data for role
     * @param string $role_id
     * @return array
     */
    public static function load_data_for_role($role_id) {
        
        $access_data = get_option(self::ACCESS_DATA_KEY);
        if (is_array($access_data) && array_key_exists($role_id, $access_data)) {
            $result =  $access_data[$role_id];
        } else {
            $result = array();
        }
        
        return $result;
    }
    // end of load_data_for_role()
    
    
    public function load_access_data_for_user($user) {
    
        if (is_object($user)) {
            $id = $user->ID;
        } else if (is_int($user)) {
            $id = $user;
            $user = get_user_by('id', $user);
        } else {
            $user = get_user_by('login', $user);
            $id = $user->ID;
        }
        
        $blocked = get_user_meta($user->ID, self::ACCESS_DATA_KEY, true);
        if (!is_array($blocked)) {
            $blocked = array();
        }
        
        $access_data = get_option(self::ACCESS_DATA_KEY);
        if (empty($access_data)) {
            $access_data = array();
        }
        
        foreach ($user->roles as $role) {
            if (isset($access_data[$role])) {
                $blocked = array_merge($blocked, $access_data[$role]);
            }
        }
        
        $blocked = array_unique ($blocked);
        
        return $blocked;
    }
    // end of load_access_data_for_user()

    
    protected function get_access_data_from_post() {
        
        $keys_to_skip = array('action', 'ure_nonce', '_wp_http_referer', 'ure_object_type', 'ure_object_name', 'user_role');
        $access_data = array();
        foreach ($_POST as $key=>$value) {
            if (in_array($key, $keys_to_skip)) {
                continue;
            }
            $access_data[] = $key;
        }
        
        return $access_data;
    }
    // end of get_access_data_from_post()
        
    
    public function save_access_data_for_role($role_id) {
        $access_for_role = $this->get_access_data_from_post();
        $access_data = get_option(self::ACCESS_DATA_KEY);        
        if (!is_array($access_data)) {
            $access_data = array();
        }
        if (count($access_for_role)>0) {
            $access_data[$role_id] = $access_for_role;
        } else {
            unset($access_data[$role_id]);
        }
        update_option(self::ACCESS_DATA_KEY, $access_data);
    }
    // end of save_access_data_for_role()
    
    
    public function save_access_data_for_user($user_login) {
        //$access_for_user = $this->get_access_data_from_post();
        // TODO ...
    }
    // end of save_menu_access_data_for_role()   
                    
    
    protected function get_allowed_roles($user) {
        $allowed_roles = array();
        if (empty($user)) {   // request for Role Editor - work with currently selected role
            $current_role = filter_input(INPUT_POST, 'current_role', FILTER_SANITIZE_STRING);
            $allowed_roles[] = $current_role;
        } else {    // request from user capabilities editor - work with that user roles
            $allowed_roles = $user->roles;
        }
        
        return $allowed_roles;
    }
    // end of get_allowed_roles()
    
    
    public function is_restriction_aplicable() {
        
        $multisite = $this->lib->get('multisite');
        if ($multisite && is_super_admin()) {
            return false;
        }
        
        if (!$multisite && current_user_can('administrator')) {
            return false;
        }

        return true;
    }
    // end of is_restriction_aplicable()
    
    
    public function get_blocked_items() {
                
        if (is_user_logged_in()) {
            if ($this->blocked===null) {
                $current_user = wp_get_current_user();
                $this->blocked = $this->load_access_data_for_user($current_user);
            }
        } else {
            $this->blocked = array();
        }
        
        return $this->blocked;
    }
    // end of get_blocked_items()

    
    public function update_meta_boxes_list_copy() {
        global $wp_meta_boxes;
        
        if (empty($wp_meta_boxes)) { 
            return;
        }
         
        self::$meta_boxes_list = get_option(self::META_BOXES_LIST_COPY_KEY, array());
        foreach($wp_meta_boxes as $screen=>$contexts) {            
            foreach($contexts as $context=>$priorities) {
                foreach($priorities as $priority=>$meta_boxes) {
                    foreach($meta_boxes as $meta_box) {
                        if (empty($meta_box) || !isset($meta_box['id']) || !isset($meta_box['title'])) {
                            continue;
                        }
                        $mb = new StdClass();
                        $mb->id = $meta_box['id'];
                        $mb->title = $meta_box['title'];
                        $mb->screen = $screen;
                        $mb->context = $context;
                        $mb->priority = $priority;
                        $mb_hash = md5($mb->id . $mb->screen . $mb->context);
                        self::$meta_boxes_list[$mb_hash] = $mb;
                    }
                }
            }                        
        }
        
        update_option(self::META_BOXES_LIST_COPY_KEY, self::$meta_boxes_list, false);
        
    }
    // end of update_meta_boxes_list_copy()
    
    
    public function remove_blocked_metaboxes() {
        
        if (!$this->is_restriction_aplicable()) {
            return;
        }
        
        $this->get_blocked_items();
        if (empty($this->blocked)) {
            return;
        }
                        
        $all_meta_boxes = $this->get_all_meta_boxes();
        foreach($this->blocked as $mb_hash) {
            if (!isset($all_meta_boxes[$mb_hash])) {
                continue;
            }
            $blocked_mb = $all_meta_boxes[$mb_hash];
            remove_meta_box($blocked_mb->id, $blocked_mb->screen, $blocked_mb->context);
        }
        
    }
    // end of remove_blocked_metaboxes()

    
    private function is_gutenberg_page( $hook ) {
        if ( $hook!=='post.php' ) {
            // It's not a post editor page
            return false;
        }                                
        if ( !method_exists( 'WP_Screen', 'is_block_editor' ) ) { 
            // Gutenberg is not available
            return false;
        }
        $screen = get_current_screen();
        if ( !$screen->is_block_editor() ) {
            // Gutenberg is not active
            return false;
        }
        
        return true;
    }
    // end of is_gutenberg_page()
    
    
    private function get_gutenberg_components_former_meta_boxes() {
    
        $data = array(
            'categorydiv'=>'taxonomy-panel-category',
            'commentstatusdiv'=>'discussion-panel',
            'postexcerpt'=>'post-excerpt',  
            'postimagediv'=>'featured-image',
            'tagsdiv-post_tag'=>'taxonomy-panel-post_tag',  
            'slugdiv'=>'post-link',
            'pageparentdiv'=>'page-attributes'
        );
        
        return $data;        
    }
    // end of get_gutenberg_components_former_meta_boxes()


    private function get_blocked_gutenberg_components() {
        
        $screen = get_current_screen();
        $mbs_2_gbc = $this->get_gutenberg_components_former_meta_boxes();        
        $all_meta_boxes = $this->get_all_meta_boxes();
        $blocked_gbc = array();
        foreach( $this->blocked as $mb_hash ) {
            if ( !isset( $all_meta_boxes[$mb_hash] ) ) {
                continue;
            }
            $mb = $all_meta_boxes[$mb_hash];
            if ($mb->screen!==$screen->id) {
                continue;
            }
            if ( isset( $mbs_2_gbc[$mb->id] ) ) {
                $blocked_gbc[] = $mbs_2_gbc[$mb->id];
            }
        }        
        
        return $blocked_gbc;
    }
    // end of get_blocked_gutenberg_components()
    
    public function block_gutenberg_components( $hook ) {
        
        if ( !$this->is_gutenberg_page( $hook ) ) {
            return;
        }        
        if (!$this->is_restriction_aplicable()) {
            return;
        }                                
        $this->get_blocked_items();
        if (empty($this->blocked)) {
            return;
        }
        
        $blocked_gbc = $this->get_blocked_gutenberg_components();        
        if ( empty( $blocked_gbc ) ) {
            return;
        }
        
        wp_register_script( 'ure-gutenberg', plugins_url( '/pro/js/gutenberg.js', URE_PLUGIN_FULL_PATH ), array(), true, true );
        wp_enqueue_script ( 'ure-gutenberg' );
        wp_localize_script( 'ure-gutenberg', 'ure_pro_data', 
                array(
                    'blocked_gb_components' => $blocked_gbc
                ));
    }
    // end of block_gutenberg_components()
    

    /**
     * Remove registered ACF field groups which corresponds to the blocked meta boxes. 
     * This will prevent ACF from register meta boxes for those field groups.
     * @param array $acf_groups
     * @return array
     */
    public function remove_blocked_acf_meta_boxes($acf_groups) {
        
        if (!$this->is_restriction_aplicable()) {
            return $acf_groups;
        }
        
        $this->get_blocked_items();
        if (empty($this->blocked)) {
            return $acf_groups;
        }
        
        $all_meta_boxes = $this->get_all_meta_boxes();
        foreach($this->blocked as $mb_hash) {
            if (!isset($all_meta_boxes[$mb_hash])) {
                continue;
            }
            $blocked_mb = $all_meta_boxes[$mb_hash];
            foreach($acf_groups as $key=>$group) {
                if ('acf_'. $group['id']==$blocked_mb->id) {
                    unset($acf_groups[$key]);
                }
            }
        }
    
        return $acf_groups;
    }
    // end of remove_blocked_acf_meta_boxes()
    
    
    public function get_all_meta_boxes() {
        
        if (self::$meta_boxes_list==null) {
            self::$meta_boxes_list = get_option(self::META_BOXES_LIST_COPY_KEY, array());
        }
        
        return self::$meta_boxes_list;
    }
    // end of get_all_meta_boxes()
    
    
    public function asort_screen($a, $b) {
        
        if ($a['mb']->screen!==$b['mb']->screen) {
            $result = $a['mb']->screen>$b['mb']->screen;
        } else {
            $result = $a['mb']->title>$b['mb']->title;
        }
        
        return $result;
    }
    // end of asort()
    
    
    private function sort_meta_boxes($meta_boxes) {

        $tmp = array();
        foreach ($meta_boxes as $key=>$meta_box) { 
            if (!isset($meta_box->id)) {
                continue;
            }
            $tmp[] = array('hash'=>$key, 'mb'=>$meta_box);
        }
        usort($tmp, array($this, 'asort_screen'));

        $sorted = array();
        foreach ($tmp as $rec) {
            $sorted [$rec['hash']] = $rec['mb'];
        }

        return $sorted;
    }
    // end of sort_meta_boxes()


    public function get_html($user=null) {
        
        $allowed_roles = $this->get_allowed_roles($user);
        if (empty($user)) {
            $ure_object_type = 'role';
            $ure_object_name = $allowed_roles[0];
            $blocked_items = self::load_data_for_role($ure_object_name);
        } else {
            $ure_object_type = 'user';
            $ure_object_name = $user->user_login;
            $blocked_items = $this->load_access_data_for_user($ure_object_name);
        }
        $meta_boxes_list = $this->sort_meta_boxes($this->get_all_meta_boxes());
        if (empty($meta_boxes_list)) {
            $answer = array(
                'result'=>'success', 
                'message'=>'Widgets permissions for '+ $ure_object_name, 
                'html'=>'<span style="color: red;">'. 
                    esc_html__('Please open post, page and (custom post type) editor page to initilize the list of available meta_boxes', 'user-role-editor') .
                    '</span>');
            return $answer;
        }
                
        $multisite = $this->lib->get('multisite');
        $readonly_mode = (!$multisite && $allowed_roles[0]=='administrator') || ($multisite && !is_super_admin());
        $network_admin = filter_input(INPUT_POST, 'network_admin', FILTER_SANITIZE_NUMBER_INT);
        
        ob_start();
?>
<form name="ure_meta_boxes_access_form" id="ure_meta_boxes_access_form" method="POST"
      action="<?php echo URE_WP_ADMIN_URL . ($network_admin ? 'network/':'')  . URE_PARENT.'?page=users-'.URE_PLUGIN_FILE;?>" >
<table id="ure_meta_boxes_access_table" style="width:100%; table-layout:fixed;">
    <th style="color:red;width:7%;"><?php esc_html_e('Block', 'user-role-editor');?></th>
    <th class="ure-cell" style="width:44%;"><?php esc_html_e('Title', 'user-role-editor');?></th>        
    <th class="ure-cell" style="width:44%;"><?php esc_html_e('Id','user-role-editor');?></th>
    <th style="width: 5%;">&nbsp;</th>
<?php
        $current_screen = '-';
        foreach($meta_boxes_list as $key=>$item) {            
            if ($item->screen!==$current_screen) {
                $current_screen = $item->screen;
?>
    <tr>
        <th colspan="3"><?php echo ucfirst($current_screen);?></th>
    </tr>
<?php    
            }
?>
    <tr id="tr_<?php echo $key;?>">
        <td>   
<?php 
    if (!$readonly_mode) {
        $checked = in_array($key, $blocked_items) ? 'checked' : '';
?>
            <input type="checkbox" name="<?php echo $key;?>" id="<?php echo $key;?>" <?php echo $checked;?> />
<?php
    }
?>
        </td>
        <td class="ure-cell" style="width:45%;word-wrap:break-word;"><?php echo $item->title;?></td>
        <td class="ure-cell" style="width:40%;word-wrap:break-word;"><?php echo $item->id;?></td>    
        <td style="text-align: center;">
            <a href="#" onclick="ure_meta_boxes_remove_from_list('<?php echo $key; ?>');" title="<?php esc_html_e('Delete from the list of available meta boxes', 'user-role-editor');?>">
                <img id="remove_<?php echo $key; ?>" src="<?php echo URE_PLUGIN_URL .'images/remove-16.png'?>"/>
                <img id="ajax_<?php echo $key; ?>" src="<?php echo URE_PLUGIN_URL .'images/ajax-loader.gif'?>" style="display: none;"/>
            </a>
        </td>
    </tr>        
<?php
        }   // foreach($meta_boxes_list)
?>
</table> 
    <input type="hidden" name="action" id="action" value="ure_update_meta_boxes_access" />
    <input type="hidden" name="ure_object_type" id="ure_object_type" value="<?php echo $ure_object_type;?>" />
    <input type="hidden" name="ure_object_name" id="ure_object_name" value="<?php echo $ure_object_name;?>" />
<?php
    if ($ure_object_type=='role') {
?>
    <input type="hidden" name="user_role" id="ure_role" value="<?php echo $ure_object_name;?>" />
<?php
    }
?>
    <?php wp_nonce_field('user-role-editor', 'ure_nonce'); ?>
</form>    
<?php    
        $html = ob_get_contents();
        ob_end_clean();        
                        
        return array('result'=>'success', 'message'=>'Meta boxes permissions for '.$ure_object_name, 'html'=>$html);        
    }
    // end of get_html()
}
// end of URE_Metaboxes class