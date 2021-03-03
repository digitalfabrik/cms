<?php
/*
 * Project: User Role Editor Pro WordPress plugin 
 * Class for remove own data on unistallation
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+
 * 
*/

class URE_Uninstall_Pro extends URE_Uninstall {


    protected function init_options_list() {

        parent::init_options_list();
        
        $this->options[] = 'ure_admin_menu_access_data_version';
        $this->options[] = 'ure_admin_menu_copy';
        $this->options[] = 'ure_admin_sub_menu_copy';
        $this->options[] = 'ure_admin_menu_access_data';
        $this->options[] = 'ure_admin_menu_allowed_args_data';
        $this->options[] = 'ure_meta_boxes_access_data';
        $this->options[] = 'ure_meta_boxes_list_copy';
        $this->options[] = 'ure_other_roles_access_data';
        $this->options[] = 'ure_plugins_access_data';     
        $this->options[] = 'ure_posts_edit_access_data';
        $this->options[] = 'ure_posts_view_access_data';
        $this->options[] = 'ure_widgets_access_data';
        $this->options[] = 'ure_widgets_show_access_data';
        $this->options[] = 'ure_nav_menus_access_data';
        $this->options[] = 'external_updates-user-role-editor-pro';
        
    }
    // end fo init_options_list()

    
    protected function delete_options() {
    
        parent::delete_options();
        delete_site_option( 'ure_assign_role_job' );
        
    }
    // end of delete_options()
    
    
    private function build_where_str() {
        
        $meta_keys = array(
            'ure_allow_plugins_activation',
            'ure_allow_themes',            
            'wp_ure_authors_list',
            'ure_categories_list',
            'ure_own_data_only',
            'ure_plugins_access_selection_model',
            'ure_posts_list',
            'ure_posts_restriction_type',
            'ure_post_restrict_type_by_author',
            'ure_post_types',
            'ure_allow_gravity_forms'
        );
        $operands = array();
        foreach( $meta_keys as $meta_key ) {
            $operands[] = "meta_key LIKE '%{$meta_key}'";
        }
        $where_str = implode( ' OR ', $operands );
        
        return $where_str;
    }
    // end of build_where_str()
    
    
    private function delete_usermeta() {
        global $wpdb;
        
        $where_str = $this->build_where_str();
        $query = "DELETE FROM {$wpdb->usermeta} 
                    WHERE {$where_str}";
        $wpdb->query($query);
        
    }
    // end of delete_user_meta()
    
    
    public function act() {

        parent::act();

        $this->delete_usermeta();
        
    }
    // end of act()
}
// end of URE_Uninstall_Pro class
