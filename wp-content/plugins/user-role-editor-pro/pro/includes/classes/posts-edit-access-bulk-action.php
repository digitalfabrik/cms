<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of class-post-edit-access-bulk-action
 *
 * @author vladimir
 */
class URE_Posts_Edit_Access_Bulk_Action {
    
    private $lib = null;
    private $user_meta = null;
    
    public function __construct() {

        $this->lib = URE_Lib_Pro::get_instance();
        $this->user_meta = new URE_Posts_Edit_Access_User_Meta();
        
        if ( !(defined( 'DOING_AJAX' ) && DOING_AJAX) ) {
            add_action( 'admin_init', array($this, 'add_css') );
            add_action( 'admin_footer', array($this, 'add_js') );        
        }

    }
    // end of __construct()
    
    
    public function add_css() {
        
        if (!$this->lib->is_right_admin_path('edit.php')) {        
            return;
        }
        
        if ( !(current_user_can('edit_users') && current_user_can('ure_edit_posts_access')) ) {
            return;
        }
        
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_style('ure-admin-css', URE_PLUGIN_URL . 'css/ure-admin.css', array(), false, 'screen');
    }
    // end of add_css()



    public function add_js() {
        
        if (!$this->lib->is_right_admin_path('edit.php')) {        
            return;
        }
                
        if ( !(current_user_can('edit_users') && current_user_can('ure_edit_posts_access')) ) {
            return;
        }

?>
        <div id="ure_bulk_post_edit_access_dialog" class="ure-dialog">
            <div id="ure_bulk_post_edit_access_content" style="padding: 10px;">
                <span class="bold">What to do:</span>&nbsp;<input type="radio" name="ure_what_todo" id="ure_what_todo1" value="1" checked >
                <label for="ure_what_todo1">Add to existing data</label>
                <input type="radio" name="ure_what_todo" id="ure_what_todo2" value="2"  >
                <label for="ure_what_todo2">Replace existing data</label>
                <hr/>
                <input type="radio" name="ure_posts_restriction_type" id="ure_posts_restriction_type1" value="1" checked >
                <label for="ure_posts_restriction_type1">Allow</label>
                <input type="radio" name="ure_posts_restriction_type" id="ure_posts_restriction_type2" value="2"  >
                <label for="ure_posts_restriction_type2">Prohibit</label><br>
                edit these Posts (comma separated list of IDs):<br>
                <textarea name="ure_posts" id="ure_posts" rows="2" cols="50"></textarea><br/>
                for these Users (comma separated list of IDs):<br/>
                <textarea name="ure_users" id="ure_users" rows="2" cols="50"></textarea>
            </div>                
        </div>
<?php
        
        wp_enqueue_script('jquery-ui-dialog', '', array('jquery-ui-core','jquery-ui-button', 'jquery') );
        wp_register_script( 'ure-bulk-edit-access', plugins_url( '/pro/js/ure-bulk-edit-access.js', URE_PLUGIN_FULL_PATH ), array(), URE_VERSION );
        wp_enqueue_script ( 'ure-bulk-edit-access' );      
        wp_localize_script( 'ure-bulk-edit-access', 'ure_bulk_edit_access_data', array(
            'wp_nonce' => wp_create_nonce('user-role-editor'),
            'action_title' => esc_html__('Edit Access', 'user-role-editor'),
            'dialog_title' => esc_html(__('Editor Restrictions Helper', 'user-role-editor')),
            'apply' => esc_html(__('Apply', 'user-role-editor')),
            'provide_user_ids' => esc_html(__('Provide list of users ID', 'user-role-editor'))
              ));
        
    }
    // end of add_js()

    
    private function bulk_update_prepare() {
                
        if (!current_user_can('ure_edit_posts_access')) {
            $answer = array('result'=>'failure', 'message'=>esc_html__('You do not have enough permissions for this action.','user-role-editor'));
            return $answer;
        }
        $what_todo = $this->lib->get_request_var('what_todo', 'post', 'int');
        if ($what_todo!=1 && $what_todo!=2) {
            $what_todo = 1;
        }
        
        $post_ids_str = $this->lib->get_request_var('post_ids', 'post');
        
        $user_ids_str = $this->lib->get_request_var('user_ids', 'post');
        if (empty($user_ids_str)) {
            $answer = array('result'=>'failure', 'message'=>esc_html__('Provide users ID list.','user-role-editor'));
            return $answer;
        }
        
        $posts_ids_arr = explode(',',$post_ids_str);
        $posts_list_str = URE_Utils::filter_int_array_to_str($posts_ids_arr);
        
        $users_ids_arr = explode(',',$user_ids_str);
        $users_ids_str1 = URE_Utils::filter_int_array_to_str($users_ids_arr);
        if (empty($users_ids_str1)) {
            $answer = array('result'=>'failure', 'message'=>esc_html__('Provide valid users ID list (integers separated by commas).', 'user-role-editor'));
            return $answer;
        }        
                
        $posts_restriction_type = $this->lib->get_request_var('posts_restriction_type', 'post', 'int');
        if ($posts_restriction_type!=1 && $posts_restriction_type!=2) {
            $posts_restriction_type = 1;
        }
                
        $result = array();
        $result['users_list'] = explode(',', $users_ids_str1);
        $result['what_todo'] = $what_todo;
        $result['posts_restriction_type'] = $posts_restriction_type;
        $result['posts_list_str'] = $posts_list_str;        
        
        return $result;
    }
    // end of bulk_update_prepare()
    

    private function update_user_edit_restrictions($user_id, $what_todo, $restriction_type, $posts_list, $post_types) {
        
        $this->user_meta->set_restriction_type($user_id, $restriction_type);        
        if ($what_todo==1) {    // add to existing data
            $current_posts_list = $this->user_meta->get_posts_list($user_id);            
            if (!empty($current_posts_list)) {
                if (!empty($posts_list)) {
                    $posts_list = $current_posts_list .','. $posts_list;
                } else {
                    $posts_list = $current_posts_list;
                }
            }
        }
        $this->user_meta->set_posts_list($user_id, $posts_list);
        
    }
    // end of update_user_edit_restrictions()

    
    public function set_users_edit_restrictions() {
                
        $answer = $this->bulk_update_prepare();
        if (array_key_exists('result', $answer)) {
            return $answer;
        }
        
        extract($answer);   // create variables from array
        foreach($users_list as $user_id) {
            $this->update_user_edit_restrictions($user_id, $what_todo, $posts_restriction_type, $posts_list_str, $post_types);
        }
        
        $answer = array('result'=>'success', 'message'=>'Data updated successfully.');
        return $answer;
    } 
    // end of set_users_edit_restrictions()
    
}
// end of class URE_Posts_Edit_Access_Bulk_Action