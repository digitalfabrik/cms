<?php
/*
 * Access restriction to plugins administration
 * Role View
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */

class URE_Plugins_Access_Role_View extends URE_Plugins_Access_View {
    
    /**
     * echo HTML for modal dialog window
     */
    static public function dialog_html() {
        
?>
        <div id="ure_plugins_access_dialog" class="ure-modal-dialog">
            <div id="ure_plugins_access_container">
            </div>    
        </div>
<?php        
        
    }
    // end of dialog_html()
    
    
    static public function add_toolbar_button() {
        
        $button_title = esc_html__('Allow activate/deactivate selected plugins', 'user-role-editor');
        $button_label = esc_html__('Plugins', 'user-role-editor');
?>                
        <button id="ure_plugins_access_button" class="ure_toolbar_button" title="<?php echo $button_title; ?>"><?php echo $button_label; ?></button>
<?php

    }
    // end of add_toolbar_button()
    
    
    static public function get_html($args) {
        extract($args);
        $plugins_arr = explode(',', $plugins);
        $network_admin = filter_input(INPUT_POST, 'network_admin', FILTER_SANITIZE_NUMBER_INT);
        ob_start();
?>
    <form name="ure_plugins_access_form" id="ure_plugins_access_form" method="POST"
        action="<?php echo URE_WP_ADMIN_URL . ($network_admin ? 'network/':'') . URE_PARENT .'?page=users-'.URE_PLUGIN_FILE;?>" >
<?php        
        echo URE_Plugins_Access_View::get_model_html($selection_model);
        echo '<hr/>';
        echo URE_Plugins_Access_View::get_plugins_list_html($plugins_arr);
?>        
        <input type="hidden" name="action" id="action" value="ure_update_plugins_access" />
        <input type="hidden" name="ure_object_type" id="ure_object_type" value="role" />
        <input type="hidden" name="ure_object_name" id="ure_object_name" value="<?php echo $object_name;?>" />
        <input type="hidden" name="user_role" id="ure_role" value="<?php echo $object_name;?>" />        
<?php
        wp_nonce_field('user-role-editor', 'ure_nonce'); 
?>
    </form>
<?php        
        $output = ob_get_clean();
        
        return $output;
    }
    // end of get_html()
    
}
// end of URE_Plugins_Access_Role_View