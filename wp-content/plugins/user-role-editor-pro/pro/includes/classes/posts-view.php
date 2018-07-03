<?php
/*
 * User Role Editor WordPress plugin
 * Class URE_Posts_View - "Posts View Access" view module for role and user level support
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Posts_View {

 
    
    public function __construct() {
        
 
    }
    // end of __construct()
                
        
    protected function get_allowed_roles($user) {
        $allowed_roles = array();
        if (empty($user)) {   // request for Role Editor - work with currently selected role
            $current_role = filter_input(INPUT_POST, 'current_role', FILTER_SANITIZE_STRING);
            $allowed_roles[] = $current_role;
        } else {    // request from user capabilities editor - work with that user roles
            $allowed_roles = $user->roles;
        }
        
        return $allowed_roles;
    }
    // end of get_allowed_roles()
                            
    
    protected function terms_html($blocked_items) {
    
        $taxonomies = get_taxonomies(
                array('public'=>true,
                      'show_ui'=>true), 
                'objects');
        $terms = array();
        if (isset($blocked_items['data']['terms']) && is_array($blocked_items['data']['terms'])) {
            $terms = $blocked_items['data']['terms'];
        }
        
    foreach($taxonomies as $tax_id=>$tax_obj) {
?>
    <span style="font-weight: bold;"><?php echo $tax_obj->labels->name;?></span>
<table id="ure_pva_<?php echo $tax_id;?>_table">
    <tr>
        <th><input type="checkbox" id="ure-cb-select-all-term-<?php echo $tax_id;?>" class="ure_cb_select_all_terms"></th>
        <th style="min-width: 30px;"><?php esc_html_e('ID','user-role-editor');?></th>
        <th><?php echo $tax_obj->labels->singular_name;?></th>
    </tr>
<?php
        $categories = get_categories(array(
            'type' => 'post',
            'child_of' => 0,
            'parent' => '',
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => 0,
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'number' => '',
            'taxonomy' => $tax_id,
            'pad_counts' => false
        ));
    
        foreach($categories as $category) {
?>
    <tr>
        <td>   
<?php     
            $checked = in_array($category->term_id, $terms) ? 'checked' : '';
            $cb_class = 'ure-cb-col-term-'. $tax_id;
            $category_name = ($category->parent>0 ? ' - ':'') . $category->name;
?>
            <input type="checkbox" name="<?php echo 'cat_'. $category->term_id;?>" id="<?php echo 'cat_'. $category->term_id;?>" class="<?php echo $cb_class;?>" <?php echo $checked; ?> />
        </td>
        <td><?php echo $category->term_id;?></td>
        <td title="<?php echo $category->description;?>"><?php echo $category_name;?></td>
    </tr> 
<?php
        }   // foreach($categories...
?>
    </tr>        
</table> 
<hr/>
<?php
        }   // foreach($taxonomies...        
        
    }
    // end of terms_html()
    
    
    protected function page_templates_html($blocked_items) {
        
        $blocked_templates = array();
        if (isset($blocked_items['data']['page_templates']) && is_array($blocked_items['data']['page_templates'])) {
            $blocked_templates = $blocked_items['data']['page_templates'];
        }
        
        $all_templates = get_page_templates();
        if (count($all_templates)==0) {
            return;
        }
        ksort($all_templates);        
?>    
    <span style="font-weight: bold;"><?php esc_html_e('Page templates:','user-role-editor');?></span>
    <table id="ure_pva_page_templates_table">
        <tr>
            <th><input type="checkbox" id="ure-cb-select-all-templates" class="ure_cb_select_all_templates"></th>            
            <th><?php esc_html_e('Name','user-role-editor');?></th>
            <th style="min-width: 30px;"><?php esc_html_e('File','user-role-editor');?></th>
        </tr>
<?php        
        $cb_class = 'ure-cb-col-template';
        foreach(array_keys($all_templates) as $key) {
            $checked = in_array($all_templates[$key], $blocked_templates) ? 'checked' : '';
            $dom_conv = str_replace(array('.','/','_'), '-', $all_templates[$key]);
            $id = 'templ_'. $dom_conv;
?>        
        <tr>
            <td>
                <input type="checkbox" name="<?php echo $id; ?>" id="<?php echo $id; ?>" class="<?php echo $cb_class;?>" <?php echo $checked; ?>
            </td>    
            <td><?php echo esc_html($key); ?></td>
            <td><?php echo $all_templates[$key]; ?></td>
        </tr>    
<?php
        }
?>
    </table>
    <hr/>    
<?php
    } 
    // end of page_templates_html()
    
    
    public function get_html($user=null) {        
                        
        $allowed_roles = $this->get_allowed_roles($user);                         
        if (empty($user)) {
            $ure_object_type = 'role';
            $ure_object_name = $allowed_roles[0];
            $blocked_items = URE_Content_View_Restrictions_Controller::load_data_for_role($ure_object_name);
        } else {
            $ure_object_type = 'user';
            $ure_object_name = $user->user_login;
            $blocked_items = URE_Content_View_Restrictions_Controller::load_access_data_for_user($ure_object_name);
        }
        
        $posts_list = '';
        if (isset($blocked_items['data']['posts']) && is_array($blocked_items['data']['posts'])) {
            $posts_list = implode(', ', $blocked_items['data']['posts']);
        }
        
        $posts_authors_list = '';
        if (isset($blocked_items['data']['authors']) && is_array($blocked_items['data']['authors'])) {
            $posts_authors_list = implode(', ', $blocked_items['data']['authors']);
        }
        
        if (isset($blocked_items['data']['own_data_only']) && $blocked_items['data']['own_data_only']==1) {
            $own_data_only = 1;
        } else {
            $own_data_only = 0;
        }                   
        
        ob_start();
?>
<form name="ure_posts_view_access_form" id="ure_posts_view_access_form" method="POST"
      action="<?php echo URE_WP_ADMIN_URL . URE_PARENT.'?page=users-'.URE_PLUGIN_FILE;?>" >
    <span style="font-weight: bold;"><?php echo esc_html_e('Block:', 'user-role-editor');?></span>&nbsp;&nbsp;
    <input type="radio" name="ure_access_model" id="ure_access_model_selected" value="1" 
        <?php echo ($blocked_items['access_model']==1) ? 'checked="checked"' : '';?> > <label for="ure_access_model_selected"><?php esc_html_e('Selected', 'user-role-editor');?></label> 
    <input type="radio" name="ure_access_model" id="ure_access_model_not_selected" value="2" 
        <?php echo ($blocked_items['access_model']==2) ? 'checked="checked"' : '';?> > <label for="ure_access_model_not_selected"><?php esc_html_e('Not Selected', 'user-role-editor');?></label>
    <hr/>
    <input type="radio" id="ure_return_http_error_404" name="ure_post_access_error_action" value="1" <?php checked($blocked_items['access_error_action'], 1);?> >
    <label for="ure_return_http_error_404">Return HTTP 404 error</label>&nbsp;&nbsp;
    <input type="radio" id="ure_show_post_access_error_message" name="ure_post_access_error_action" value="2" <?php checked($blocked_items['access_error_action'], 2);?> >
    <label for="ure_show_post_access_error_message">Show access error message</label>
    <hr/>
    <span style="font-weight: bold;"><?php echo esc_html_e('Posts ID list (comma separated)', 'user-role-editor');?>:</span>
    <input type="text" id="ure_posts_list" name="ure_posts_list" value="<?php echo $posts_list;?>" style="width: 300px;" />
    <hr/>
    
    <span style="font-weight: bold;"><?php echo esc_html_e('Authors ID list (comma separated)', 'user-role-editor');?>:</span>
    <input type="text" id="ure_posts_authors_list" name="ure_posts_authors_list" value="<?php echo $posts_authors_list;?>" style="width: 300px;" /><br/>
    <input type="checkbox" id="ure_own_data_only" name="ure_own_data_only" <?php checked($own_data_only, 1);?> />
    <label for="ure_own_data_only"><?php echo esc_html_e('Own data only', 'user-role-editor');?></label>    
    <hr/>
    
<?php
    $this->terms_html($blocked_items);   
    $this->page_templates_html($blocked_items);
    
?>  
    <input type="hidden" name="action" id="action" value="ure_update_posts_view_access" />
    <input type="hidden" name="ure_object_type" id="ure_object_type" value="<?php echo $ure_object_type;?>" />
    <input type="hidden" name="ure_object_name" id="ure_object_name" value="<?php echo $ure_object_name;?>" />
<?php
        if ($ure_object_type=='role') {
?>
    <input type="hidden" name="user_role" id="ure_role" value="<?php echo $ure_object_name;?>" />
<?php
        }
?>
    <?php wp_nonce_field('user-role-editor', 'ure_nonce'); ?>
</form>    
<?php    
        $html = ob_get_contents();
        ob_end_clean();
        
        if (!empty($user)) {
            $current_object = $user->user_login;
        } else {
            $current_object = $allowed_roles[0];
        }
     
        return array('result'=>'success', 'message'=>'Posts view permissions for '+ $current_object, 'html'=>$html);
    }
    // end of get_html()

}
// end of URE_Posts_View class