<?php
/**
 * 
 * Content View Restrictions add-on
 * Output below is used as a template for meta box at the post editor page and as additional form at the taxonomy/category editor page
 * 
 */

?>
<div style="margin-bottom: 10px;">
    <strong><?php esc_html_e('View Access:','user-role-editor');?></strong>
    <div style="padding-left: 20px;">
        <input type="radio" id="ure_allow_flag" name="ure_prohibit_allow_flag" value="2"  <?php checked($prohibit_allow_flag, 2);?> > <label for="ure_allow_flag"><?php echo esc_html_e('Allow View', 'user-role-editor');?></label><br>
        <input type="radio" id="ure_prohibit_flag" name="ure_prohibit_allow_flag" value="1"  <?php checked($prohibit_allow_flag, 1);?> > <label for="ure_prohibit_flag"><?php echo esc_html_e('Prohibit View', 'user-role-editor');?></label>&nbsp;
    </div>    
</div>

<div style="margin-bottom: 10px;">
    <strong><?php esc_html_e('For Users:','user-role-editor');?></strong>
    <div style="padding-left: 20px;">
        <input type="radio" id="content_view_whom_all" name="ure_content_view_whom" value="1"  <?php checked($content_view_whom, 1); ?> class="ure_content_view_whom" > 
        <label for="content_view_whom_all"><?php echo esc_html_e('All visitors (logged in or not)', 'user-role-editor'); ?></label><br>
        <input type="radio" id="content_view_whom_any_role" name="ure_content_view_whom" value="2"  <?php checked($content_view_whom, 2); ?> class="ure_content_view_whom" > 
        <label for="content_view_whom_any_role"><?php echo esc_html_e('Any User Role (logged in only)', 'user-role-editor'); ?></label><br>
        <input type="radio" id="content_view_whom_selected_roles" name="ure_content_view_whom" value="3"  <?php checked($content_view_whom, 3); ?> class="ure_content_view_whom" > 
        <label for="content_view_whom_selected_roles"><?php echo esc_html_e('Selected User Roles', 'user-role-editor'); ?></label>
        <div id="ure_selected_roles_container" style="padding-left: 20px;">
            <button id="edit_content_for_roles"><?php echo esc_html_e('Edit Roles List', 'user-role-editor');?></button>&nbsp;
            <input type="text" id="ure_content_for_roles" name="ure_content_for_roles" value="<?php echo $content_for_roles;?>" readonly="readonly" style="width:70%;"/>
        </div>
    </div>    
</div>
<div style="margin-bottom: 5px;">
    <strong><?php esc_html_e('Action:','user-role-editor');?></strong>
    <div style="padding-left: 20px;">    
        <input type="radio" id="ure_return_http_error_404" name="ure_post_access_error_action" value="1"  
            <?php checked($content_view_access_error_action, 1);?> > 
        <label for="ure_return_http_error_404"><?php esc_html_e('Return HTTP 404 error', 'user-role-editor');?></label><br>
        <input type="radio" id="ure_show_post_access_error_message" name="ure_post_access_error_action" value="2"  
            <?php checked($content_view_access_error_action, 2);?> > 
        <label for="ure_show_post_access_error_message"><?php esc_html_e('Show access error message', 'user-role-editor');?></label><br>
        <input type="radio" id="ure_show_post_access_error_message_custom" name="ure_post_access_error_action" value="3"  
            <?php checked($content_view_access_error_action, 3);?> > 
        <label for="ure_show_post_access_error_message_custom"><?php esc_html_e('Show custom access error message', 'user-role-editor');?></label><br>
        <textarea id="ure_post_access_error_message" name="ure_post_access_error_message" rows="2" style="width: 70%;"><?php echo $post_access_error_message;?></textarea><br>
        <input type="radio" id="ure_redirect_to_url" name="ure_post_access_error_action" value="4"  
            <?php checked($content_view_access_error_action, 4);?> > 
        <label for="ure_redirect_to_url"><?php esc_html_e('Redirect to URL', 'user-role-editor');?></label>&nbsp;
        <input type="text" id="ure_view_access_error_url" name="ure_view_access_error_url" style="width: 70%;" value="<?php echo $view_access_error_url;?>" /> 
    </div>
</div>
<div style="text-align: right; color: #cccccc; font-size: 0.8em;"><?php esc_html_e('User Role Editor Pro', 'user-role-editor');?></div>

<div id="edit_roles_list_dialog" style="display: none;">
    <div id="edit_roles_list_dialog_content" style="padding:10px;">
        <?php echo $roles_list; ?>
    </div>    
</div>    
    <?php        

