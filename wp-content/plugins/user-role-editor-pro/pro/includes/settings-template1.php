<?php
/*
 * User Role Editor Pro WordPress plugin options page
 *
 * @Author: Vladimir Garagulya
 * @URL: http://role-editor.com
 * @package UserRoleEditor
 *
 */


?>
    <tr>
        <td>
            <input type="checkbox" name="show_notices_to_admin_only" id="show_notices_to_admin_only" value="1"
                   <?php checked($show_notices_to_admin_only, 1); ?> /> 
            <label for="show_notices_to_admin_only"><?php esc_html_e('Show plugins/themes notices to admin only', 'user-role-editor'); ?></label></td>
        <td>                        
        </td>
    </tr>
<?php
if (!$multisite || $license_key_only || $active_for_network) {
?>
      <tr>
          <td cospan="2"><h3><?php esc_html_e('License', 'user-role-editor');?></h3></td>
      </tr>      
      <tr>
        <td>
            <label for="license_key"><?php esc_html_e('License Key:', 'user-role-editor'); ?></label>
<?php
    $license_key_value = empty($license_key) ? '': str_repeat('*', 64);
    if ($license_key->is_editable()) {
?>
            <input type="text" name="license_key" id="license_key" value="<?php echo $license_key_value; ?>" size="15" style="width:450px;" /> 
<?php            
        if (empty($license_key)) {
?>                
            <span style="color: red"><?php esc_html_e('Not installed!', 'user-role-editor');?></span>
        <?php esc_html_e('Input license key to activate automatic updates from role-editor.com', 'user-role-editor'); ?>
<?php                
            }                
    } else {    // if ($license_key->is_editable())
        echo $license_key_value;            
    }   // if ($license_key->is_editable())
    
    if (!empty($license_key)) {        
?>        
    <span style="color: <?php echo $license_state_color;?>;" title="<?php esc_html_e('License key is hidden to limit access to it', 'user-role-editor'); ?>" >
<?php                   
        if (!$license_key->is_editable()) {
            echo esc_html__('Installed', 'user-role-editor') .' (wp-config.php) - ';
        }
        echo $license_state['text'];
?>
    </span>
<?php
    }
?>
        </td>
        <td>
        </td>
      </tr>
<?php
}   // if (!$multisite || $license_key_only || $active_for_network) {