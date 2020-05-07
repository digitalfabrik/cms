<?php
/*
 * User Role Editor Pro WordPress plugin options page
 * "Additional Modules" tab
 * @Author: Vladimir Garagulya
 * @URL: http://role-editor.com
 * @package UserRoleEditor
 *
 */

$admin_menu_access_url_args_link_display = $activate_admin_menu_access_module==1 ? 'block' : 'none';

?>
      <tr>
        <td>
            <input type="checkbox" name="activate_admin_menu_access_module" id="activate_admin_menu_access_module" value="1" 
            <?php checked($activate_admin_menu_access_module, 1); ?> onclick="ure_admin_menu_access_url_args.toggle_link();"/> 
            <label for="activate_admin_menu_access_module"><?php esc_html_e('Activate Administrator Menu Access module', 'user-role-editor'); ?></label>
            <div id="admin_menu_access_url_args_link" style="display: <?php echo $admin_menu_access_url_args_link_display; ?>; padding-left: 25px;">
                <a href="javascript: void(0);" onclick="ure_admin_menu_access_url_args.show();">URL Parameters White List...</a>
            </div>
            <div id="admin_menu_allowed_args_dialog"><div id="admin_menu_allowed_args_container"></div></div>
        </td>
        <td>
        </td>
      </tr>
      <tr>
        <td>
            <input type="checkbox" name="activate_front_end_menu_access_module" id="activate_front_end_menu_access_module" value="1" 
            <?php checked($activate_front_end_menu_access_module, 1); ?> /> 
            <label for="activate_front_end_menu_access_module"><?php esc_html_e('Activate Front End Menu Access module', 'user-role-editor'); ?></label>
        </td>
        <td>
        </td>
      </tr>
      <tr>
        <td>
            <input type="checkbox" name="activate_nav_menus_access_module" id="activate_nav_menus_access_module" value="1" 
            <?php checked( $activate_nav_menus_access_module, 1 ); ?> /> 
            <label for="activate_nav_menus_access_module"><?php esc_html_e( 'Activate Navigation Menus Access module', 'user-role-editor' ); ?></label>
        </td>
        <td>
        </td>
      </tr>
      <tr>
        <td>
            <input type="checkbox" name="activate_widgets_access_module" id="activate_widgets_access_module" value="1" 
            <?php checked($activate_widgets_access_module, 1); ?> /> 
            <label for="activate_widgets_access_module"><?php esc_html_e('Activate Widgets Admin Access module', 'user-role-editor'); ?></label>
        </td>
        <td>
        </td>
      </tr>
      <tr>
        <td>
            <input type="checkbox" name="activate_widgets_show_access_module" id="activate_widgets_show_access_module" value="1" 
            <?php checked($activate_widgets_show_access_module, 1); ?> /> 
            <label for="activate_widgets_show_access_module"><?php esc_html_e('Activate Widgets Show Access module', 'user-role-editor'); ?></label>
        </td>
        <td>
        </td>
      </tr>
      <tr>
        <td>
            <input type="checkbox" name="activate_meta_boxes_access_module" id="activate_meta_boxes_access_module" value="1" 
                <?php checked($activate_meta_boxes_access_module, 1); ?> /> 
            <label for="activate_meta_boxes_access_module"><?php esc_html_e('Activate Meta Boxes Access module', 'user-role-editor'); ?></label>
        </td>
        <td>
        </td>
      </tr>
      <tr>
        <td>
            <input type="checkbox" name="activate_other_roles_access_module" id="activate_other_roles_access_module" value="1" 
                <?php checked($activate_other_roles_access_module, 1); ?> /> 
            <label for="activate_other_roles_access_module"><?php esc_html_e('Activate Other Roles Access module', 'user-role-editor'); ?></label>
        </td>
        <td>
        </td>
      </tr>      
      <tr>
        <td>
            <input type="checkbox" name="manage_plugin_activation_access" id="manage_plugin_activation_access" value="1" 
                <?php checked($manage_plugin_activation_access, 1); ?> /> 
            <label for="manage_plugin_activation_access"><?php esc_html_e('Activate per plugin user access management for plugins activation', 'user-role-editor'); ?></label>
        </td>
        <td>
        </td>
      </tr>      
      <tr>
        <td>
            <input type="checkbox" name="activate_page_permissions_viewer" id="activate_page_permissions_viewer" value="1" 
                <?php checked($activate_page_permissions_viewer, 1); ?> /> 
            <label for="activate_page_permissions_viewer"><?php esc_html_e('Activate wp-admin pages permissions viewer', 'user-role-editor'); ?></label>
        </td>
        <td>
        </td>
      </tr>
      <tr>
        <td>
            <input type="checkbox" name="activate_export_roles_csv" id="activate_export_roles_csv" value="1" 
                <?php checked($activate_export_roles_csv, 1); ?> /> 
            <label for="activate_export_roles_csv"><?php esc_html_e('Activate export user roles to CSV', 'user-role-editor'); ?></label>
        </td>
        <td>
        </td>
      </tr>
      <tr>
          <td cospan="2"><h3><?php esc_html_e('Content editing restrictions', 'user-role-editor');?></h3></td>
      </tr>
      <tr>
        <td>
            <input type="checkbox" name="activate_create_post_capability" id="activate_create_post_capability" value="1" 
                <?php checked($activate_create_post_capability, 1); ?> /> 
            <label for="activate_create_post_capability"><?php esc_html_e('Activate "Create" capability for posts/pages/custom post types', 'user-role-editor'); ?></label>
        </td>
        <td>
        </td>
      </tr>      
      <tr>
        <td>
            <input type="checkbox" name="manage_posts_edit_access" id="manage_posts_edit_access" value="1" 
                <?php checked($manage_posts_edit_access==1); ?> /> 
            <label for="manage_posts_edit_access"><?php esc_html_e('Activate user access management to editing selected posts, pages, custom post types', 'user-role-editor'); ?></label>
        </td>
        <td>
        </td>
      </tr>
      <tr>
        <td>
            <input type="checkbox" name="force_custom_post_types_capabilities" id="force_custom_post_types_capabilities" value="1" 
                <?php checked($force_custom_post_types_capabilities, 1); ?> /> 
            <label for="force_custom_post_types_capabilities"><?php esc_html_e('Force custom post types to use their own capabilities', 'user-role-editor'); ?></label>
        </td>
        <td>
        </td>
      </tr>
