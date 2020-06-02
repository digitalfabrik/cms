<?php

/*
 * Admin menu hashes processing class
 * Project: User Role Editor Pro
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Convert blocked menu items hashes from version 4.15 to version 4.22+ using changed format
 *
 * @author Vladimir Garagulya
 */
class URE_Admin_Menu_Hashes {
    
    const TASK_ID = 'ure_admin_menu_access_data_conversion';
    
    /**
     * Menu item ID calculator for versions from 4.15 up to 4.21.1
     * 
     * @param string $menu_kind
     * @param string $menu_item
     * @return string
     */
    public static function calc_menu_item_id_old($menu_kind, $menu_item) {
        
        if (strpos($menu_item, 'customize.php')!==false) {
            $menu_item = 'customize.php';   // use only the permanent part of the Customize menu item, as the return page value (url) is varied
        } elseif (strpos($menu_item, 'themes.php?page=custom-header')!==false) {
            $menu_item = 'custom-header';
        } elseif (strpos($menu_item, 'themes.php?page=custom-background')!==false) {
            $menu_item = 'custom-background';
        }        
        
        $item_id = md5($menu_kind . $menu_item);
        
        return $item_id;
    }
    // end of calc_menu_item_id_old()
    
        
    private static function add_value($data, $value) {
        
        if (!in_array($value, $data)) {
            $data[] = $value;
        }
        
        return $data;
    }
    // end of add_hash
    
    private static function update_hash($prefix, $menu_item, &$menu_access_data) {
        
        $customize_hash = '71cf5c9f472f8adbfc847a3f71ce9f0e';
        $old_hash = self::calc_menu_item_id_old($prefix, $menu_item[2]);                    
        foreach($menu_access_data as $role=>$access_data_for_role) {
            if (!isset($access_data_for_role['data'])) {
                continue;
            }            
            $data = $access_data_for_role['data'];
            
            for($i=0; $i<count($data); $i++) {
                if (!isset($data[$i])) {
                    continue;
                }
                if ($data[$i]==$old_hash) {
                    $menu_access_data[$role]['data'][$i] = URE_Admin_Menu::calc_menu_item_id($prefix, $menu_item[3]);
                    if($old_hash===$customize_hash) {  // if top menu item 'Customize' blocked, mark as blocked all other related menu items
                        $menu_access_data[$role]['data'] = self::add_value($menu_access_data[$role]['data'], $customize_hash);  // Appearance - Customize - customize.php
                        $menu_access_data[$role]['data'] = self::add_value($menu_access_data[$role]['data'], '2aac623bfe5d573d5987568d7528e50d'); // Header - customize.php
                        $menu_access_data[$role]['data'] = self::add_value($menu_access_data[$role]['data'], '1f487197d7512a85f7ef509e3a6471bd'); // Background - customize.php
                        $menu_access_data[$role]['data'] = self::add_value($menu_access_data[$role]['data'], 'e07dad6171f87801da401de77966ff87'); // Header - themes.php?page=custom-header
                        $menu_access_data[$role]['data'] = self::add_value($menu_access_data[$role]['data'], '5e52942b3efe461d923740a27c075872'); // Backgroudn - themes.php?page=custom-background
                    }
                }                
            }                        

        }   // foreach()
        
    }
    // end of update_hash()
    
    
    private static function convert_site() {
        
        URE_Admin_Menu_Copy::update();
        
        $menu_access_data = get_option(URE_Admin_Menu::ACCESS_DATA_KEY);
        if (empty($menu_access_data)) { // nothing to update
            return;
        }
        
        $current_menu = get_option(URE_Admin_Menu::ADMIN_MENU_COPY_KEY);                
        foreach($current_menu as $menu_item) {
            self::update_hash('menu', $menu_item, $menu_access_data);
        } 
        $current_submenu = get_option(URE_Admin_Menu::ADMIN_SUBMENU_COPY_KEY);
        foreach($current_submenu as $submenu) {
            foreach($submenu as $menu_item) {
                self::update_hash('submenu', $menu_item, $menu_access_data);
            }
        }
        update_option(URE_Admin_Menu::ACCESS_DATA_KEY, $menu_access_data);        
    }
    // end of convert_site()
    
    
    private static function remove_task() {
        $task_queue = URE_Task_Queue::get_instance();
        $task_queue->remove(self::TASK_ID);
    }
    // end of remove_task()
    
    
    /**
     * Update admin menu data from versions earlier than 4.15
     */
    public static function convert() {

        $data_version = get_option(URE_Admin_Menu::DATA_VERSION_KEY, 0);
        if ($data_version>=URE_Admin_Menu::DATA_VERSION) {
            self::remove_task();
            return;
        }        
        
        self::convert_site();
        
        update_option(URE_Admin_Menu::DATA_VERSION_KEY, URE_Admin_Menu::DATA_VERSION, true);
        self::remove_task();
        
    }
    // end of convert()
    
    
    private static function is_conversion_needed() {
        $lib = URE_Lib_Pro::get_instance();
        $activate_admin_menu_access_module = $lib->get_option('activate_admin_menu_access_module', false);
        if (empty($activate_admin_menu_access_module)) {
            return false;
        }
        
        $data_version = get_option(URE_Admin_Menu::DATA_VERSION_KEY, 0);
        if ($data_version>=URE_Admin_Menu::DATA_VERSION) {
            return false;
        }
        
        return true;
    }
    // end of is_conversion_needed()
    
    
    /**
     * Add conversion data task to the URE's tasks queue for the later execution by 'admin_menu' action
     * 
     * @param URE_Task_Queue $task_queue
     */
    private static function require_site_data_conversion($task_queue) {
        
        $args = array('action'=>'admin_menu', 'routine'=>'URE_Admin_Menu_Hashes::convert', 'priority'=>99);
        $task_queue->add(self::TASK_ID, $args);
        
    }
    // end of require_site_data_conversion()
        
            
    /**
     * Convert admin menu data if we update from an older URE Pro version     
     */
    public static function require_data_conversion() {
        global $wpdb;
        
        $task_queue = URE_Task_Queue::get_instance();
        $lib = URE_Lib_Pro::get_instance();  
        $multisite = $lib->get('multisite');
        if ($multisite) {            
            $old_blog = $wpdb->blogid;
            $blog_ids = $lib->get_blog_ids();
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                if (self::is_conversion_needed()) {
                    $task_queue->reinit();  // re-read queue data from the current blog
                    self::require_site_data_conversion($task_queue);
                }
            }
            $lib->restore_after_blog_switching($old_blog);
        } elseif (self::is_conversion_needed()) {
            self::require_site_data_conversion($task_queue);
        }
                                               
    }
    // end of require_data_conversion()        
    
}
// end of URE_Admin_Menu_Hashes class