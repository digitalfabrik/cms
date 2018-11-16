<?php
/*
 * Class: Edit access to posts/pages for role/user data views support
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */

class URE_Posts_Edit_Access_View {
 

    /**
     * echo HTML for modal dialog window
     */
    static public function dialog_html() {
        
?>
        <div id="ure_posts_edit_access_dialog" class="ure-modal-dialog">
            <div id="ure_posts_edit_access_container">
            </div>    
        </div>
<?php        
        
    }
    // end of dialog_html()

    
    static public function add_toolbar_button() {
        
        $button_title = esc_html__('Allow/Prohibit editing selected posts', 'user-role-editor');
        $button_label = esc_html__('Posts Edit', 'user-role-editor');
?>                
        <button id="ure_posts_edit_access_button" class="ure_toolbar_button" title="<?php echo $button_title; ?>"><?php echo $button_label; ?></button>
<?php

    }
    // end of add_toolbar_button()
    
    /**
     * Build and return the string with HTML form for input/update posts edit access data 
     * 
     * @param array $args
     * @return string
     */
    static public function get_html($args) {
        global $pagenow;
        
        extract($args);
        
        ob_start();
        if (isset($user_profile)) { // show section at user profile
            echo '<h3>'. esc_html__('Posts/Pages/Custom Post Types Editor Restrictions', 'user-role-editor') .'</h3>'.PHP_EOL;
        } else {    // show form with data for currently selected role at User Role Editor dialog window
?>
<form name="ure_posts_edit_access_form" id="ure_posts_edit_access_form" method="POST"
      action="<?php echo URE_WP_ADMIN_URL . URE_PARENT .'?page=users-'. URE_PLUGIN_FILE;?>" >
<?php
        }
?>        
        <table class="form-table">
            <tr>
                <th scope="role">
                    <?php esc_html_e('What to do', 'user-role-editor'); ?>
                </th>    
                <td>
                    <input type="radio" name="ure_posts_restriction_type" id="ure_posts_restriction_type1" value="1" <?php  checked($restriction_type, 1);?> >
                    <label for="ure_posts_restriction_type1"><?php esc_html_e('Allow', 'user-role-editor'); ?></label>&nbsp;
                    <input type="radio" name="ure_posts_restriction_type" id="ure_posts_restriction_type2" value="2" <?php  checked($restriction_type, 2);?> >
                    <label for="ure_posts_restriction_type2"><?php esc_html_e('Prohibit', 'user-role-editor'); ?></label>&nbsp;
<?php
    if ($pagenow=='user-edit.php') {
?>
                    <input type="radio" name="ure_posts_restriction_type" id="ure_posts_restriction_type0" value="0" <?php  checked($restriction_type, 0);?> >
                    <label for="ure_posts_restriction_type0"><?php esc_html_e('Look at roles', 'user-role-editor'); ?></label>
<?php
    }
?>
                </td>
            </tr>    
            <tr>
        			<th scope="row">               
               <?php esc_html_e('Own data only', 'user-role-editor'); ?>
           </th>
        			<td>
               <input type="checkbox" name="ure_own_data_only" id="ure_own_data_only" value="1" <?php  checked($own_data_only, 1);?> />
        			</td>
        		</tr>
        		<tr>
        			<th scope="row">               
               <?php esc_html_e('with post ID (comma separated)', 'user-role-editor'); ?>
           </th>
        			<td>
               <input type="text" name="ure_posts_list" id="ure_posts_list" value="<?php echo $posts_list; ?>" size="40" />
        			</td>
        		</tr>    
          <tr>
        			<th scope="row">               
               <?php esc_html_e('with category/taxonomy ID (comma separated)', 'user-role-editor'); ?>
           </th>
        			<td>
               <input type="text" name="ure_categories_list" id="ure_categories_list" value="<?php echo $categories_list; ?>" size="40" />
        			</td>
        		</tr>
<?php
            if ($show_authors) {
?>
          <tr>
        			<th scope="row">
               <?php esc_html_e('with author user ID (comma separated)', 'user-role-editor'); ?>
           </th>
        			<td>
               <input type="text" name="ure_post_authors_list" id="ure_post_authors_list" value="<?php echo $post_authors_list; ?>" size="40" />
        			</td>
        		</tr>
<?php
            }
?>
        </table>		                
<?php
if (!isset($user_profile)) {
?>
    <input type="hidden" name="action" id="action" value="ure_update_posts_edit_access" />
    <input type="hidden" name="ure_object_type" id="ure_object_type" value="<?php echo $object_type;?>" />
    <input type="hidden" name="ure_object_name" id="ure_object_name" value="<?php echo $object_name;?>" />
<?php    
    if ($object_type=='role') {
?>
    <input type="hidden" name="user_role" id="ure_role" value="<?php echo $object_name;?>" />
<?php
    }
    wp_nonce_field('user-role-editor', 'ure_nonce'); 
?>
</form>
<?php    
}
        $output = ob_get_contents();
        ob_end_clean();
        
        return $output;
    }
    // end of get_html()
    
}
// end of URE_Posts_Edit_Access_View class