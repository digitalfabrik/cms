<?php
/*
 * User Role Editor Pro WordPress plugin options page
 *
 * @Author: Vladimir Garagulya
 * @URL: http://role-editor.com
 * @package UserRoleEditor
 *
 */

if (is_super_admin()) {
?>
      <tr>
        <td style="width: 40%;">
            <input type="checkbox" name="enable_simple_admin_for_multisite" id="enable_simple_admin_for_multisite" value="1" 
            <?php checked($enable_simple_admin_for_multisite, 1); ?>
            <?php echo defined('URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE') ? 'disabled="disabled" title="Predefined by \'URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE\' PHP constant"' : ''; ?> />
            <label for="enable_simple_admin_for_multisite">
                <?php esc_html_e('Allow single site administrator access to User Role Editor', 'user-role-editor'); ?>
            </label>
        </td>
        <td>&nbsp;</td>
      </tr>
<?php
}
?>
      <tr>
        <td>
            <input type="checkbox" name="enable_unfiltered_html_ms" id="enable_unfiltered_html_ms" value="1" 
                <?php checked($enable_unfiltered_html_ms, 1); ?> /> 
            <label for="enable_unfiltered_html_ms" style="color: red;">
                <?php esc_html_e('Enable "unfiltered_html" capability', 'user-role-editor'); ?>
            </label>                        
        </td>
        <td>
<?php
    if ($enable_unfiltered_html_ms==1) {
?>
            <span style="color: red; font-weight: bold;">Warning!</span> This is a very dangerous to activate, if you have untrusted users on your site. Any user with <strong>unfiltered_html</strong> capability could add Javascript code to steal the login cookies of any visitor who runs a blog on the same site. The rogue user can then impersonate any of those users.
<?php
    }
?>
        </td>
      </tr>
<?php
if (is_super_admin()) {
?>
      <tr>
        <td>
            <input type="checkbox" name="manage_themes_access" id="manage_themes_access" value="1" <?php checked($manage_themes_access, 1);; ?>            
            <label for="manage_themes_access">
                <?php esc_html_e('Activate access management for themes', 'user-role-editor'); ?>
            </label>
        </td>
        <td>
        </td>
      </tr>
      <tr>
        <td>
            <input type="checkbox" name="caps_access_restrict_for_simple_admin" id="caps_access_restrict_for_simple_admin" value="1" 
            <?php checked($caps_access_restrict_for_simple_admin, 1); ?>
            <label for="caps_access_restrict_for_simple_admin">
                <?php esc_html_e('Activate access restrictions to User Role Editor for single site administrator', 'user-role-editor'); ?>
            </label>
        </td>
        <td>
        </td>
      </tr>
      
<?php
    if ($caps_access_restrict_for_simple_admin) {
?>
      <tr>
          <td colspan="2">
              <hr/>
          </td>
      </tr>      
      <tr>
          <td colspan="2">
              <h3>Restrict single site administrators access to User Role Editor</h3>
          </td>
      </tr>
      <tr>
        <td colspan="2">
            <input type="checkbox" name="add_del_role_for_simple_admin" id="add_del_role_for_simple_admin" value="1" 
            <?php checked($add_del_role_for_simple_admin, 1); ?>
            <label for="add_del_role_for_simple_admin" title="<?php esc_html_e('Single site administrator can delete roles with allowed capabilities inside only', 'user-role-editor'); ?>">
                <?php esc_html_e('Allow single site administrator Add/Delete roles', 'user-role-editor'); ?>
            </label>
        </td>
      </tr>      
      <tr>
          <td colspan="2">            
            <table>
                <tr>
                    <td>
                        <span style="color: red;font-weight: bold;"><?php esc_html_e('Blocked capabilities:', 'user-role-editor'); ?></span><br/>
                            Filter: <input type="text" id="box1Filter" /><button type="button" id="box1Clear">X</button><br />
                            <select id="box1View" multiple="multiple" style="height:400px;width:300px;">
                               <?php echo $html_caps_blocked_for_single_admin;?>
                            </select><br/>
                            <span id="box1Counter" class="countLabel"></span>

                           <select id="box1Storage">
                           </select>    
                    </td>
                    <td>
                        <button id="to2" type="button" style="width: 50px;">&nbsp;>&nbsp;</button><br/><br/>
                        <button id="to1" type="button" style="width: 50px;">&nbsp;<&nbsp;</button><br/><br/>
                        <button id="allTo2" type="button" style="width: 50px;">&nbsp;>>&nbsp;</button><br/><br/>
                        <button id="allTo1" type="button" style="width: 50px;">&nbsp;<<&nbsp;</button>
                    </td>
                    <td>
                        <span style="color: green;font-weight: bold;"><?php esc_html_e('Allowed capabilities:', 'user-role-editor'); ?></span><br/>
                        Filter: <input type="text" id="box2Filter" /><button type="button" id="box2Clear">X</button><br />
                        <select id="box2View" name="caps_allowed_for_single_admin[]" multiple="multiple" style="height:400px;width:300px;">
                            <?php echo $html_caps_allowed_for_single_admin;?>
                        </select><br/>
                        <span id="box2Counter" class="countLabel"></span>
                        <select id="box2Storage">
                        </select>
                    </td>
                </tr>
            </table>    
              
      <script>
          jQuery(function() {
              jQuery.configureBoxes({
                  /*useSorting: false*/ 
              });
          });
      </script>      
          </td>
      </tr>
<?php
    } // if ($caps_access_restrict_for_simple_admin)    
}   //  if (is_super_admin()) {