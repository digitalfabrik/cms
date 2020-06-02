<?php
/*
 * Project: User Role Editor Pro WordPress plugin
 * URE addons manager class
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+
 * 
*/

class URE_Addons_Manager {

    private static $instance = null; // object exemplar reference  
    private $lib = null;
    private $addons = null;
    
    
    public static function get_instance() {
        
        if (self::$instance === null) {            
            // new static() will work too
            self::$instance = new URE_Addons_Manager();
        }

        return self::$instance;
    }
    // end of get_instance()
    
    
    private function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        $this->init_addons_list();
        
    }
    // end of __construct()
    
    
    public static function execute_once() {

/*      
 *  Do not delete: Data conversion could be needed again in a future.
        if (class_exists('URE_Admin_Menu_Hashes')) {
            URE_Admin_Menu_Hashes::require_data_conversion();
        }
*/        
    }
    // end of execute_once()
    
    
    private function add( $addon_id, $access_data_key = null, $replicator_title = '', $exportable=false ) {
        
        $addon = new stdClass();
        $addon->id = $addon_id;
        $addon->active = false;        
        $addon->access_data_key = $access_data_key;
        $addon->replicator_title = $replicator_title;
        $addon->exportable = $exportable;
        $this->addons[$addon->id] = $addon;
        
    }
    // end of add()
    
    private function init_addons_list() {
        
        $this->addons = array();
        
        if (class_exists('URE_Admin_Menu')) {
            $exportable = apply_filters('ure_admin_menu_exportable', true);
            $this->add('admin_menu', URE_Admin_Menu::ACCESS_DATA_KEY, esc_html__('Admin menu access restrictions', 'user-role-editor'), $exportable);
        }
        if (class_exists('URE_Front_End_Menu_Access')) {
            $this->add('front_end_menu');
        }
        if ( class_exists( 'URE_Nav_Menus_Admin_Access' ) ) {
            $this->add( 'nav_menus' );
        }
        if (class_exists('URE_Widgets_Admin_Controller')) {
            $exportable = apply_filters('ure_widgets_admin_exportable', true);
            $this->add('widgets_admin', URE_Widgets_Admin_Controller::ACCESS_DATA_KEY, esc_html__('Widgets admin access restrictions', 'user-role-editor'), $exportable);
        }
        if (class_exists('URE_Widgets_Show_Controller')) {
            $this->add('widgets_show', URE_Widgets_Show_Controller::ACCESS_DATA_KEY, esc_html__('Widgets show access restrictions', 'user-role-editor'));
        }
        if (class_exists('URE_Meta_Boxes')) {
            $exportable = apply_filters('ure_meta_boxes_exportable', true);
            $this->add('meta_boxes', URE_Meta_Boxes::ACCESS_DATA_KEY, esc_html__('Meta Boxes access restrictions', 'user-role-editor'), $exportable);
        }
        if (class_exists('URE_Other_Roles')) {
            $exportable = apply_filters('ure_other_roles_exportable', true);
            $this->add('other_roles', URE_Other_Roles::ACCESS_DATA_KEY, esc_html__('Other Roles access restrictions', 'user-role-editor'), $exportable);
        }
        if (class_exists('URE_Posts_Edit_Access')) {
            $exportable = apply_filters('ure_posts_edit_exportable', false);
            $this->add('posts_edit', URE_Posts_Edit_Access_Role::ACCESS_DATA_KEY, esc_html__('Posts/Pages edit access restrictions', 'user-role-editor'), $exportable);
        }
        if (class_exists('URE_Plugins_Access')) {
            $exportable = apply_filters('ure_plugins_admin_exportable', true);
            $this->add('plugins', URE_Plugins_Access_Role::ACCESS_DATA_KEY, esc_html__('Plugins activation/deactivation access restrictions', 'user-role-editor'), $exportable);
        }
        if (class_exists('URE_Page_Permissions_View')) {
            $this->add('page_permissions_view');   
        }
        if (class_exists('URE_Themes_Access')) {
            $this->add('themes_activation');    // for user level only
        }
        if (class_exists('URE_GF_Access')) {
            $this->add('gravity_forms');        // for user level only
        }
        if (class_exists('URE_Content_View_Restrictions')) {
            $exportable = apply_filters('ure_posts_view_exportable', false);
            $this->add('content_view', URE_Content_View_Restrictions_Controller::ACCESS_DATA_KEY, esc_html__('Posts/Pages view access restrictions', 'user-role-editor'), $exportable);
        }
        if (class_exists('URE_Content_View_Shortcode')) {
            $this->add('content_view_shortcode');
        }
        if (class_exists('URE_Additional_Caps')) {
            $this->add('additional_caps');
        }
        if (class_exists('URE_Export_Roles_CSV')) {
            $this->add('export_roles_csv');
        }
    }
    // end of init_addons_list()
    
    
    private function activate($addon_id) {
        if (isset($this->addons[$addon_id])) {
            $this->addons[$addon_id]->active = true;
        } else {
            echo 'Addon '. $addon_id .' is unknown';
            die;
        }
    }
    // end of add()
    
    
    public static function get_replicator_id($addon_id) {
    
        $replicator_id = 'ure_replicate_'. $addon_id .'_access_restrictions';
        
        return $replicator_id;
    }
    // end of get_replicator_id()
    
    
    public function get_all() {
        
        return $this->addons;
        
    }
    // end of get()
    
    
    public function get_active() {
        
        $list = array();
        foreach($this->addons as $addon) {
            if ($addon->active) {
                $list[$addon->id] = $addon;
            }
        }

        return $list;
    }
    // end of get_active()
    
    
    public function get_replicatable() {
        
        $list = array();
        foreach($this->addons as $addon) {
            if ($addon->active && !empty($addon->access_data_key)) {
                $list[$addon->id] = $addon;
            }
        }

        return $list;
    }
    // end of get_replicatable()
    
    
    private function load_admin_menu() {
        
        $activate = $this->lib->get_option('activate_admin_menu_access_module', false);
        if (!empty($activate)) {
            new URE_Admin_Menu_Access();
            $this->activate('admin_menu');
        }
                
    }
    // end of load_admin_menu()
    
    
    private function load_front_end_menu() {
        
        $activate = $this->lib->get_option('activate_front_end_menu_access_module', false);
        if (!empty($activate)) {
            new URE_Front_End_Menu_Access();
            $this->activate('front_end_menu');
        }
                
    }
    // end of load_front_end_menu()
    
    
    private function load_nav_menus() {
        
        $activate = $this->lib->get_option( 'activate_nav_menus_access_module', false );
        if ( !empty($activate) ) {
            new URE_Nav_Menus_Admin_Access();
            $this->activate( 'nav_menus');
        }
                
    }
    // end of load_nav_menus()
    
    
    private function load_widgets_admin() {
        
        if (!is_admin()) {
            return;
        }
        $activate = $this->lib->get_option('activate_widgets_access_module', false);
        if (!empty($activate)) {                        
            new URE_Widgets_Admin_Access();
            $this->activate('widgets_admin');
        }
                
    }
    // end of load_widgets_admin()
    
    
    private function load_widgets_show() {
        
        $activate = $this->lib->get_option('activate_widgets_show_access_module', false);
        if (!empty($activate)) {                        
            new URE_Widgets_Show_Access();
            $this->activate('widgets_show');
        }
                
    }
    // end of load_widgets_admin()
    
    
    private function load_meta_boxes() {
        
        if (!is_admin()) {
            return;
        }
        $activate = $this->lib->get_option('activate_meta_boxes_access_module', false);
        if (!empty($activate)) {
            new URE_Meta_Boxes_Access();
            $this->activate('meta_boxes');
        }
                
    }
    // end of load_widgets()    
    
    
    private function load_other_roles() {
        
        if (!is_admin()) {
            return;
        }
        $activate = $this->lib->get_option('activate_other_roles_access_module', false);
        if (!empty($activate)) {            
            new URE_Other_Roles_Access();
            $this->activate('other_roles');
        }
                
    }
    // end of load_other_roles()

    
    private function load_posts_edit() {
        if (is_network_admin()) {
            return;
        }
        
        $activate = $this->lib->get_option('manage_posts_edit_access', false);
        if (!empty($activate)) {            
            new URE_Posts_Edit_Access();
            $this->activate('posts_edit');
        }
    }
    // end of load_posts_edit()

    
    private function load_plugins() {
        
        if (!is_admin()) {
            return;
        }
        $activate = $this->lib->get_option('manage_plugin_activation_access', false);
        if (!empty($activate)) { 
            new URE_Plugins_Access();
            $this->activate('plugins');
        }
        
    }
    // end of load_plugins()
    
    
    private function load_page_permissions_view() {
        if (!is_admin()) {
            return;
        }
        $activate = $this->lib->get_option('activate_page_permissions_viewer', false);
        if (!empty($activate)) {                                    
            new URE_Page_Permissions_View();
            $this->activate('page_permissions_view');
        }
    }
    // end of load_page_permissions_view()
    
    
    private function load_themes_activation() {
    
        $multisite = $this->lib->get('multisite');
        if (!$multisite) {
            return;
        }
        if (!is_admin()) {
            return;
        }
        $activate = $this->lib->get_option('manage_themes_access', false);
        if (!empty($activate)) {            
            new URE_Themes_Access();
            $this->activate('themes_activation');
        }

    }
    // end of load_themes_activation()
    

    /**
     * Load Gravity Forms Access Restriction module
     * @return void
     */
    private function load_gravity_forms() {
        
        if (!is_admin()) {
            return;
        }
        if ( !class_exists('GFForms') ) {
            return;        
        }
        $activate = $this->lib->get_option('manage_gf_access', false);
        if ($activate) {
            new URE_GF_Access();
            $this->activate('gravity_forms');
        }
        
    }
    // end of load_gravity_forms()
    
    
    private function load_content_view() {
        
        if (is_network_admin()) {
            return;
        }

        $activate = $this->lib->get_option('activate_content_for_roles', false);
        if ($activate) {            
            new URE_Content_View_Restrictions();
            $this->activate('content_view');
        }
        
    }
    // end of load_content_view()
        
    
    private function load_content_view_shortcode() {
        
        if (is_network_admin()) {
            return;
        }

        $activate = $this->lib->get_option('activate_content_for_roles_shortcode', false);
        if ($activate) {            
            new URE_Content_View_Shortcode();
            $this->activate('content_view_shortcode');
        }
        
    }
    // end of load_content_view_shortcode()
    
    
    private function load_additional_caps() {
        if (version_compare(get_bloginfo('version'), '4.9', '<')) {
            return;
        }
        
        $activate_add_caps_for_plugins = $this->lib->get_option('activate_add_caps_for_plugins', false);
        $activate_add_caps_for_languages = $this->lib->get_option('activate_add_caps_for_languages', false);
        $activate_add_caps_for_privacy = $this->lib->get_option('activate_add_caps_for_privacy', false);
        if ($activate_add_caps_for_plugins || $activate_add_caps_for_languages || $activate_add_caps_for_privacy) {
            new URE_Additional_Caps();
            $this->activate('additional_caps');
        }
        
    }
    // end of load_additional_caps()
    
    
    private function load_export_roles_csv() {
        
        $activate = $this->lib->get_option('activate_export_roles_csv', false);
        if ($activate) {            
            new URE_Export_Roles_CSV();
            $this->activate('export_roles_csv');
        }
        
    }
    // end of load_export_roles_csv()
    
        
    public function load_addons() {

        foreach ($this->addons as $addon) {
            $method = 'load_'. $addon->id;
            if (method_exists($this, $method)) {
                $this->$method();
            }
        }
        
    }
    // end of load_addons()
    
}
// end of class URE_Addons_Manager