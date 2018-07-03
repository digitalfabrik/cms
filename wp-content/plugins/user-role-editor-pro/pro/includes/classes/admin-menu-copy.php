<?php
/*
 * User Role Editor Pro WordPress plugin
 * Class URE_Admin_Menu_Copy - creates/stores updated copy of WP admin backend menu for use in admin menu access add-on
 * Code was built on the base of wp-admin/menu-header.php
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://role-editor.com
 * License: GPL v2+ 
 */
class URE_Admin_Menu_Copy {

    const FORCE_ADMIN_MENU_COPY_UPDATE = 'force_admin_menu_copy_update';
    
    private static $admin_is_parent = false;
    
    
    private static function get_menu_hook($menu_key, $submenu_item) {
        
        if (!empty($submenu_item) && isset($submenu_item[2])) {
            $menu_hook = get_plugin_page_hook($submenu_item[2], $menu_key);
        } else {
            $menu_hook = '';
        }
        
        return $menu_hook;
    }
    // end of get_menu_hook()
    
    
    private static function get_menu_file($submenu_item) {
        if (!empty($submenu_item) && isset($submenu_item[2])) {
            $menu_file = $submenu_item[2];
            $pos = strpos($menu_file, '?');
            if ($pos!==false) {
                $menu_file = substr($menu_file, 0, $pos);
            }
        } else {
            $menu_file = '';
        }
        
        return $menu_file;
    }
    // end of get_menu_file()
        
    
    private static function get_menu_item_link($menu_key, $submenu_as_parent = true) {
        
        global $submenu;
        
        $link = '';
        $submenu_item = '';
        if (!empty($submenu[$menu_key])) {			
            $submenu_items = $submenu[$menu_key];
            $submenu_item = reset($submenu_items);  // get 1st element of array
        }
        
        $menu_hook = self::get_menu_hook($menu_key, $submenu_item);
        $menu_file = self::get_menu_file($submenu_item);        
        if ($submenu_as_parent && !empty($submenu_item) && isset($submenu_item[2])) {
            if (!empty($menu_hook) || 
                (('index.php'!=$submenu_item[2]) && file_exists(WP_PLUGIN_DIR .'/'. $menu_file) && 
                 !file_exists(ABSPATH .'/wp-admin/'. $menu_file))) {
                self::$admin_is_parent = true;
                $link = 'admin.php?page='. $submenu_item[2];
            } else {
                $link = $submenu_item[2];
            }
        } elseif (!empty($menu_key)) {
            $menu_hook = get_plugin_page_hook( $menu_key, 'admin.php');
            $menu_file = $menu_key;
            $pos = strpos($menu_file, '?');
            if (false!==$pos) {
                $menu_file = substr($menu_file, 0, $pos);
            }
            if (!empty($menu_hook) || 
                (('index.php'!=$menu_key ) && file_exists(WP_PLUGIN_DIR .'/'. $menu_file) && 
                 !file_exists(ABSPATH .'/wp-admin/'. $menu_file))) {                
                self::$admin_is_parent = true;
                $link = 'admin.php?page='. $menu_key;
            } else {
                $link = $menu_key;
            }
        }

        $normalized_link = URE_Admin_Menu::normalize_link($link);
        
        return $normalized_link;
    }
    // end of get_menu_link()
       
    
    private static function get_submenu_item_link($submenu_key, $menu_key) {
        
        $link = '';
        $menu_file = $menu_key;
        $pos = strpos($menu_file, '?');
        if ($pos!==false) {
            $menu_file = substr($menu_file, 0, $pos);
        }

        $menu_hook = get_plugin_page_hook($submenu_key, $menu_key);
    				$sub_file = $submenu_key;
        $pos = strpos($sub_file, '?');
        if ($pos!==false) {
            $sub_file = substr($sub_file, 0, $pos);
        }
        if (!empty($menu_hook) || 
            (('index.php'!=$submenu_key) && file_exists(WP_PLUGIN_DIR .'/'. $sub_file) && 
              !file_exists(ABSPATH .'/wp-admin/'. $sub_file))) {
            // If admin.php is the current page or if the parent exists as a file in the plugins or admin dir
            if ((!self::$admin_is_parent && file_exists(WP_PLUGIN_DIR .'/'. $menu_file) && 
                 !is_dir(WP_PLUGIN_DIR .'/'. $menu_key)) || file_exists($menu_file)) {
                $link = add_query_arg(array('page' => $submenu_key), $menu_key);
            } else {
                $link = add_query_arg(array('page' => $submenu_key), 'admin.php');
            }
            $link = esc_url($link, array('http', 'https'), 'internal');
            $link = str_replace('&', '&amp;', $link);
        } else {
            $link = $submenu_key;
        }
        
        $normalized_link = URE_Admin_Menu::normalize_link($link);
        
        return $normalized_link;
    }
    // end of get_submenu_item_link()
    
    
    public static function force_update() {
        
        $lib = URE_Lib_Pro::get_instance();
        $lib->put_option(self::FORCE_ADMIN_MENU_COPY_UPDATE, 1, true);
    }
    // end of force_update()

