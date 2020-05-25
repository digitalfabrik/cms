<?php
/* 
 * User Role Editor Pro Settings
 * Content View Restrictions add-on defaults section   
 *
 */
 
 ?>
<span style="font-size: 15px; font-weight: bold;"><?php esc_html_e('Defaults for content view restrictions:', 'user-role-editor'); ?></span>
<div style="padding-left: 10px;">
    <strong><?php esc_html_e('View access:', 'user-role-editor'); ?></strong>
    <div style="padding-left: 20px;">
        <input type="radio" id="content_view_allow_flag" name="content_view_allow_flag" value="2"  
               <?php checked($content_view_allow_flag, 2); ?> />
        <label for="ure_allow_flag"><?php echo esc_html_e('Allow View', 'user-role-editor'); ?></label><br>
        <input type="radio" id="content_view_prohibit_flag" name="content_view_allow_flag" value="1"  
               <?php checked($content_view_allow_flag, 1); ?> > 
        <label for="ure_prohibit_flag"><?php echo esc_html_e('Prohibit View', 'user-role-editor'); ?></label>
    </div>
</div>
<div style="padding-left: 10px;">
    <strong><?php esc_html_e('For Users:', 'user-role-editor'); ?></strong>
    <div style="padding-left: 20px;">
        <input type="radio" id="content_view_whom_all" name="content_view_whom" value="1"  <?php checked($content_view_whom, 1); ?> > 
        <label for="content_view_whom_all"><?php echo esc_html_e('All visitors (logged in or not)', 'user-role-editor'); ?></label><br>
        <input type="radio" id="content_view_whom_any_role" name="content_view_whom" value="2"  <?php checked($content_view_whom, 2); ?> > 
        <label for="content_view_whom_any_role"><?php echo esc_html_e('Any User Role (logged in only)', 'user-role-editor'); ?></label><br>
        <input type="radio" id="content_view_whom_selected_roles" name="content_view_whom" value="3"  <?php checked($content_view_whom, 3); ?> > 
        <label for="ure_content_view_selected_roles"><?php echo esc_html_e('Selected User Roles / (logged in only)', 'user-role-editor'); ?></label>
    </div>
</div>
<div style="padding-left: 10px;">
    <strong><?php esc_html_e('Action:', 'user-role-editor'); ?></strong>
    <div style="padding-left: 20px;">
        <input type="radio" id="content_view_show_access_error_message" name="content_view_access_error_action" 
               value="2"  <?php checked($content_view_access_error_action, 2); ?> 
               onclick="ure_cvr_defaults.show_message_div();" > 
        <label for="content_view_show_access_error_message"><?php echo esc_html_e('Show Access Error Message', 'user-role-editor'); ?></label><br>
        <input type="radio" id="content_view_return_http_error_404" name="content_view_access_error_action" 
               value="1"  <?php checked($content_view_access_error_action, 1); ?> 
               onclick="ure_cvr_defaults.hide_message_div();" > 
        <label for="content_view_return_http_error_404"><?php echo esc_html_e('Return HTTP 404 error', 'user-role-editor'); ?></label><br>
        <div id="content_view_access_error_message_container" style="display:none;">
            <?php esc_html_e('Message for post access error:', 'user-role-editor'); ?><br/>
            <textarea id="content_view_access_error_message" name="content_view_access_error_message" rows="3" cols="70"><?php echo $content_view_access_error_message; ?></textarea>
        </div>
								<input type="radio" id="ure_redirect_to_url" name="content_view_access_error_action" value="4"  
            <?php checked($content_view_access_error_action, 4);?> > 
        <label for="ure_redirect_to_url"><?php esc_html_e('Redirect to URL', 'user-role-editor');?></label>&nbsp;
        <input type="text" id="content_view_access_error_url" name="content_view_access_error_url" style="width: 70%;" value="<?php echo $content_view_access_error_url;?>" /> 
    </div>
</div>
<input type="hidden" id="cvr_defaults_visible" name="cvr_defaults_visible" value="<?php echo $cvr_defaults_visible; ?>" />