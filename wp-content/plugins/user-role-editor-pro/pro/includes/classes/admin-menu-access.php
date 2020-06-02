<?php

/*
 * User Role Editor WordPress plugin
 * Class URE_Admin_Menu_Access - prohibit selected menu items for role or user
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://role-editor.com
 * License: GPL v2+ 
 */

class URE_Admin_Menu_Access {

    const DEBUG = false;
    const BLOCKED_URLS = 'ure_admin_menu_blocked_urls';
    
    // reference to the code library object
    private $lib = null;   
    
    // Temporally store links to the modified submenus
    private $submenu_modified = null;
    
    // Temporally store blocked_urls list
    private $blocked_urls = null;
    

    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        
        add_action('ure_role_edit_toolbar_service', 'URE_Admin_Menu_View::add_toolbar_buttons');
        add_action('ure_load_js', 'URE_Admin_Menu_View::add_js');
        add_action('ure_dialogs_html', 'URE_Admin_Menu_View::dialog_html');
        add_action('ure_process_user_request', 'URE_Admin_Menu::update_data');
        
        add_action('activated_plugin', 'URE_Admin_Menu_Copy::force_update');
        add_action('admin_menu', array($this, 'menu_glitches_cleanup'), 9998);
        add_action('admin_menu', 'URE_Admin_Menu_Copy::update', 9999999997);  // Jetpack uses 998, MetaSlider - 9553, FooGallery - 999999999. We should execute code as late as possible.
        add_action('admin_head', array($this, 'protect'), 101);
        add_action('customize_controls_init', array($this, 'redirect_blocked_urls'), 10);  // Especially for the customize.php URL        
        add_action('admin_bar_menu', array($this, 'replace_wp_admin_bar_my_sites_menu'), 19);
        add_action('wp_before_admin_bar_render', array($this, 'modify_admin_menu_bar'), 101);
        add_filter('media_view_strings', array($this, 'block_media_upload'), 99);
    }
    // end of __construct()


    public function protect() {
        
        $this->remove_blocked_menu_items();
        $this->redirect_blocked_urls();
        
    }
    // end of protect()


    /**
     * Check if there is an accessible submenu item - replace menu item capability with submenu item capability then
     * @global array $submenu
     * @param array $menu_item
     * @return boolean
     */
    private function is_submenu_permitted($menu_item) {
        global $submenu;
        
        $permitted = false;
        if (isset($submenu[$menu_item[2]])) {
            foreach($submenu[$menu_item[2]] as $submenu_item) {
                if (current_user_can($submenu_item[1])) {
                    $permitted = true;
                    break;
                }
            }
        }
        
        return $permitted;
    }
    // end of is_submenu_permitted()
    
    
    /* 
     * Remove menus that require privileges which a user does not have.
     */
    private function remove_prohibited_items() {
        global $menu;
        
        foreach ($menu as $key=>$menu_item) {
            if (!current_user_can($menu_item[1]) && !$this->is_submenu_permitted($menu_item)) {
                unset($menu[$key]);
            }                        
        } // foreach($menu ...
        
    }
    // end of remove_prohibited_items()
  
    
    // Remove every separator which is followed by another separator
    public function remove_duplicated_separators() {
    
        global $menu;
        
        $count = 0;
        foreach ($menu as $key=>$item) {            
            if ( $item[4]=='wp-menu-separator' ) {
              $count++;  
            } else {
                $count = 0;
            }
            if ( $count>1 ) {
                unset( $menu[$key] ); 
                $count = 0;
            }
        }
        
    }
    // end of remove_duplicated_separators()
    
    
    /**
     * Some plugins incorrectly modify globals $menu/$submenu and users with changed permissions may get broken $menu/submenu$ structures.
     * This function fixes that, removing broken menu/submenu items.
     * @global type $menu
     * @global array $submenu
     */
    public function menu_glitches_cleanup() {
        global $menu, $submenu;
        
        foreach($menu as $key=>$item) {
            if (!isset($item[1])) {
                unset($menu[$key]);
            }
        }
        foreach($submenu as $key=>$items_list) {
            foreach($items_list as $item_key=>$item) {
                if (!isset($item[1]) || empty($item[1])) {
                    unset($submenu[$key][$item_key]);
                }
            }
        }
        
        $this->remove_prohibited_items();
        
    }
    // end of menu_glitches_cleanup()
    
    
    /**
     * Try to search an initial key in case it was changed according to a current user privileges, like in case when
     * user has 'manage_categories', but does not have 'edit_posts', when submenu index becomes 
     * edit-tags.php?taxonomy=category instead of initial edit.php, which was saved with $submenu_copy.
     * 
     * @param type $key
     * @param type $submenu_copy 
     */
    private function search_initial_submenu_key($key, $submenu_copy) {

        foreach($submenu_copy as $ind=>$items) {
            foreach($items as $item) {
                if ($item[2]===$key) {
                    return $ind;
                }
            }
        }
        
        return false;
    }
    // end of search_initial_submenu_key()
    
    
    /** 
     * Compare links of the current WordPress admin submenu and its copy used by User Role Editor admin access add-on
     * The same menu items may have different indexes at the $submenu global array built for the current user and 
     * its copy made for the superadmin
     * 
     * @global array $submenu
     * @param string $key
     * @param string $key1
     * @param array $submenu_copy
     * @return boolean
     */
    private function find_submenu_item($key, $key1, $submenu_copy) {
        global $submenu;
        
        if (!isset($submenu_copy[$key]) || !is_array($submenu_copy[$key])) {
            $key0 = $this->search_initial_submenu_key($key, $submenu_copy);
            if (empty($key0)) {
                return false;
            }            
        } else {
            $key0 = $key;
        }
        $link1 = URE_Admin_Menu::normalize_link($submenu[$key][$key1][2]);
        if (isset($submenu_copy[$key0][$key1])) {
            $link2 = URE_Admin_Menu::normalize_link($submenu_copy[$key0][$key1][2]);
            if ($link1==$link2) { // submenu item does not match with the same index at a copy
                return array('key0'=>$key0, 'key'=>$key1);
            }
        }
        
        $key2 = $this->get_key_from_menu_copy($submenu[$key][$key1], $submenu_copy[$key0]);
        if (!empty($key2)) {
            $link2 = URE_Admin_Menu::normalize_link($submenu_copy[$key0][$key2][2]);
            if ($link1==$link2) {
                return array('key0'=>$key0, 'key'=>$key2);
            }
        }
        
        return false;
    }
    // end of find_submenu_item()
    
    
    // Check if WordPress admin menu link is included into the menu copy, used by User Role Editor admin access add-on
    private function get_key_from_menu_copy($menu_item, $menu_copy) {
        
        $key_found = false;
        foreach($menu_copy as $key=>$menu_item1) {
            if ($menu_item[2]==$menu_item1[2]) {
                $key_found = $key;
                break;
            }
        }
        
        return $key_found;
    }
    // end of get_key_from_menu_copy()
    
    
    private function remove_submenu_item($link, $blocked, $key, $key1) {
        global $submenu;
        
        $item_id = URE_Admin_Menu::calc_menu_item_id('submenu', $link);
        if ( ($blocked['access_model']==1 && in_array($item_id, $blocked['data'])) ||
             ($blocked['access_model']==2 && !in_array($item_id, $blocked['data'])) ||
            // some plugins like 'Yith WooCommerce wishlist' builds menu incorrectly - 
            // menus "Yith Plugins" with non-available capabilities (manage_options, install_plugins) stay available to the user.
            // Therefor we duplicate access checking here
             !current_user_can($submenu[$key][$key1][1]) ) { 
            unset($submenu[$key][$key1]);
            $this->blocked_urls[] = $link;
            $this->submenu_modified[$key] = 1;
        }
    }
    // end of remove_submenu_item()
    
    
    private function remove_from_submenu($blocked, $submenu_copy) {
        global $submenu;
        
        $this->submenu_modified = array();
        $this->blocked_urls = array();
        foreach($submenu as $key=>$menu_item) {
            foreach(array_keys($menu_item) as $key1) {
                $data = $this->find_submenu_item($key, $key1, $submenu_copy);
                if (empty($data)) {
                    continue;
                }                
                $key0 = $data['key0'];
                $key2 = $data['key'];
                $link = URE_Admin_Menu::normalize_link($submenu_copy[$key0][$key2][3]);
                $this->remove_submenu_item($link, $blocked, $key, $key1);
            } 
            
        }
        
    }
    // end of remove_from_submenu()
    
    /**
     * Relink submenu to another key
     * 
     * @param string $key
     * @param string $submenu_key
     * @param string $submenu_copy
     */
    private function relink_submenu($old_submenu_key, $new_submenu_key, $submenu_copy) {
        global $submenu;
                        
        // replace relative links in submenu to the full path absolute links 
        foreach (array_keys($submenu[$old_submenu_key]) as $item_key) {
            $data = $this->find_submenu_item($old_submenu_key, $item_key, $submenu_copy);
            if (!empty($data)) {
                $key0 = $data['key0'];
                $key2 = $data['key'];
                $submenu[$old_submenu_key][$item_key][2] = $submenu_copy[$key0][$key2][3];
            }
        }
        
        // re-link submenu to another key
        $tmp_copy = $submenu[$old_submenu_key];
        unset($submenu[$old_submenu_key]);
        $submenu[$new_submenu_key] = $tmp_copy;
        
    }
    // end relink_submenu
    
    
    private function update_menu($key, $submenu_key, $submenu_copy, $blocked_menu_item) {
        global $menu, $submenu;
        
        if (!isset($submenu[$submenu_key])) {
            unset($menu[$key]);
        } elseif (count($submenu[$submenu_key])==0) {
            unset($submenu[$submenu_key]);
            unset($menu[$key]);   
        } elseif (count($submenu[$submenu_key])==1) { // submenu has the only item
            if ($blocked_menu_item && isset($submenu[$submenu_key][0][2]) &&  $submenu[$submenu_key][0][2]==$submenu_key) {  // and submenu item has the same link as the blocked menu item
                // written for and tested with "Email Subscribers & Newsletters" (email-subscribers) plugin and similar, where top level menu item has the same link as the 1st submenu item, but has another user capability.
                unset($submenu[$submenu_key]);
                unset($menu[$key]);   
            }
        } else {
            reset($submenu[$submenu_key]);
            $submenu_1st_key = key($submenu[$submenu_key]);
            $data = $this->find_submenu_item($submenu_key, $submenu_1st_key, $submenu_copy);
            $key2 = $data['key'];
            if (empty($key2)) {
                return;
            }
            $key0 = $data['key0'];
            if (count($submenu[$submenu_key])==1) {
                $menu[$key][0] = $submenu_copy[$key0][$key2][0];   // replace Menu item title
            }
            $menu[$key][1] = $submenu_copy[$key0][$key2][1];   // Menu item capability            
            $menu[$key][2] = $submenu_copy[$key0][$key2][3];   // absolute menu item link            
            $this->relink_submenu($submenu_key, $menu[$key][2], $submenu_copy);          
        }        
    }
    // end update_menu()
    
        
    private function refresh_blocked_urls_transient() {

        $current_user_id = get_current_user_id();
        $data = get_transient(self::BLOCKED_URLS);
        if (!is_array($data)) {
            $data = array();
        }
        $data[$current_user_id] = $this->blocked_urls;
        set_transient(self::BLOCKED_URLS, $data, 15);
        
    }
    // end of refresh_blocked_urls()


    /*
     * Remove "WCMp Commissions" menu item from "WooCommerce" menu and add it to the top level menu items
     */
    private function update_wcmp_menu(&$menu_copy, $submenu_copy) {
    
        $wcmp_item = null;
        foreach($submenu_copy['woocommerce'] as $key=>$item) {
            if ($item[2]==='edit.php?post_type=dc_commission') {
                $wcmp_item = $item; 
                unset($submenu_copy['woocommerce'][$key]);
                break;
            }
        }
        if (!empty($wcmp_item)) {
            $menu_copy[] = $item;
        }
        
    }
    // end of update_wcmp_menu()
    
    
    private function remove_blocked_menu_items() {
        global $menu;
                        
        if ($this->lib->is_super_admin()) {
            return;
        }      
        
        $current_user = wp_get_current_user();
        $blocked = URE_Admin_Menu::load_data_for_user($current_user);
        if (empty($blocked['data'])) {
            return;
        }

        $menu_copy = URE_Admin_Menu_Copy::get_menu();
        $submenu_copy = URE_Admin_Menu_Copy::get_submenu(); 
        if (URE_Plugin_Presence::is_active('wcmp') && !current_user_can('manage_woocommerce')) {
            $this->update_wcmp_menu($menu_copy, $submenu_copy);
        }
        $this->remove_from_submenu($blocked, $submenu_copy);

        foreach($menu as $key=>$menu_item) {
            $key1 = $this->get_key_from_menu_copy($menu_item, $menu_copy);
            if ($key1===false) { // menu item does not found at menu copy
                continue;
            }
            $link = URE_Admin_Menu::normalize_link($menu_copy[$key1][3]);
            
            // some plugins like 'Yith WooCommerce wishlist' builds menu incorrectly - 
            // menus "Yith Plugins" with non-available capabilities (manage_options, install_plugins) stay available to the user.
            // Therefore we duplicate access checking here
            if (!current_user_can($menu_item[1])) {
                $this->update_menu($key, $menu_item[2], $submenu_copy, true);
                $this->blocked_urls[] = $link;
                continue;
            }
            
            $blocked_menu_item = false;
            $item_id1 = URE_Admin_Menu::calc_menu_item_id('menu', $link);
            $item_id2 = URE_Admin_Menu::calc_menu_item_id('submenu', $link);
            if ($blocked['access_model']==1) {
                if (in_array($item_id1, $blocked['data']) || in_array($item_id2, $blocked['data'])) {                    
                    $this->blocked_urls[] = $link;
                    $blocked_menu_item = true;
                }
            } elseif ($blocked['access_model']==2) {
                if (!in_array($item_id1, $blocked['data']) && !in_array($item_id2, $blocked['data'])) {                     
                    $this->blocked_urls[] = $link;
                    $blocked_menu_item = true;
                }
            }
            if ($blocked_menu_item || isset($this->submenu_modified[$menu_item[2]])) {
                $this->update_menu($key, $menu_item[2], $submenu_copy, $blocked_menu_item);
            }
        }
        
        $this->refresh_blocked_urls_transient();        
        $this->remove_duplicated_separators();        
        
    }
    // end of remove_blocked_menu_items()
                
    
    private function get_link_from_submenu_copy($subkey) {
        
        $submenu_copy = URE_Admin_Menu_Copy::get_submenu();
        foreach($submenu_copy as $sk=>$sm) {
            foreach($sm as $sk1=>$sm1) {
                if ($sm1[2]==$subkey) {
                    return $sm1[3];
                }
            }
        }
        
        return false;
    }
    // end of get_key_from_submenu_copy()
    
    
    private function get_first_available_menu_item($dashboard_allowed) {    
        global $menu;
        
        $menu_copy = URE_Admin_Menu_Copy::get_menu();
        $site_admin_url = admin_url();
                
        $available = '';
        foreach ($menu as $key=>$menu_item) {
            if (URE_Admin_Menu::is_separator($menu_item[4])) {
                continue;
            }
            $key1 = $this->get_key_from_menu_copy($menu_item, $menu_copy);
            if ($key1===false) { // menu item does not found at menu copy
                $link = $this->get_link_from_submenu_copy($menu_item[2]);
            } else {
                $link = $menu_copy[$key1][3];
            }
            if (empty($link)) {
                continue;
            }
            // in spite of all blocked URLs should be excluded from admin menu
            // make another check to exclude incidents with endless redirection loop
            if ( in_array( $link, $this->blocked_urls ) ) {
                continue;
            }
            if (strpos($link, $site_admin_url)===false) {
                $available = $site_admin_url . $link;
            } else {
                $available = $link;
            }
            break;            
        }

        if (empty($available)) {
            $available = get_option('siteurl');
            if ($dashboard_allowed) {
                $available = $site_admin_url;
            }            
        }
        
        return $available;        
    }
    // end of get_first_available_menu()
    
    
    /*
     * remove Welcome panel from the dashboard as 
     * it's not good to show direct links to WordPress functionality for restricted user
     */ 
    private function remove_welcome_panel($command, $blocked_data, $access_model) {
        if ($command!=='index.php') { 
            return;
        }
        
        $customize_hash = '71cf5c9f472f8adbfc847a3f71ce9f0e'; /* 'submenu'.'customize.php' */
        if (($access_model==1 && in_array($customize_hash, $blocked_data)) || 
            ($access_model==2 && !in_array($customize_hash, $blocked_data))) {
            remove_action('welcome_panel', 'wp_welcome_panel'); 
        }
        
    }
    // end of remove_welcome_panel()
    
    /**
     * Extract edit.php part from string like edit.php?arg1=val1&arg2=val2#anchor
     * 
     * @param string $command
     * @return string
     */
    private function get_php_command($command) {
        
        $question_pos = strpos($command, '?');
        if ($question_pos!==false) {
            $php_command = substr($command, 0, $question_pos);
        } else {
            $php_command = $command;
        }
        if (empty($php_command)) {
            $php_command = 'index.php';
        }
        
        return $php_command;
    }
    // end of get_php_command()


    // if URL argument encoded as an array element, e.g. param1[0], remove brackets part, leave just name, like 'param1'
    private function remove_brackets($arg) {
        $bracket_pos = strpos($arg, '%5b');
        if ($bracket_pos===false) {
            return $arg;
        }
        $arg = substr($arg, 0, $bracket_pos);
        
        return $arg;
    }
    // end of remove_brackets()
    
    private function extract_command_args($command) {
        $args = array();
        $args_pos = strpos($command, '?');
        if ($args_pos===false) {
            return $args;
        }
        $args_str = substr($command, $args_pos + 1);
        $args0 = explode('&amp;', $args_str);
        foreach($args0 as $arg0) {
            $arg1 = explode('=', $arg0);
            $arg_key = $this->remove_brackets($arg1[0]); 
            if (isset($arg1[1])) {                
                $args[$arg_key] = $arg1[1];
            } else {
                $args[$arg_key] = null;
            }            
        }
        
        return $args;
    }
    // end of extract_command_args()


    private function is_command_args_registered($command, $args_to_check) {

        $allowed_args = URE_Admin_Menu_URL_Allowed_Args::get($command);
        $page = $this->get_page();
        if (!isset($allowed_args[$page])) {
            return false;
        }
        
        foreach(array_keys($args_to_check) as $arg) {
            if ($arg==='page') {
                continue;
            }
            if (!in_array($arg, $allowed_args[$page])) {
                return false;
            }
        }
                    
        return true;
    }
    // end of is_command_args_registered()
    
    
    private function is_command_args_blocked($command, $args_to_check, $blocked_args) {
    
        $result = true;
        if (!empty($blocked_args)) {
            foreach($blocked_args as $key=>$value) {
                if (!isset($args_to_check[$key]) || $args_to_check[$key]!==$value) {
                    $result = false;
                    break;
                }
            }
        } else {
            if ($this->is_command_args_registered($command, $args_to_check)) {
                $result = false;
            }
        }
        
        return $result;
    }
    // end of is_command_args_blocked()

    
    private function is_exact_menu_command($command) {
        $menu_hashes = URE_Admin_Menu::get_menu_hashes();
        if (empty($menu_hashes)) {
            return false;
        }
        $command_list = array_keys($menu_hashes);
        foreach($command_list as $menu_link) {
            if (empty($menu_link)) {
                continue;
            }
            if ($menu_link===$command) {
                return true;
            }
        }
        
        return false;
    }
    // end of is_exact_menu_command()
    

    private function is_blocked_selected_menu_item($command) {
        
        $current_user_id = get_current_user_id();
        if ($current_user_id===0) {
            return false;
        }
        
        $data = get_transient(self::BLOCKED_URLS);
        if (empty($data) || !isset($data[$current_user_id])) {
            return false;
        }
        
        $blocked_urls = $data[$current_user_id];
        if (empty($blocked_urls)) {
            return false;
        }
        
        foreach($blocked_urls as $blocked_url) {
            if ($blocked_url===$command) {
                return true;
            }
        }
        
        if ($this->is_exact_menu_command($command)) {
            // it's unblocked and exactly matches to the command from the main menu
            return false;
        }
        
        foreach($blocked_urls as $blocked_url) {
            $php_command = $this->get_php_command($command);
            $blocked_php_command = $this->get_php_command($blocked_url);
            if ($php_command!==$blocked_php_command) {
                continue;
            }
            // compare command arguments with values together
            $command_args = $this->extract_command_args($command);
            $blocked_command_args = $this->extract_command_args($blocked_url);
            if ($this->is_command_args_blocked($php_command, $command_args, $blocked_command_args)) {
                return true;
            }
        }
        
        return false;
    }
    // end of is_blocked_selected_menu_item()    
    
    
    private function is_blocked_not_selected_menu_item($command) {
        
        $current_user_id = get_current_user_id();
        if ($current_user_id===0) {
            return false;
        }
        $data = get_transient(self::BLOCKED_URLS);
        if (empty($data) || !isset($data[$current_user_id])) {
            return false;
        }
        $blocked_urls = $data[$current_user_id];
        if (empty($blocked_urls)) {
            return false;
        }
        
        foreach($blocked_urls as $blocked_url) {
            if ($blocked_url===$command) {
                return true;
            }
            $php_command = $this->get_php_command($command);
            $blocked_php_command = $this->get_php_command($blocked_url);
            if ($php_command!==$blocked_php_command) {
                continue;
            }
            // compare command arguments names only
            $command_args = $this->extract_command_args($command);
            $blocked_command_args = $this->extract_command_args($blocked_url);
            if ($this->is_command_args_blocked($php_command, $command_args, $blocked_command_args)) {
                return true;
            }
        }
        
        return false;
    }
    // end of is_blocked_not_selected_menu_item()


    private function compare_links($menu_link, $command, $php_command, $exact_compare) {
        $result = false;
        if ($menu_link===$command) {
            $result = true;
        } elseif (!$exact_compare) {   //  just occurence is enough
            if (strpos($menu_link, $php_command)===0 || strpos($php_command, $menu_link)===0) {
                $result = true;
            }
        }            
        
        return $result;
    }
    // end of compare_strings()
    
    
    private function command_from_main_menu($command) {
        
        if (strpos($command, 'upload.php?mode=')!==false) { // this is command inside Media Library page
            return false;
        }
        
        $exact_compare = false;
        if (strpos($command, 'post-new.php?post_type=')!==false) {
            // command could be available just inside a page, like WooCommerce->Coupons->Add New : post-new.php?post_type=shop_coupon
            $exact_compare = true;  
        }
                
        $result = false;
        $php_command = $this->get_php_command($command);
        $menu_hashes = URE_Admin_Menu::get_menu_hashes();
        $command_list = array_keys($menu_hashes);
        foreach($command_list as $menu_link) {
            if (empty($menu_link)) {
                continue;
            }
            $result = $this->compare_links($menu_link, $command, $php_command, $exact_compare);
            if ($result) {
                break;
            }            
        }
        
        return $result;
    }
    // end of command_from_main_menu()
    
    
    /**
     * Restore link back from its extracted parts
     * @param string $php_command - PHP file name, like edit.php
     * @param array $args
     * @return string   - restored link like edit.php?post_type=page&amp;status=trash
     */
    private function build_link_back($php_command, $args) {
        
        $params = '';           
        foreach($args as $param=>$value) {
            if (!empty($params)) {
                $params .= '&amp;';
            }
            $params .= $param .'='. $value;
        }
        $link = $php_command .'?'. $params;
        
        return $link;
    }
    // end of build_link_back()
            

    private function get_page() {
        $page = $this->lib->get_request_var('page', 'get');
        if (empty($page)) {
            $page = '';
        }
        
        return $page;
    }
    // end of get_page()
                    
    
    /**
     * Remove additional parameters which may be added to command inside allowed page, like 
     * edit.php?post_status=trash&post_type=page
     * post-new.php?post_type=page&lang=de
     */
    private function normalize_command($command) {
                
        $php_command = $this->get_php_command($command);
        $allowed_args = URE_Admin_Menu_URL_Allowed_Args::get($php_command);
        if (empty($allowed_args)) {
            return $command;
        }
        $page = $this->get_page();
        if (!isset($allowed_args[$page])) {
            return $command;
        }
                                
        $updated = false;
        $command_args = $this->extract_command_args($command);
        $args = array_keys($command_args);
        foreach($args as $arg) {            
            if (!in_array($arg, $allowed_args[$page])) {
                continue;
            }
            $updated = true;
            unset($command_args[$arg]);
            if (empty($command_args)) {
                return $php_command;
            }                            
        }
        if (!$updated) {
            return $command;
        }
        
        $command = $this->build_link_back($php_command, $command_args);                
        
        return $command;
    }
    // end of normalize_command()    
        
    
    /**
     * Is this command from inside of the allowed page
     * @param string $menu_link - link from admin menu which should be allowed
     * @param string $command - link from browser (without host and path)
     * @param array $selected - list of hashes for the allowed (selected) admin menu items
     * @return boolean
     */
    private function is_command_allowed($menu_link, $command, $allowed_hash, $hash1, $hash2) {
                
        if ($hash1!==$allowed_hash && $hash2!==$allowed_hash) {
           // checked page is blocked
            return false;
        }
        if (!empty($command) && strpos($menu_link, $command)!==0) {
           // it's not command from the checked page - commands without additonal parameters should be equal
            return false;
        }
                    
        // this command is allowed, just contains additional parameters
        return true;
    }
    // end of is_command_allowed()
    
    
    private function exclusion_for_not_selected($command, $allowed) {
        
        if ($this->is_blocked_not_selected_menu_item($command)) {
            return false;
        }
        
        // if command was not selected but it does not match with any admin menu (submenu) item - do not block it
        if (!$this->command_from_main_menu($command)) {
            return true;
        }
        
        $n_command = $this->normalize_command($command);
        $menu_hashes = URE_Admin_Menu::get_menu_hashes();
        $menu_links = array_keys($menu_hashes);
        foreach($menu_links as $menu_link) {
            $hash1 = URE_Admin_Menu::calc_menu_item_id('menu', $menu_link);
            $hash2 = URE_Admin_Menu::calc_menu_item_id('submenu', $menu_link);
            
            foreach($allowed as $hash) {
                if ($this->is_command_allowed($menu_link, $n_command, $hash, $hash1, $hash2)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    // end of exclusions_for_not_selected()
    
    
    public function redirect_blocked_urls() {        
        
        $current_user = wp_get_current_user();
        if (empty($current_user->ID)) {
            return;
        }
        if ($this->lib->is_super_admin()) {
            return;
        }        
        
        $blocked = URE_Admin_Menu::load_data_for_user($current_user);
        if (empty($blocked['data'])) {
            return;
        }
        
        $command = $this->lib->extract_command_from_url( $_SERVER['REQUEST_URI'] );
        $item_id1 = URE_Admin_Menu::calc_menu_item_id('menu', $command);
        $item_id2 = URE_Admin_Menu::calc_menu_item_id('submenu', $command);        
        $command1 = strtolower($command);
        if ($blocked['access_model']==1) {  // block selected
            if (!(in_array($item_id1, $blocked['data']) || in_array($item_id2, $blocked['data']) ||
                  $this->is_blocked_selected_menu_item($command1))) {
            $this->remove_welcome_panel($command1, $blocked['data'], 1);
            return;
            }
        } elseif ($blocked['access_model']==2) {    // block not selected
            if (in_array($item_id1, $blocked['data']) || 
                in_array($item_id2, $blocked['data']))  { 
                $this->remove_welcome_panel($command1, $blocked['data'], 2);
                return;
            }          
            if ($this->exclusion_for_not_selected($command1, $blocked['data'])) {
                return;                
            }                                    
        }
          
        $url = $this->get_first_available_menu_item($command1!=='index.php');
        if (headers_sent()) {
?>
<script>
    document.location.href = '<?php echo $url; ?>';
</script>    
<?php
        } else {
            wp_redirect($url);
        }
        die;
    }
    // end of redirect_blocked_urls()

    
    /**
     * Return admin menu bar command string, 
     * but false for admin bar menu items which should be ignored
     * 
     * @param object $menu_item
     * @return boolean
     */
    protected function get_admin_menu_bar_command($menu_item) {
        
        if (empty($menu_item->href)) {
            return false;
        }
        
        $default_ignore_list = array(
            'about',
            'edit-profile',
            'logout',
            'my-account',
            'switch-back',
            'user-info'
        );
        $ignore_list = apply_filters('ure_do_not_remove_from_admin_bar', $default_ignore_list);
        if (in_array($menu_item->id, $ignore_list)) {
            return false;
        }
        
        $command = $this->lib->extract_command_from_url( $menu_item->href );
                
        return $command;
    }
    // end of get_admin_menu_bar_command()


    private function is_link_blocked($link) {
        
        $current_user = wp_get_current_user();        
        $blocked = URE_Admin_Menu::load_data_for_user($current_user);                
        $command = $this->lib->extract_command_from_url( $link );
        $item_id1 = URE_Admin_Menu::calc_menu_item_id('menu', $command);
        $item_id2 = URE_Admin_Menu::calc_menu_item_id('submenu', $command);        
        if ($blocked['access_model']==1) {  // block selected
            if (!(in_array($item_id1, $blocked['data']) || in_array($item_id2, $blocked['data']) ||
                  $this->is_blocked_selected_menu_item($command))) {
                  return false;
            }
        } elseif ($blocked['access_model']==2) {    // block not selected
            if (in_array($item_id1, $blocked['data']) || 
                in_array($item_id2, $blocked['data']))  { 
                return false;
            }          
            if ($this->exclusion_for_not_selected($command, $blocked['data'])) {
                return false;
            }                                    
        }   
        
        return true;
    }
    // end of is_link_blocked()
    
    
    /**
     * Copy of wp-includes/admin-bar.php::wp_admin_bar_my_sites_menu() function
     * Permissions control was added for access to the WP dasboard at the current site
     * @Important: compare with original code before every WordPress core update
     * 
     * @return void
     */
    public function wp_admin_bar_my_sites_menu($wp_admin_bar) {        
        
        // Don't show for logged out users or single site mode.
        if (!is_user_logged_in() || !is_multisite()) {
            return;
        }

        // Show only when the user has at least one site, or they're a super admin.
        if (count($wp_admin_bar->user->blogs) < 1 && !is_super_admin()) {
            return;
        }

        if ($wp_admin_bar->user->active_blog) {
            $my_sites_url = get_admin_url($wp_admin_bar->user->active_blog->blog_id, 'my-sites.php');
        } else {
            $my_sites_url = admin_url('my-sites.php');
        }

        $wp_admin_bar->add_menu(array(
            'id' => 'my-sites',
            'title' => __('My Sites'),
            'href' => $my_sites_url,
        ));

        if (is_super_admin()) {
            $wp_admin_bar->add_group(array(
                'parent' => 'my-sites',
                'id' => 'my-sites-super-admin',
            ));

            $wp_admin_bar->add_menu(array(
                'parent' => 'my-sites-super-admin',
                'id' => 'network-admin',
                'title' => __('Network Admin'),
                'href' => network_admin_url(),
            ));

            $wp_admin_bar->add_menu(array(
                'parent' => 'network-admin',
                'id' => 'network-admin-d',
                'title' => __('Dashboard'),
                'href' => network_admin_url(),
            ));
            $wp_admin_bar->add_menu(array(
                'parent' => 'network-admin',
                'id' => 'network-admin-s',
                'title' => __('Sites'),
                'href' => network_admin_url('sites.php'),
            ));
            $wp_admin_bar->add_menu(array(
                'parent' => 'network-admin',
                'id' => 'network-admin-u',
                'title' => __('Users'),
                'href' => network_admin_url('users.php'),
            ));
            $wp_admin_bar->add_menu(array(
                'parent' => 'network-admin',
                'id' => 'network-admin-t',
                'title' => __('Themes'),
                'href' => network_admin_url('themes.php'),
            ));
            $wp_admin_bar->add_menu(array(
                'parent' => 'network-admin',
                'id' => 'network-admin-p',
                'title' => __('Plugins'),
                'href' => network_admin_url('plugins.php'),
            ));
            $wp_admin_bar->add_menu(array(
                'parent' => 'network-admin',
                'id' => 'network-admin-o',
                'title' => __('Settings'),
                'href' => network_admin_url('settings.php'),
            ));
        }

        // Add site links
        $wp_admin_bar->add_group(array(
            'parent' => 'my-sites',
            'id' => 'my-sites-list',
            'meta' => array(
                'class' => is_super_admin() ? 'ab-sub-secondary' : '',
            ),
        ));

        $current_blog_id = get_current_blog_id();
        foreach ((array) $wp_admin_bar->user->blogs as $blog) {
            switch_to_blog($blog->userblog_id);
            $blog_id = get_current_blog_id();
            $blavatar = '<div class="blavatar"></div>';

            $blogname = $blog->blogname;

            if (!$blogname) {
                $blogname = preg_replace('#^(https?://)?(www.)?#', '', get_home_url());
            }

            $menu_id = 'blog-' . $blog->userblog_id;

            $wp_admin_bar->add_menu(array(
                'parent' => 'my-sites-list',
                'id' => $menu_id,
                'title' => $blavatar . $blogname,
                'href' => admin_url(),
            ));
            

            $link = admin_url();    // added
            if (current_user_can('read') && !$this->is_link_blocked($link)) {
                $wp_admin_bar->add_menu(array(
                    'parent' => $menu_id,
                    'id' => $menu_id . '-d',
                    'title' => __('Dashboard'),
                    'href' => $link,
                ));
            }
            
            $pto = get_post_type_object('post');
            $link = admin_url('post-new.php');
            if (current_user_can($pto->cap->create_posts) && !$this->is_link_blocked($link)) {                                
                $wp_admin_bar->add_menu(array(
                    'parent' => $menu_id,
                    'id' => $menu_id . '-n',
                    'title' => __('New Post'),
                    'href' => $link,
                ));
            }

            $link = admin_url('edit-comments.php');
            if (current_user_can($pto->cap->edit_posts) && !$this->is_link_blocked($link)) {
                $wp_admin_bar->add_menu(array(
                    'parent' => $menu_id,
                    'id' => $menu_id . '-c',
                    'title' => __('Manage Comments'),
                    'href' => $link,
                ));
            }

            $wp_admin_bar->add_menu(array(
                'parent' => $menu_id,
                'id' => $menu_id . '-v',
                'title' => __('Visit Site'),
                'href' => home_url('/'),
            ));

        }
        $this->lib->restore_after_blog_switching($current_blog_id);
        
    }
    // end of wp_admin_bar_my_sites_menu()
        

    public function replace_wp_admin_bar_my_sites_menu() {
        $multisite = $this->lib->get('multisite');
        if (is_admin() || !$multisite || is_super_admin()) {
            return;
        }
        
        remove_action('admin_bar_menu', 'wp_admin_bar_my_sites_menu', 20);
        add_action('admin_bar_menu', array($this, 'wp_admin_bar_my_sites_menu'), 20);
        
    }
    // end of replace_wp_admin_bar_my_sites_menu()
            
    
    /**
     * Returns true if menu item is a child of 'My Sites' menu at admin top menu bar
     * @param string $parent
     * @return boolean
     */
    private function is_my_sites_child($parent) {
     
        if (empty($parent)) {
            return false;
        }
        if ($parent=='my-sites-list' || strpos($parent, 'blog-')===0) {
            return true;
        }
        
        return false;
    }
    // end of is_my_sites_child()

    /**
     * Special support (Customize menu and menus created by plugins): if menu is blocked for the role, block it at top admin menu bar too
     * 
     * @global array $wp_admin_bar
     * @param  array $blocked
     */
    private function remove_admin_bar_menu_for_blocked_menu_specials($blocked) {
        global $wp_admin_bar;
        
        $admin_bar0 = array(
            'Customize'=>array(
                'type'=>'submenu', // 71cf5c9f472f8adbfc847a3f71ce9f0e - md5('submenu'.'customize.php');
                'url'=>'customize.php',
                'wp_id'=>'customize'
            ),
            'Yoast SEO'=>array(
                'type'=>'menu',   // e960550080acc7b8154fddae02b72542 - md5('menu'.'admin.php?page=wpseo_dashboard');
                'url'=>'admin.php?page=wpseo_dashboard',
                'wp_id'=>'wpseo-menu'
            ),            
            'UpDraftPlus'=>array(
                'type'=>'submenu',   // 65a42f8ea41f40edd4b652f10dd7a457 - md5('submenu'. 'options-general.php?page=updraftplus');    
                'url'=>'options-general.php?page=updraftplus',
                'wp_id'=>'updraft_admin_node'
            ),
            'WP Fastest Cache'=>array(
                'type'=>'menu',  // 723a7922230d5ad5c31c6708d5a7a620' - md5('menu' .'admin.php?page=wpfastestcacheoptions');
                'url'=>'admin.php?page=wpfastestcacheoptions',
                'wp_id'=>'wpfc-toolbar-parent'
            ),
        );
        $admin_bar = apply_filters('ure_admin_menu_access_admin_bar', $admin_bar0);
        
        foreach($admin_bar as $menu) {
            $hash = URE_Admin_Menu::calc_menu_item_id($menu['type'], $menu['url']);
            if (($blocked['access_model']==1 && in_array($hash, $blocked['data'])) ||
                ($blocked['access_model']==2 && !in_array($hash, $blocked['data'])) ) {
                $wp_admin_bar->remove_menu($menu['wp_id']);
            }
        }
    
    }
    // end of remove_admin_bar_menu_for_blocked_menu_specials()

    
    /**
     * 
     * @global type $wp_admin_bar
     * @return void
     */
    public function modify_admin_menu_bar() {
        global $wp_admin_bar;
                
        if (empty($wp_admin_bar)) {
            return;
        }
        
        $nodes = $wp_admin_bar->get_nodes();
        if (empty($nodes)) {
            return;
        }
        
        if ($this->lib->is_super_admin()) {
            return;
        }        
        
        // remove 'SEO' menu from top bar
        if (!current_user_can('manage_options')) {
            $wp_admin_bar->remove_menu('wpseo-menu');
        } 
        
        $current_user = wp_get_current_user();
        $blocked = URE_Admin_Menu::load_data_for_user($current_user);
        if (empty($blocked)) {
            echo 'Wow!!!'.PHP_EOL;
            return;
        }                
        
        // Special support: if menu is blocked for the role, block it at top admin menu bar too
        $this->remove_admin_bar_menu_for_blocked_menu_specials($blocked);
        
        foreach($nodes as $key=>$menu_item) {
            $command = $this->get_admin_menu_bar_command($menu_item);
            if (empty($command)) {
                continue;
            }
            
            if ($this->is_my_sites_child($menu_item->parent) || $menu_item->id=='edit') {
                continue;
            }
            
            $item_id1 = URE_Admin_Menu::calc_menu_item_id('menu', $command);
            $item_id2 = URE_Admin_Menu::calc_menu_item_id('submenu', $command);
            
            if ($blocked['access_model']==1) {  // block selected
                if (in_array($item_id1, $blocked['data'])) {
                    $wp_admin_bar->remove_menu($menu_item->id);
                } elseif (in_array($item_id2, $blocked['data'])) {
                    $wp_admin_bar->remove_node($menu_item->id);
                }
            } elseif ($blocked['access_model']==2) {    // block not selected
                if (!in_array($item_id1, $blocked['data']) && !in_array($item_id2, $blocked['data'])) {
                    $wp_admin_bar->remove_menu($menu_item->id);                
                }
            }
        }
                
    }
    // end of modify_admin_menu_bar()
    
    
    public function block_media_upload($strings) {
                        
        if ($this->lib->is_super_admin()) {
            return $strings;
        }

        $current_user = wp_get_current_user();
        $blocked = URE_Admin_Menu::load_data_for_user($current_user);
        if (empty($blocked)) {
            return $strings;
        }
        
        if ($blocked['access_model']==1) {  // block selected
            foreach($blocked['data'] as $menu_hash) {
                if ($menu_hash=='a6d96d2991e9d58c1d04ef3c2626da56') {  // Media -> Add New
                    // Undocumented trick to remove "Upload Files" tab at the Post Editor "Add Media" popup window 
                    // Be aware - it may stop working with next version of WordPress
                    unset($strings['uploadFilesTitle']);    
                    break;
                }
            }
        } else {    // block not selected
            $selected = false;
            foreach($blocked['data'] as $menu_hash) {
                if ($menu_hash=='a6d96d2991e9d58c1d04ef3c2626da56') {  // Media -> Add New
                    $selected = true;
                    break;
                }
            }
            if (!$selected) {
                unset($strings['uploadFilesTitle']);    
            }
        }
                        
        return $strings;
    }
    // end of block_media_upload()
    
}
// end of URE_Admin_Menu_Access class