    /**
     * Remove from submenu copy any submenu which is not linked to the main menu item
     * 
     * @param array $menu_copy
     * @param array $submenu_copy
     * @return array
     */
    private static function cleanup_submenu($menu_copy, $submenu_copy) {
        
        foreach(array_keys($submenu_copy) as $submenu_key) {
            $found = false;
            foreach($menu_copy as $menu_item) {
                if ($menu_item[2]==$submenu_key) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {  //  Submenu was not linked to any available menu, remove it 
                unset($submenu_copy[$submenu_key]);
            }
        }
        
        return $submenu_copy;
    }
    // end of cleanup_submenu()

    
    /**
     * Save current WordPress admin menu for future use via AJAX requests, when menu is not available
     * 
     */
    public static function update() {
        global $menu, $submenu, $pagenow;
        
        $lib = URE_Lib_Pro::get_instance();
        $force_update = $lib->get_option(self::FORCE_ADMIN_MENU_COPY_UPDATE);
        $menu_copy = get_option(URE_Admin_Menu::ADMIN_MENU_COPY_KEY);                
        if ( !($force_update || empty($menu_copy) || ($pagenow==='users.php' && isset($_GET['page']) && $_GET['page']==='users-user-role-editor-pro.php')) ) {  
            // Update menu copies only when they were not created yet or every time when user opens User Role Editor page
            return;
        }
        
        $menu_hashes = array();        
        $menu_copy = $menu;
        $submenu_copy = self::cleanup_submenu($menu_copy, $submenu);        
        
        foreach($menu_copy as $key=>$menu_item) {
            self::$admin_is_parent = false;
            if (URE_Admin_Menu::is_separator($menu_item[4])) {   // do not include separators
                unset($menu_copy[$key]);
                continue;
            }
            for($i=3; $i<count($menu_item); $i++) {
                unset($menu_copy[$key][$i]);
            }
            
            $menu_key = $menu_item[2];
            $link = self::get_menu_item_link($menu_key);
            $menu_copy[$key][3] = $link;
            $menu_hashes[$link] = 1;
            
            if (empty($submenu[$menu_key])) {    // Menu does not have submenu
                continue;
            }
            
            // Go through submenu and build submenu item link
            foreach($submenu_copy[$menu_key] as $key1=>$items) {
                if (isset($items[2])) {
                    $link = self::get_submenu_item_link($items[2], $menu_key);
                } else {
                    $link = '';
                }
                $submenu_copy[$menu_key][$key1][3] = $link;
                $menu_hashes[$link] = 1;
            }
        }              
        uksort($menu_copy, "strnatcasecmp"); // make it all pretty: wp-admin/includes/menu.php, line #241
        
        update_option(URE_ADMIN_MENU::ADMIN_MENU_COPY_KEY, $menu_copy, false);
        update_option(URE_ADMIN_MENU::ADMIN_SUBMENU_COPY_KEY, $submenu_copy, false);
        update_option(URE_ADMIN_MENU::ADMIN_MENU_HASHES, $menu_hashes);
        if ($force_update) {    // clear "force update" flag
            $lib->put_option(self::FORCE_ADMIN_MENU_COPY_UPDATE, 0, true);
        }
        
    }
    // end of update_menu_copy()
    
    
    private static function get_menu_index($title, $menu_copy) {
        
        $ind = false;
        foreach($menu_copy as $key=>$item) {
            if ($item[0]===$title) {
                $ind = $key;
                break;
            }
        }
        
        return $ind;
    }
    // end of get_menu_index()
    
    
    public static function get_menu() {
        $menu_copy = get_option(URE_Admin_Menu::ADMIN_MENU_COPY_KEY);
        if (!current_user_can('list_users')) {
            $menu_copy[70] = array( __('Profile'), 'read', 'profile.php', 'profile.php');
        }
        if (!current_user_can('manage_options')) {
            if (URE_Plugin_Presence::is_active('visual-composer')) {
                $ind = self::get_menu_index('Visual Composer', $menu_copy);
                $menu_copy[$ind][2] = 'vc-welcome';
                $menu_copy[$ind][3] = 'admin.php?page=vc-welcome';
            }
        }
        
        return $menu_copy;
    }
    // end of get_menu()
    
    
    public static function get_submenu() {
        $submenu_copy = get_option(URE_Admin_Menu::ADMIN_SUBMENU_COPY_KEY);
        if (!current_user_can('list_users')) {
            $menu_copy[70] = array( __('Profile'), 'read', 'profile.php', 'profile.php');
            unset($submenu_copy['users.php']);
            $submenu_copy['profile.php'][5] = array(__('Your Profile'), 'read', 'profile.php', 'profile.php');
        }
        if (current_user_can('create_users')) {
            $submenu_copy['profile.php'][10] = array(__('Add New User'), 'create_users', 'user-new.php', 'user-new.php');
        } else {
            $submenu_copy['profile.php'][10] = array(__('Add New User'), 'promote_users', 'user-new.php', 'user-new.php');
        }
        
        return $submenu_copy;
    }
    // end of get_submenu()
    
}
// end of Admin_Menu_Copy class