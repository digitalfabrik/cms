<?php
/**
 * Settings manager
 * 
 * Project: User Role Editor Pro WordPress plugin
 * 
 * Author: Vladimir Garagulia
 * email: support@role-editor.com
 *
**/
class URE_Settings_Pro {        
    
    /*
     * General options tab update
     */
    public static function update1() {
              
        $lib = URE_Lib_Pro::get_instance();
        
        $show_notices_to_admin_only = $lib->get_request_var('show_notices_to_admin_only', 'post', 'checkbox');
        $lib->put_option('show_notices_to_admin_only', $show_notices_to_admin_only);
                
        $license_key = new URE_License_Key($lib);
        if ($license_key->is_editable()) {
            $license_key_value = $lib->get_request_var('license_key', 'post');            
            if (!empty($license_key_value) && strpos($license_key_value, '*')===false) {
                $lib->put_option('license_key', $license_key_value);
            }
        }
    }
    // end of update1()
    

    private static function update_content_view_defaults() {

        $lib = URE_Lib_Pro::get_instance();
        
        $content_view_allow_flag = $lib->get_request_var('content_view_allow_flag', 'post', 'int');
        $lib->put_option('content_view_allow_flag', $content_view_allow_flag);
        
        $content_view_whom = $lib->get_request_var('content_view_whom', 'post', 'int');
        $lib->put_option('content_view_whom', $content_view_whom);
        
        $content_view_access_error_action = $lib->get_request_var('content_view_access_error_action', 'post', 'int');
        $lib->put_option('content_view_access_error_action', $content_view_access_error_action);
        
        if ( $content_view_access_error_action==2 ) {			// Show access error message
            // It's not escaped in order to allow to a user to use HTML links inside this text
            $content_view_access_error_message = $_POST['content_view_access_error_message'];
            $lib->put_option('post_access_error_message', $content_view_access_error_message);
        }
								
								// Redirect to URL																
								$content_view_access_error_url = filter_input(INPUT_POST, 'content_view_access_error_url', FILTER_SANITIZE_URL);
								$lib->put_option('content_view_access_error_url', $content_view_access_error_url);
								        
    }
    // end of update_content_view_defaults()
    
    
    /*
     * Additional Modules options tab update
     */
    public static function update2() {
        
        $lib = URE_Lib_Pro::get_instance();
                            
        $activate_admin_menu_access_module = $lib->get_request_var('activate_admin_menu_access_module', 'post', 'checkbox');
        $lib->put_option('activate_admin_menu_access_module', $activate_admin_menu_access_module);
        
        $activate_front_end_menu_access_module = $lib->get_request_var('activate_front_end_menu_access_module', 'post', 'checkbox');
        $lib->put_option('activate_front_end_menu_access_module', $activate_front_end_menu_access_module);
        
        $activate_nav_menus_access_module = $lib->get_request_var('activate_nav_menus_access_module', 'post', 'checkbox');
        $lib->put_option( 'activate_nav_menus_access_module', $activate_nav_menus_access_module );
        
        $activate_widgets_access_module = $lib->get_request_var('activate_widgets_access_module', 'post', 'checkbox');
        $lib->put_option('activate_widgets_access_module', $activate_widgets_access_module);
        
        $activate_widgets_show_access_module = $lib->get_request_var('activate_widgets_show_access_module', 'post', 'checkbox');
        $lib->put_option('activate_widgets_show_access_module', $activate_widgets_show_access_module);
        
        $activate_meta_boxes_access_module = $lib->get_request_var('activate_meta_boxes_access_module', 'post', 'checkbox');
        $lib->put_option('activate_meta_boxes_access_module', $activate_meta_boxes_access_module);
        
        $activate_other_roles_access_module = $lib->get_request_var('activate_other_roles_access_module', 'post', 'checkbox');
        $lib->put_option('activate_other_roles_access_module', $activate_other_roles_access_module);
        
        $manage_plugin_activation_access = $lib->get_request_var('manage_plugin_activation_access', 'post', 'checkbox');
        $lib->put_option('manage_plugin_activation_access', $manage_plugin_activation_access);
        
        $activate_page_permissions_viewer = $lib->get_request_var('activate_page_permissions_viewer', 'post', 'checkbox');
        $lib->put_option('activate_page_permissions_viewer', $activate_page_permissions_viewer);
        
        $activate_export_roles_csv = $lib->get_request_var('activate_export_roles_csv', 'post', 'checkbox');
        $lib->put_option('activate_export_roles_csv', $activate_export_roles_csv);
        
        $manage_posts_edit_access = $lib->get_request_var('manage_posts_edit_access', 'post', 'checkbox');
        $lib->put_option('manage_posts_edit_access', $manage_posts_edit_access);

        if ($manage_posts_edit_access) {
            $activate_create_post_capability = 1;
        } else {
            $activate_create_post_capability = $lib->get_request_var('activate_create_post_capability', 'post', 'checkbox');
        }
        $lib->put_option('activate_create_post_capability', $activate_create_post_capability);
        
        $force_custom_post_types_capabilities = $lib->get_request_var('force_custom_post_types_capabilities', 'post', 'checkbox');
        $lib->put_option('force_custom_post_types_capabilities', $force_custom_post_types_capabilities);
        
        if (class_exists('GFForms')) {
            $manage_gf_access = $lib->get_request_var('manage_gf_access', 'post', 'checkbox');
            $lib->put_option('manage_gf_access', $manage_gf_access);
        }

        $activate_content_for_roles_shortcode = $lib->get_request_var('activate_content_for_roles_shortcode', 'post', 'checkbox');
        $lib->put_option('activate_content_for_roles_shortcode', $activate_content_for_roles_shortcode);
        
        $activate_content_for_roles = $lib->get_request_var('activate_content_for_roles', 'post', 'checkbox');
        $lib->put_option('activate_content_for_roles', $activate_content_for_roles);
        if ($activate_content_for_roles==1) {
            self::update_content_view_defaults();
        }                
        
        $cvr_defaults_visible = $lib->get_request_var('cvr_defaults_visible', 'post', 'int');
        $lib->put_option('cvr_defaults_visible', $cvr_defaults_visible);
        
        $activate_add_caps_for_plugins = $lib->get_request_var('activate_add_caps_for_plugins', 'post', 'checkbox');
        $lib->put_option('activate_add_caps_for_plugins', $activate_add_caps_for_plugins);
        
        $activate_add_caps_for_languages = $lib->get_request_var('activate_add_caps_for_languages', 'post', 'checkbox');
        $lib->put_option('activate_add_caps_for_languages', $activate_add_caps_for_languages);
        
        $activate_add_caps_for_privacy = $lib->get_request_var('activate_add_caps_for_privacy', 'post', 'checkbox');
        $lib->put_option('activate_add_caps_for_privacy', $activate_add_caps_for_privacy);
        
        
        
    }
    // end of update2()
    
    
    /**
     * Exclude not existing capabilities
     * @param string $user_caps_array - name of POST variable with array of capabilities from user input
     */
    private static function filter_existing_caps_input( $user_caps_array ) {
                
        if ( isset( $_POST[$user_caps_array] ) && is_array( $_POST[$user_caps_array] ) ) {
            $user_caps = $_POST[$user_caps_array];
        } else {
            $user_caps = array();
        }
        if ( count( $user_caps ) ) {
            $lib = URE_Lib_Pro::get_instance();
            $full_capabilities = $lib->init_full_capabilities( 'role' );
            foreach ( $user_caps as $cap ) {
                if ( !isset( $full_capabilities[$cap] ) ) {
                    unset( $user_caps[$cap] );
                }
            }
        }

        return $user_caps;
    }
    // end of filter_existing_caps_input()    
    
    
    // Update settings from Multisite tab
    public static function ms_update() {
    
        $lib = URE_Lib_Pro::get_instance();
        $multisite = $lib->get('multisite');
        if (!$multisite) {
            return;
        }
        
        if (defined('URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE') && (URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE == 1)) {
            $enable_simple_admin_for_multisite = 1;
        } else {
            $enable_simple_admin_for_multisite = $lib->get_request_var('enable_simple_admin_for_multisite', 'post', 'checkbox');
        }
        $lib->put_option('enable_simple_admin_for_multisite', $enable_simple_admin_for_multisite);
        
        $enable_unfiltered_html_ms = $lib->get_request_var('enable_unfiltered_html_ms', 'post', 'checkbox');
        $lib->put_option('enable_unfiltered_html_ms', $enable_unfiltered_html_ms);
                
        $manage_themes_access = $lib->get_request_var('manage_themes_access', 'post', 'checkbox');
        $lib->put_option('manage_themes_access', $manage_themes_access);
        
        $caps_access_restrict_for_simple_admin = $lib->get_request_var('caps_access_restrict_for_simple_admin', 'post', 'checkbox');
        $lib->put_option('caps_access_restrict_for_simple_admin', $caps_access_restrict_for_simple_admin);
        if ($caps_access_restrict_for_simple_admin) {
            $add_del_role_for_simple_admin = $lib->get_request_var('add_del_role_for_simple_admin', 'post', 'checkbox');
            $caps_allowed_for_single_admin = self::filter_existing_caps_input('caps_allowed_for_single_admin');            
        } else {
            $add_del_role_for_simple_admin = 1;
            $caps_allowed_for_single_admin = array();            
        }
        $lib->put_option('add_del_role_for_simple_admin', $add_del_role_for_simple_admin);
        $lib->put_option('caps_allowed_for_single_admin', $caps_allowed_for_single_admin);
        
    }
    // end of ms_update()


