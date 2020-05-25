<?php
/*
 * User Role Editor Pro WordPress plugin - main class
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v3
 * 
*/

class User_Role_Editor_Pro extends User_Role_Editor {
       
    public $screen_help = null;

    
    public static function get_instance() {
        if (self::$instance===null) {        
            self::$instance = new User_Role_Editor_Pro();
        }
        
        return self::$instance;
    }
    // end of get_instance()

    
    protected function __construct() {
        $this->lib = URE_Lib_Pro::get_instance('user_role_editor');
        
        add_action('ure_on_activation', array($this, 'execute_once'));
        parent::__construct();                                        

        add_action('plugins_loaded', array($this, 'load_addons'));                        
        $this->allow_unfiltered_html(); 
        
        $this->init_updater();
                
    }
    // end of __construct()

    
    public function execute_once() {
        
        URE_Addons_Manager::execute_once();        
                
    }
    // end of update_on_activation()

        
    private function init_updater() {
        $multisite = $this->lib->get('multisite');
        if ((!$multisite && is_admin()) || 
            ($multisite && (is_network_admin() || (is_admin() && defined('DOING_AJAX') && DOING_AJAX)))) {
            require_once(URE_PLUGIN_DIR . 'pro/includes/plugin-update-checker.php');
            $ure_update_checker = new PluginUpdateChecker(URE_UPDATE_URL . '?action=get_metadata&slug=user-role-editor-pro', URE_PLUGIN_FULL_PATH);
            //Add the license key to query arguments.
            $ure_update_checker->addQueryArgFilter(array($this, 'filter_update_checks'));
        }
    }
    // end of init_updater()   
    

