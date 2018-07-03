<?php
/**
 * User Role Editor Pro WordPress plugin
 * Class URE_Front_End_Menu_View - shows front end menu access section at front end menu item edit form
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
**/
class URE_Front_End_Menu_View {
    
    public static function show($item_id) {
        
        if (!current_user_can('ure_front_end_menu_access')) {
            return;
        }
        
        $data = URE_Front_End_Menu_Controller::get($item_id);
        $roles = array();
        $roles_list = '';
        if (empty($data) || !is_array($data)) {
            $whom = 1;            
        } else {
            $whom = $data['whom'];
            if (!empty($data['roles'])) {
                $roles_list = $data['roles'];
            }
        }
?>
        <div id="ure_show_to_container_<?php echo $item_id;?>" style="display: block;">            
            <span class="description"><?php esc_html_e('Show to:', 'user-role-editor');?></span><br>
            <input type="radio" name="ure_show_to[<?php echo $item_id;?>]" id="ure_show_to_everyone_<?php echo $item_id;?>" class="ure_show_to" value="1" <?php checked(1, $whom, true);?>/>
            <label for="ure_show_to_everyone_<?php echo $item_id;?>"><?php esc_html_e('Everyone', 'user-role-editor');?></label><br>
            <input type="radio" name="ure_show_to[<?php echo $item_id;?>]" id="ure_show_to_logged_in_<?php echo $item_id;?>" class="ure_show_to" value="2" <?php checked(2, $whom, true);?> />
            <label for="ure_show_to_logged_in_<?php echo $item_id;?>"><?php esc_html_e('Logged-in users', 'user-role-editor');?></label><br>
            <input type="radio" name="ure_show_to[<?php echo $item_id;?>]" id="ure_show_to_logged_in_with_roles_<?php echo $item_id;?>" class="ure_show_to" value="3" <?php checked(3, $whom, true);?> />            
            <label for="ure_show_to_logged_in_with_roles_<?php echo $item_id;?>"><?php esc_html_e('Logged-in users with roles', 'user-role-editor');?></label><br>
            <div id="ure_roles_container1_<?php echo $item_id;?>"></div>
            <input type="radio" name="ure_show_to[<?php echo $item_id;?>]" id="ure_show_to_not_logged_in_<?php echo $item_id;?>" class="ure_show_to" value="4" <?php checked(4, $whom, true);?> />
            <label for="ure_show_to_not_logged_in_<?php echo $item_id;?>"><?php esc_html_e('Not logged-in', 'user-role-editor');?></label><br>
            <input type="radio" name="ure_show_to[<?php echo $item_id;?>]" id="ure_show_to_not_logged_in_and_with_roles_<?php echo $item_id;?>" class="ure_show_to" value="5" <?php checked(5, $whom, true);?> />
            <label for="ure_show_to_not_logged_in_and_with_roles_<?php echo $item_id;?>"><?php esc_html_e('Not logged-in and logged-in users with roles', 'user-role-editor');?></label><br>
            <div id="ure_roles_container2_<?php echo $item_id;?>"></div>
            <div id="ure_selected_roles_container_<?php echo $item_id;?>" style="display: none; padding-left: 20px;">
                <button id="ure_edit_roles_list_<?php echo $item_id;?>" class="ure_edit_roles_list"><?php echo esc_html_e('Edit Roles List', 'user-role-editor');?></button><br>
                <div style="padding-top: 5px;">
                    <textarea id="ure_roles_list_<?php echo $item_id;?>" name="ure_roles_list[<?php echo $item_id;?>]" rows="3" style="width: 100%;" readonly="readonly"><?php echo $roles_list;?></textarea>
                </div>
            </div>
        </div>
<?php
    }
    // end of show()
}
// end of URE_Front_End_Menu_View class