<?php
if (class_exists('GFForms')) {
?>
      <tr>
        <td>
            <input type="checkbox" name="manage_gf_access" id="manage_gf_access" value="1" <?php checked($manage_gf_access, 1); ?> />
            <label for="manage_gf_access"><?php esc_html_e('Activate per form user access management for Gravity Forms', 'user-role-editor'); ?></label>
        </td>
        <td> 
        </td>
      </tr>
<?php
    
}
?>
      <tr>
          <td cospan="2"><h3><?php esc_html_e('Content view restrictions', 'user-role-editor');?></h3></td>
      </tr>
      <tr>
          <td>
              <input type="checkbox" name="activate_content_for_roles_shortcode" id="activate_content_for_roles_shortcode" value="1" 
                    <?php checked($activate_content_for_roles_shortcode, 1); ?> />
              <label for="activate_content_for_roles_shortcode"><?php esc_html_e('Activate [user_role_editor roles="role1, role2, ..."] shortcode', 'user-role-editor'); ?></label>
          </td>
          <td>              
          </td>
      </tr>
      <tr>
          <td>
              <input type="checkbox" name="activate_content_for_roles" id="activate_content_for_roles" value="1" onclick="ure_cvr_defaults.refresh();"
                    <?php checked($activate_content_for_roles, 1); ?> />
              <label for="activate_content_for_roles"><?php esc_html_e('Activate content view restrictions', 'user-role-editor'); ?></label>              
          </td>
          <td>              
          </td>
      </tr>
      <tr id="ure_content_view_defaults" style="display:none;">
          <td colspan="2"style="padding-left:25px;">
              <a id="content_view_restrictions_defaults_show" href="javascript: void(0);" onclick="ure_cvr_defaults.toggle_container();"><?php echo empty($cvr_defaults_visible) ? esc_html__('Show Defaults...', 'user-role-editor') : esc_html__('Hide Defaults...', 'user-role-editor');?></a>
              <div id="content_view_restrictions_defaults_container" style="<?php echo (empty($cvr_defaults_visible)) ? 'display: none;' : '';?>padding-left: 5px;">
                  <?php require_once(URE_PLUGIN_DIR .'pro/includes/settings-content-view-defaults.php'); ?>                     
              </div>
          </td>              
      </tr>      
      
      <tr>
          <td cospan="2"><h3><?php esc_html_e('Use additional user capabilities', 'user-role-editor');?></h3></td>
      </tr>
      <tr>
          <td>
              <input type="checkbox" name="activate_add_caps_for_plugins" id="activate_add_caps_for_plugins" value="1" 
                    <?php checked($activate_add_caps_for_plugins, 1); ?> />
              <label for="activate_add_caps_for_plugins"><?php esc_html_e('Plugins related', 'user-role-editor'); ?> (deactivate_plugins)</label>
          </td>
          <td>              
          </td>
      </tr>
      <tr>
          <td>
              <input type="checkbox" name="activate_add_caps_for_languages" id="activate_add_caps_for_languages" value="1" 
                    <?php checked($activate_add_caps_for_languages, 1); ?> />
              <label for="activate_add_caps_for_languages"><?php esc_html_e('Languages related', 'user-role-editor'); ?> (install_languages, update_languages)</label>
          </td>
          <td>              
          </td>
      </tr>
      <tr>
          <td>
              <input type="checkbox" name="activate_add_caps_for_privacy" id="activate_add_caps_for_privacy" value="1" 
                    <?php checked($activate_add_caps_for_privacy, 1); ?> />
              <label for="activate_add_caps_for_privacy"><?php esc_html_e('Privacy related', 'user-role-editor'); ?> (manage_privacy_options, export_others_personal_data, erase_others_personal_data)</label>
          </td>
          <td>              
          </td>
      </tr>
      