    public function plugin_init() {
        parent::plugin_init();

        // URE Setting page
        add_action( 'ure_settings_update1', array('URE_Settings_Pro', 'update1') );
        add_action( 'ure_settings_show1', array('URE_Settings_Pro', 'show1') );
        add_action( 'ure_settings_update2', array('URE_Settings_Pro', 'update2') );        
        add_action( 'ure_settings_show2', array('URE_Settings_Pro', 'show2') );
        add_action( 'ure_settings_tools_show', array( 'URE_Tools_Ext', 'show' ) );
        add_action( 'ure_settings_tools_exec', array( 'URE_Tools_Ext', 'exec' ) );
        
        $multisite = $this->lib->get('multisite');
        if ($multisite) {
            add_action('ure_settings_ms_update', array('URE_Settings_Pro', 'ms_update'));
            add_action('ure_settings_ms_show', array('URE_Settings_Pro', 'ms_show'));
            
            // Replicate add-ons data for all network
            add_action( 'ure_direct_network_roles_update', array( 'URE_Editor_Ext', 'network_replicate_addons_data' ) );
            
            // User permissions update - multisite extenstion
            add_action('ure_before_user_permissions_update', array('URE_Editor_Ext', 'add_user_to_current_blog'), 10, 1 );
            add_action('ure_user_permissions_update', array('URE_Editor_Ext', 'network_update_user'), 10, 1 );
        }
        
        add_action('ure_load_js', array($this, 'add_js'));        
        add_action('ure_load_js_settings', array($this, 'add_js_settings'));
        
        $active_for_network = $this->lib->get('active_for_network');
        if ($multisite && is_network_admin()) {
            if (!$active_for_network) {
                add_filter('network_admin_plugin_action_links_'. URE_PLUGIN_BASE_NAME, 
                           array($this, 'network_admin_plugin_action_links'), 10, 1);
            }
            add_action('ms_user_row_actions', array( $this, 'user_row'), 10, 2);
            add_action('ure_role_edit_toolbar_update', 'URE_Pro_View::add_role_update_network_button');
            add_action('ure_user_edit_toolbar_update', 'URE_Pro_View::add_user_update_network_button');
            add_action('ure_dialogs_html', 'URE_Pro_View::network_update_dialog_html');
            add_action('ure_load_js_settings', array($this, 'add_js_settings_ms'));
        }
/*                
        if (!$multisite) {
            $count_users_without_role = $this->lib->get_option('count_users_without_role', 0);
            if ($count_users_without_role) {
                add_action(URE_Assign_Role_Pro::CRON_ACTION_HOOK, array($this, 'assign_role_to_users_without_role'));
            }
        }
*/        
        $this->screen_help = new URE_Screen_Help_Pro();
    }
    // end of plugin_init()
    
    
    /**
     * Modify plugin action links
     * 
     * @param array $links
     * @param string $file
     * @return array
     */
    public function network_admin_plugin_action_links($links) {
/*
        $settings_link = "<a href='settings.php?page=settings-" . URE_PLUGIN_FILE . "'>" . esc_html__('Settings', 'user-role-editor') . "</a>";
        $links = array_merge($links, array($settings_link));
*/
        return $links;
    }
    // end of network_admin_plugin_action_links()

        
    protected function is_user_profile_extention_allowed() {
        // no limits for the Pro version
        return true;
    }
    // end of is_user_profile_extention_allowed()
    
    
    /**
     * Load additional modules
     * 
     */
    public function load_addons() {
        
        $show_notices_to_admin_only = $this->lib->get_option('show_notices_to_admin_only', false);
        if ($show_notices_to_admin_only) {
            add_action('admin_head', array($this, 'show_notices_to_admin_only'));
        }
        
        $activate_create_post_capability = $this->lib->get_option('activate_create_post_capability', false);
        if ($activate_create_post_capability) {       
            new URE_Create_Posts_Cap();
        }
        
        $force_custom_post_types_capabilities = $this->lib->get_option('force_custom_post_types_capabilities', false);
        if ($force_custom_post_types_capabilities) {
            new URE_Post_Types_Own_Caps();
        }
        
        $manager = URE_Addons_Manager::get_instance();
        $manager->load_addons();        

        if ((is_admin() || is_network_admin()) && (!(defined('DOING_AJAX') && DOING_AJAX))) {
            if (current_user_can('ure_export_roles')) {
                new URE_Export_Single_Role();
            }
            if (current_user_can('ure_import_roles')) {
                new URE_Import_Single_Role();
            }
        }

        
        $multisite = $this->lib->get('multisite');
        if ( $multisite ) {
            // Copy addons data for new created blog
            // This actions should be linked to front-end too. As new site (PmPro for example) can be registered directly from front-end
            add_filter('ure_get_addons_data_for_new_blog', array('URE_Network_Addons_Data_Replicator', 'get_for_new_blog'), 10, 1);
            add_action('ure_set_addons_data_for_new_blog', array('URE_Network_Addons_Data_Replicator', 'set_for_new_blog'), 10, 2);
        }

    }
    // end of load_addons()
    
                             
    public function network_plugin_menu() {
        
        parent::network_plugin_menu();
        
        $multisite = $this->lib->get('multisite');
        if ($multisite) {
            $ure_page = add_submenu_page('users.php', esc_html__('User Role Editor', 'user-role-editor'), esc_html__('User Role Editor', 'user-role-editor'), 
            $this->key_capability, 'users-'.URE_PLUGIN_FILE, array($this, 'edit_roles'));
            add_action("admin_print_styles-$ure_page", array($this, 'admin_css_action'));        
        }
        
    } 
    // end of network_plugin_menu()
                
	
    public function filter_update_checks($query_args) {
    
        $license_key = new URE_License_Key($this->lib);
        $license_key_value = $license_key->get();
        if (!empty($license_key_value)) {
            $query_args['license_key'] = $license_key_value;
        }

        return $query_args;
    }
    // end of filter_update_checks()
    
    
    public function add_js() {
        
        wp_register_script( 'ure-js-pro', plugins_url( '/pro/js/ure-pro.js', URE_PLUGIN_FULL_PATH ) );
        wp_enqueue_script ( 'ure-js-pro' );
        
        $manager = URE_Addons_Manager::get_instance();
        $addons = $manager->get_replicatable();
        $replicators = array();
        foreach($addons as $addon) {
            $replicators[] = $manager->get_replicator_id($addon->id);
        }
        
        wp_localize_script( 'ure-js-pro', 'ure_data_pro', 
                array(
                    'update_network' => esc_html__('Update Network', 'user-role-editor'),
                    'replicators'=>$replicators
                ));
    }
    // end of add_js()
    

