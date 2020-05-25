<?php

/*
 * User Role Editor WordPress plugin
 * Class URE_Admin_Menu_URL_Allowed_Args - support stuff for Amin Menu Access add-on
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Admin_Menu_URL_Allowed_Args {
    
    const ARGS_DATA_KEY = 'ure_admin_menu_allowed_args_data';
    
    static private function get_for_supported_plugins(&$args, $plugins, $page) {
        foreach($plugins as $plugin) {
            if (!URE_Plugin_Presence::is_active($plugin)) {
                continue;
            }
            $file = URE_PLUGIN_DIR .'pro/includes/classes/supported_plugins/admin-menu-'. $plugin .'-args.php';
            if (!file_exists($file)) {
                continue;
            }
            require_once($file);
            $method = 'get_for_'. $page;
            $plugin_id = str_replace(' ', '_', ucwords(str_replace('-', ' ', $plugin) ) );
            $class = 'URE_Admin_Menu_'. $plugin_id .'_Args';
            if (method_exists($class, $method)) {
                //$args = $class::$method($args); // for PHP version 5.3+
                $args = call_user_func(array($class, $method), $args);  // for PHP veriosn <5.3
            }
        }
    }
    // end of get_for_supported_plugins()
    
    
    static private function get_for_edit() {
        $args = array(
                ''=>array(
                    'post_type',
                    'post_status', 
                    'orderby',
                    'order',
                    's',                    
                    'action',
                    'm',
                    'cat',
                    'filter_action',
                    'paged',
                    'action2',
                    'author',
                    'all_posts',
                    'trashed',
                    'ids',
                    'untrashed',
                    'deleted',
                    'category_name',
                    'tag'
                )  
            );
    
        $plugins = array(
            'download-monitor',
            'eventon',
            'ninja-forms',
            'woocommerce',
            'wpml'
            );
        self::get_for_supported_plugins($args, $plugins, 'edit');
        
        return $args;
    }
    // end of get_for_edit()

    
    static private function get_for_edit_tags() {
        $args = array(
                ''=>array(
                    'action',
                    'tag_id',
                    'taxonomy',
                    'order',
                    'orderby',
                    'post_type',
                    's',
                    '_wpnonce'
              )  
            );
/*    
        $plugins = array(
            );
        self::get_for_supported_plugins($args, $plugins, 'edit_comments');
*/        
        return $args;
    }
    // end of get_for_edit_tags()
    
    
    static private function get_for_edit_comments() {
        $args = array(
                ''=>array(
                    'comment_status',
              )  
            );
/*    
        $plugins = array(
            );
        self::get_for_supported_plugins($args, $plugins, 'edit_comments');
*/        
        return $args;
    }
    // end of get_for_edit_comments()

    
    static private function get_for_post_new() {
    
        $args = array(''=>array('post_type'));
        $plugins = array('wpml');
        self::get_for_supported_plugins($args, $plugins, 'post_new');
        
        return $args;
    }
    // end of get_args_for_post_new()
    
    
    static private function get_for_upload() {
        
        $args = array(''=>array('mode', 'paged'));
        $plugins = array('enable-media-replace');
        self::get_for_supported_plugins($args, $plugins, 'upload');
        
        return $args;
    }
    // end of get_for_upload()
                
    
    static private function get_for_nav_menus() {
        
        $args = array(''=>array(
            'action',
            'menu'
            ));
        
        return $args;
    }
    // end of get_for_nav_menus()
    
    
    static private function get_for_users() {
        
        $args = array(''=>array(
            's',
            'action',
            'new_role',
            'paged',
            'action2',
            'new_role2',
            'orderby',
            'order',
            'role',
            'user',
            'delete_count',
            'update',
            '_wpnonce'
            ));
        $plugins = array('ultimate-member');
        self::get_for_supported_plugins($args, $plugins, 'users');
        
        return $args;
    }
    
				
    static	private	function	get_for_tools()	{

								$args	=	array(''	=>	array());

								return	$args;
				}
				// end of get_for_tools()
    
    
    static	private	function	get_for_settings()	{

        $plugins = array(
            'wp-mail-smtp'
        );
								$args	=	array();
        self::get_for_supported_plugins($args, $plugins, 'settings');

								return	$args;
				}
				// end of get_for_settings()


				static private function get_for_admin() {
                
        $plugins = array(
            'contact-form-7',
            'global-content-blocks',
            'gravity-forms',
            'ninja-forms',
            'unitegallery',
            'wpml'            
            );
        $args = array();        
        self::get_for_supported_plugins($args, $plugins, 'admin');
        
        return $args;
    }
    // end of get_for_admin()

    
    static public function update_white_list() {
        
        $lib = URE_Lib_Pro::get_instance();
        $base_url = $lib->get_request_var('base_url', 'post');
        if (empty($base_url)) {
            $error_message = esc_html__('Wrong request: Missed base URL', 'user-role-editor');
            return array('result'=>'error', 'message'=>$error_message);
        }
        
        $url_args = $lib->get_request_var('allowed_args', 'post');
        
        $args_data = get_option(self::ARGS_DATA_KEY);
        if (empty($args_data)) {
            $args_data = array();
        }
        if (empty($url_args)) {
            if (isset($args_data[$base_url])) {
                unset($args_data[$base_url]);
            }
        } else {
            $args_data[$base_url] = $url_args;
        }
        update_option(self::ARGS_DATA_KEY, $args_data);
        
        $success_message = esc_html__('Allowed arguments list was updated successfully', 'user-role-editor');
        
        return array('result'=>'success', 'message'=>$success_message);    
    }
    // end of update_white_list()
    
    
    static public function load_white_list() {
    
        $args_data = get_option(self::ARGS_DATA_KEY, array());
        
        return $args_data;
    }
    // end of load_white_list()
    
    
    // extract arguments value from CSV string to array
    static public function extract_csv($args_str) {
        if (empty($args_str)) {
            return array();
        }
        
        $args = explode(',', $args_str);
        // trim resulted array values
        foreach($args as $key=>$value) {
            $args[$key] = trim($value);
        }
        
        return $args;
    }
    // end of extract()
    
    
    static private function extract_page_from_command($command) {
        
        $page = '';
        $args_pos = strpos($command, '?');
        if ($args_pos===false) {
            return $page;
        }
        $args_str = substr($command, $args_pos + 1);
        $args = explode('&amp;', $args_str);
        foreach($args as $arg) {
            $data = explode('=', $arg);
            if (isset($data[0]) && $data[0]=='page') {
                $page = trim($data[1]);
                break;
            }
        }
        
        return $page;
    }
    // end of extract_page_from_command()
    
    
    static public function add_args_from_white_list($args) {
        
        $wl_args = self::load_white_list();
        if (empty($wl_args)) {
            return $args;
        }
        $commands = array_keys($args);
        foreach($commands as $command) {
            foreach($wl_args as $wl_base_url=>$wl_args_str) {
                if (strpos($wl_base_url, $command)!==0) {
                    continue;
                }
                $wl_args_list = self::extract_csv($wl_args_str);
                $page = self::extract_page_from_command($wl_base_url);
                if (isset($args[$command][$page]) && is_array($args[$command][$page])) {
                    $args[$command][$page] = array_merge($args[$command][$page], $wl_args_list);
                } else {
                    $args[$command][$page] = $wl_args_list;
                }
                $args[$command][$page] = array_merge($args[$command][$page], $wl_args_list);
            }
        }
                
        return $args;
    }
    // end of add_args_from_white_list()
    
    
    static public function get($command) {
        
        $edit = self::get_for_edit();
        $edit_tags = self::get_for_edit_tags();
        $edit_comments = self::get_for_edit_comments();
        $post_new = self::get_for_post_new();                
        $upload = self::get_for_upload();
        $nav_menus = self::get_for_nav_menus();
        $users = self::get_for_users();
								$tools = self::get_for_tools();
        $settings = self::get_for_settings();
        $admin = self::get_for_admin();
        
        $args0 = array(
            'edit.php'=>$edit,  
            'edit-tags.php'=>$edit_tags,
            'edit-comments.php'=>$edit_comments,
            'post-new.php'=>$post_new,            
            'upload.php'=>$upload,
            'nav-menus.php'=>$nav_menus,
            'users.php'=>$users,
												'tools.php'=>$tools,
            'options-general.php'=>$settings,
            'admin.php'=>$admin
        );
        $args1 = self::add_args_from_white_list($args0);
        $args2 = apply_filters('ure_admin_menu_access_allowed_args', $args1);
        
        $result = isset($args2[$command]) ? $args2[$command] : array();
        
        return $result;
        
    }
    // end of get()
        

}
// end of class URE_Admin_Menu_URL_Allowed_Args
