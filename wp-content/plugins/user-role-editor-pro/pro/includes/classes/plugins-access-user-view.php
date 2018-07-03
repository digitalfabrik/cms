<?php
/*
 * Access restriction to plugins administration
 * User View
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */

class URE_Plugins_Access_User_View extends URE_Plugins_Access_View {
    
    /**
     * 
     * @param WP_User $wp_user
     * @param URE_Plugins_Access_User $plugins_access_user
     * @return boolean
     */
    static private function should_show($wp_user, $plugins_access_user) {
        
        $result = stripos($_SERVER['REQUEST_URI'], 'network/user-edit.php');
        if ($result !== false) {  // exit, this code just for single site user profile only, not for network admin center
            return false;
        }

        if (!current_user_can(URE_Plugins_Access::CAPABILITY)) { // current user can not edit available plugins list
            return false;
        }

        if (user_can($wp_user, URE_Plugins_Access::CAPABILITY)) {  // edited user can edit available plugins list
            return false;
        }

        // if edited user can not activate plugins, do not show allowed plugins input field
        if (!$plugins_access_user->can_activate_plugins($wp_user)) {            
            return false;
        }

        return true;
    }
    // end of should_show()
    
    
    static public function show($wp_user, $plugins_access_user) {

        if (!self::should_show($wp_user, $plugins_access_user)) {
            return;
        }

        $model = URE_Plugins_Access_Controller::validate_model( $plugins_access_user->controller->get_model($wp_user->ID) );
        $plugins = $plugins_access_user->controller->get_plugins($wp_user->ID);
        $formatted_plugins_list = self::format_plugins_list($plugins);
        $model_html = URE_Plugins_Access_View::get_model_html($model);
        
?>        
<h3><?php esc_html_e('Plugins available for activation/deactivation', 'user-role-editor'); ?></h3>
<?php echo $model_html;?>&nbsp;&nbsp;&nbsp;
<input type="button" id="ure_edit_allowed_plugins" name="ure_edit_allowed_plugins" value="<?php esc_html_e('Edit List', 'user-role-editor'); ?>" /><br>
<div style="margin-top: 5px;">
    <textarea name="ure_show_plugins_access_list" id="ure_show_plugins_access_list" cols="80" rows="5" readonly="readonly" /><?php echo $formatted_plugins_list; ?></textarea>
</div>
<input type="hidden" name="ure_plugins_access_list" id="ure_plugins_access_list" value="<?php echo $plugins; ?>" />
<input type="hidden" name="ure_user_id" id="ure_user_id" value="<?php echo $wp_user->ID; ?>" />
<div id="ure_plugins_access_dialog" style="display: none;">
    <div id="ure_plugins_access_dialog_content" style="padding:10px;">
    </div>
</div>    
        <?php
    }

    // end of edit_user_allowed_plugins_list()
}
// end of URE_Plugins_Access_User_View