    public function add_js_settings() {
        
        wp_register_script( 'ure-settings-pro', plugins_url( '/pro/js/settings.js', URE_PLUGIN_FULL_PATH ) );
        wp_enqueue_script ( 'ure-settings-pro' );
        wp_localize_script( 'ure-settings-pro', 'ure_settings_data_pro', 
                array(
                    'admin_menu_allowed_args_dialog_title' => esc_html__('Admin menu allowed arguments for URLs', 'user-role-editor'),
                    'extract_button' => esc_html__('Extract', 'user-role-editor'),
                    'update_button' => esc_html__('Update', 'user-role-editor'),
                    'close_button' => esc_html__('Close', 'user-role-editor'),
                    'export_button' => esc_html__('Export', 'user-role-editor'),
                    'no_allowed_args_to_send' => esc_html__('Input allowed aguments before try to save it.', 'user-role-editor')
                ));
        
    }
    // end of add_js_settings()
    
    
    public function add_js_settings_ms() {
        
        wp_register_script( 'ure-jquery-dual-listbox', plugins_url( '/pro/js/jquery.dualListBox-1.3.js', URE_PLUGIN_FULL_PATH ) );
        wp_enqueue_script ( 'ure-jquery-dual-listbox' );        
        
    }
    // end of add_js_settings_ms()
    
    
    protected function allow_unfiltered_html() {
        
        $multisite = $this->lib->get('multisite');
        if ( !$multisite || !is_admin() ||  
             ((defined( 'DISALLOW_UNFILTERED_HTML' ) && DISALLOW_UNFILTERED_HTML)) ) {
            return;
        }
        
        $enable_unfiltered_html_ms = $this->lib->get_option('enable_unfiltered_html_ms', 0);
        if ($enable_unfiltered_html_ms) {
            add_filter('map_meta_cap', array($this, 'allow_unfiltered_html_filter'), 10, 2);
        }
        
    }
    // end of allow_unfiltered_html()
    
    
    public function allow_unfiltered_html_filter($caps, $cap='') {

        $current_user = wp_get_current_user();
        if ($cap=='unfiltered_html') {
            if (isset($current_user->allcaps['unfiltered_html']) && 
                $current_user->allcaps['unfiltered_html'] && $caps[0]=='do_not_allow') {
                $caps[0] = 'unfiltered_html';
                return $caps;
            }        
        }

        return $caps;

    }
    // end of allow_unfiltered_html_for_simple_admin()

    
    public function ure_ajax() {
                
        $ajax_processor = new URE_Pro_Ajax_Processor( );
        $ajax_processor->dispatch();
        
    }
    // end of ure_ajax()

    
    /**
     * Returns object with data about view access restrictions applied to the post with ID $post_id or
     * false in case there are not any view access restrictions for this post
     * 
     * @param int $post_id  Post ID
     * @return \stdClass|boolean
     */
    public function get_post_view_access_users($post_id) {
                    
        $activate_content_for_roles = $this->lib->get_option('activate_content_for_roles', false);
        if (!$activate_content_for_roles) {
            return false;
        }
        
        $result = URE_Content_View_Restrictions::get_post_view_access_users($post_id);
                        
        return $result;
    }
    // end of get_post_view_access_users($)
    
/*    
    // job to execute by WP Cron scheduler
    public function assign_role_to_users_without_role() {
        
        $assign_role = $this->lib->get_assign_role();
        $assign_role->make();
    }
    // end of assign_role_to_users_without_role()
*/
    
    public function show_notices_to_admin_only() {
        
        if (current_user_can('install_plugins')) {
            return;
        }
        echo '
<style>
    .update-nag, .notice { 
        display: none; 
    }
    #message.notice {
        display: block;
    }
</style>
';
    }
    // end of show_notices_to_admin_only()
    
}
// end of class User_Role_Editor_Pro