    /**
     * Show options at General tab
     * 
     */
    public static function show1() {
		                
        $lib = URE_Lib_Pro::get_instance();
        $show_notices_to_admin_only = $lib->get_option('show_notices_to_admin_only', false);
       
        $license_key = new URE_License_Key($lib);
        $license_key_value = $license_key->get();
        $license_state = $license_key->validate($license_key_value);        
        if ($license_state['state']=='active') {
            $license_state_color = 'green';
        } else {
            $license_state_color = 'red';
        }
        $multisite = $lib->get('multisite');
        $active_for_network = $lib->get('active_for_network');
        $license_key_only = $multisite && is_network_admin() && !$active_for_network;                
        if ($multisite) {
            $link = 'settings.php';
        } else {
            $link = 'options-general.php';
        }        
        
        require_once(URE_PLUGIN_DIR .'pro/includes/settings-template1.php');
    }
    // end of show1()
     

    /**
     * Show options at Additional Modules tab
     * 
     */
    public static function show2() {
		      
        $lib = URE_Lib_Pro::get_instance();
        
        $activate_admin_menu_access_module = $lib->get_option('activate_admin_menu_access_module', false);
        $activate_front_end_menu_access_module = $lib->get_option('activate_front_end_menu_access_module', false);
        $activate_nav_menus_access_module = $lib->get_option('activate_nav_menus_access_module', false);
        $activate_widgets_access_module = $lib->get_option('activate_widgets_access_module', false);  // Widgets Admin Access to widget configuration
        $activate_widgets_show_access_module = $lib->get_option('activate_widgets_show_access_module', false);    // Widgets Show/View Access (at front-end)
        $activate_meta_boxes_access_module = $lib->get_option('activate_meta_boxes_access_module', false);
        $activate_other_roles_access_module = $lib->get_option('activate_other_roles_access_module', false);
        $manage_plugin_activation_access = $lib->get_option('manage_plugin_activation_access', false);
        $activate_page_permissions_viewer = $lib->get_option('activate_page_permissions_viewer', false);
        $activate_export_roles_csv = $lib->get_option('activate_export_roles_csv', false);
        if (class_exists('GFForms')) {
            $manage_gf_access = $lib->get_option('manage_gf_access', false);
        }
        
// content editing restrictions        
        $activate_create_post_capability = $lib->get_option('activate_create_post_capability', false);
        $manage_posts_edit_access = $lib->get_option('manage_posts_edit_access', false);
        $force_custom_post_types_capabilities = $lib->get_option('force_custom_post_types_capabilities', false);

// content view restrictions
        $activate_content_for_roles_shortcode = $lib->get_option( 'activate_content_for_roles_shortcode', false );
        $activate_content_for_roles = $lib->get_option( 'activate_content_for_roles', false );
        // default values
        $content_view_allow_flag = $lib->get_option( 'content_view_allow_flag', 2 );
        // For whom
        $content_view_whom = $lib->get_option( 'content_view_whom', 3 );
        // Action
        $content_view_access_error_action = $lib->get_option( 'content_view_access_error_action', 2 );
        // Access error message
        $content_view_access_error_message = stripslashes( $lib->get_option( 'post_access_error_message', 
            '<p class="restricted">Not enough permissions to view this content.</p>' ) );
								// Access error redirection URL
								$content_view_access_error_url = $lib->get_option( 'content_view_access_error_url', '' );

// Content view defaults section visibility
        $cvr_defaults_visible = $lib->get_option( 'cvr_defaults_visible', 0 );

        
// Additional user capabilities        
        $activate_add_caps_for_plugins = $lib->get_option('activate_add_caps_for_plugins', false);
        $activate_add_caps_for_languages = $lib->get_option('activate_add_caps_for_languages', false);
        $activate_add_caps_for_privacy = $lib->get_option('activate_add_caps_for_privacy', false);
            
        $multisite = $lib->get('multisite');
        if ($multisite) {
            $link = 'settings.php';
        } else {
            $link = 'options-general.php';
        }
        
        
        require_once(URE_PLUGIN_DIR .'pro/includes/settings-template2.php');
    }
    // end of show2()


    private static function build_html_caps_blocked_for_single_admin() {
        
        $lib = URE_Lib_Pro::get_instance();
        $full_capabilities = $lib->init_full_capabilities( 'role' );
        $allowed_caps = $lib->get_option( 'caps_allowed_for_single_admin', array() );
        $html = '';
        // Core capabilities list
        foreach ( $full_capabilities as $capability ) {
            if ( !$capability['wp_core'] ) { // show WP built-in capabilities 1st
                continue;
            }
            if ( !in_array( $capability['inner'], $allowed_caps ) ) {
                $html .= '<option value="' . $capability['inner'] . '">' . $capability['inner'] . '</option>' . "\n";
            }
        }
        // Custom capabilities
        $built_in_wp_caps = $lib->get_built_in_wp_caps();
        $quant = count( $full_capabilities ) - count( $built_in_wp_caps );
        if ( $quant > 0 ) {            
            // Custom capabilities list
            foreach ( $full_capabilities as $capability ) {
                if ( $capability['wp_core'] ) { // skip WP built-in capabilities 1st
                    continue;
                }
                if ( !in_array( $capability['inner'], $allowed_caps ) ) {
                    $html .= '<option value="' . $capability['inner'] . '" style="color: blue;">' . $capability['inner'] . '</option>' . "\n";
                }
            }
        }

        return $html;
    }
    // end of build_html_caps_blocked_for_single_admin()

    
    private static function build_html_caps_allowed_for_single_admin() {
        
        $lib = URE_Lib_Pro::get_instance();
        $full_capabilities = $lib->init_full_capabilities( 'role' );
        $allowed_caps = $lib->get_option( 'caps_allowed_for_single_admin', array() );
        if ( count( $allowed_caps )==0 ) {
            return '';
        }
        
        $build_in_wp_caps = $lib->get_built_in_wp_caps();
        $html = '';
        // Core capabilities list
        foreach ( $allowed_caps as $cap ) {
            if ( !isset( $build_in_wp_caps[$cap] ) ) { // show WP built-in capabilities 1st
                continue;
            }
            $html .= '<option value="' . $cap . '">' . $cap . '</option>' . "\n";
        }
        // Custom capabilities
        
        $quant = count( $full_capabilities ) - count( $build_in_wp_caps );
        if ($quant > 0) {
            // Custom capabilities list
            foreach ( $allowed_caps as $cap ) {
                if ( isset($build_in_wp_caps[$cap] ) ) { // skip WP built-in capabilities 1st
                    continue;
                }
                $html .= '<option value="' . $cap . '" style="color: blue;">' . $cap . '</option>' . "\n";
            }
        }

        return $html;
    }
    // end of build_html_caps_allowed_for_single_admin()
    
    
    public static function ms_show() {
        
        $lib = URE_Lib_Pro::get_instance();
        $multisite = $lib->get('multisite');
        if (!$multisite) {
            return;
        }

        if (defined('URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE') && (URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE == 1)) {
            $enable_simple_admin_for_multisite = 1;
        } else {
            $enable_simple_admin_for_multisite = $lib->get_option('enable_simple_admin_for_multisite', 0);
        }
        $enable_unfiltered_html_ms = $lib->get_option('enable_unfiltered_html_ms', 0);
        $manage_themes_access = $lib->get_option('manage_themes_access', 0);
        $caps_access_restrict_for_simple_admin = $lib->get_option('caps_access_restrict_for_simple_admin', 0);
        if ($caps_access_restrict_for_simple_admin) {  
            $add_del_role_for_simple_admin = $lib->get_option('add_del_role_for_simple_admin', 1);
            $html_caps_blocked_for_single_admin = self::build_html_caps_blocked_for_single_admin();
            $html_caps_allowed_for_single_admin = self::build_html_caps_allowed_for_single_admin();
        }
        
        require_once(URE_PLUGIN_DIR . 'pro/includes/settings-template-ms.php');

    }
    // end of ms_show()


}
// end of class URE_Settings_Pro
