<?php

class URE_Widgets_Admin_View {

    private $lib = null;
    private $controller = null;
    
    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        $this->controller = new URE_Widgets_Admin_Controller();
        
        add_action('ure_role_edit_toolbar_service', array($this, 'add_toolbar_buttons'));
        add_action('ure_load_js', array($this, 'add_js'));
        add_action('ure_dialogs_html', array($this, 'dialog_html'));
        
    }
    // end of __construct()
    
    
    public function add_toolbar_buttons() {
        if (!current_user_can('ure_widgets_access')) {
            return;
        }
            
?>                
        <button id="ure_widgets_access_button" class="ure_toolbar_button" 
                title="<?php esc_html_e('Prohibit access to selected widgets','user-role-editor');?>">
            <?php esc_html_e('Widgets', 'user-role-editor');?></button>                     
<?php

    }
    // end of add_toolbar_buttons()

    
    public function add_js() {
        wp_register_script( 'ure-widgets-admin-access', plugins_url( '/pro/js/widgets-admin-access.js', URE_PLUGIN_FULL_PATH ) );
        wp_enqueue_script ( 'ure-widgets-admin-access' );
        wp_localize_script( 'ure-widgets-admin-access', 'ure_data_widgets_access',
                array(
                    'widgets' => esc_html__('Widgets', 'user-role-editor'),
                    'dialog_title' => esc_html__('Widgets Access', 'user-role-editor'),
                    'update_button' => esc_html__('Update', 'user-role-editor'),
                    'edit_theme_options_required' => esc_html__('Turn ON "edit_theme_options" capability to manage widgets permissions', 'user-role-editor')
                ));
    }
    // end of add_js()    

    
    public function dialog_html() {
        
?>
        <div id="ure_widgets_access_dialog" class="ure-modal-dialog">
            <div id="ure_widgets_access_container">
            </div>    
        </div>
<?php        
        
    }
    // end of dialog_html()
    

    private function list_widgets($readonly_mode, $blocked_items) {
        
        $widgets_list = $this->controller->get_all_widgets();
?>
<h3><?php esc_html_e('Widgets', 'user_role-editor');?></h3>
<table id="ure_widgets_access_table">
    <th style="color:red;"><?php esc_html_e('Block', 'user-role-editor');?></th>
    <th><?php esc_html_e('Name', 'user-role-editor');?></th>
    <th><?php esc_html_e('Description','user-role-editor');?></th>
<?php
        foreach($widgets_list as $widget_class=>$widget) {
?>
    <tr>
        <td>   
<?php 
    if (!$readonly_mode) {
        $checked = in_array($widget_class, $blocked_items) ? 'checked' : '';
?>
            <input type="checkbox" name="<?php echo $widget_class;?>" id="<?php echo $widget_class;?>" <?php echo $checked;?> />
<?php
    }
?>
        </td>
        <td><?php echo esc_html($widget->name);?></td>
        <td style="padding-left:10px;"><?php echo esc_html($widget->widget_options['description']);?></td>
    </tr>        
<?php
        }   // foreach($widgets_list)
?>
</table> 
<?php        
        
    }
    // end of list_widgets()
    

    private function list_sidebars($readonly_mode, $blocked_items) {
        $sidebars_list = $this->controller->get_all_sidebars();
?>    
<h3><?php esc_html_e('Sidebars', 'user_role-editor');?></h3>
<table id="ure_widgets_access_table">
    <th style="color:red;"><?php esc_html_e('Block', 'user-role-editor');?></th>
    <th><?php esc_html_e('Name', 'user-role-editor');?></th>
    <th><?php esc_html_e('Description','user-role-editor');?></th>
<?php
        foreach($sidebars_list as $sidebar_id=>$sidebar) {
?>
    <tr>
        <td>   
<?php 
    if (!$readonly_mode) {
        $checked = in_array($sidebar_id, $blocked_items) ? 'checked' : '';
?>
            <input type="checkbox" name="<?php echo 'ure_sb-'. $sidebar_id;?>" id="<?php echo $sidebar_id;?>" <?php echo $checked;?> />
<?php
    }
?>
        </td>
        <td><?php echo esc_html($sidebar['name']);?></td>
        <td style="padding-left:10px;"><?php echo esc_html($sidebar['description']);?></td>
    </tr>        
<?php
        }   // foreach($widgets_list)
?>
</table> 
<?php        
        
    }
    // end of list_sidebars()
    
    
    public function get_html($user=null) {
                        
        $allowed_roles = $this->controller->get_allowed_roles($user);                        
        if (empty($user)) {
            $ure_object_type = 'role';
            $ure_object_name = $allowed_roles[0];
            $blocked_items = URE_Widgets_Admin_Controller::load_data_for_role($ure_object_name);
        } else {
            $ure_object_type = 'user';
            $ure_object_name = $user->user_login;
            $blocked_items = $this->controller->load_data_for_user($ure_object_name);
        }
        
        $multisite = $this->lib->get('multisite');
        $readonly_mode = (!$multisite && $allowed_roles[0]=='administrator') || ($multisite && !$this->lib->is_super_admin()); 
        $network_admin = filter_input(INPUT_POST, 'network_admin', FILTER_SANITIZE_NUMBER_INT);
        
        ob_start();
?>
<form name="ure_widgets_access_form" id="ure_widgets_access_form" method="POST"
      action="<?php echo URE_WP_ADMIN_URL . ($network_admin ? 'network/':'') . URE_PARENT.'?page=users-'.URE_PLUGIN_FILE;?>" >
<?php 
    $this->list_widgets($readonly_mode, $blocked_items['widgets']); 
?>
    <hr/>
<?php    
    $this->list_sidebars($readonly_mode, $blocked_items['sidebars']);
?>    
    <input type="hidden" name="action" id="action" value="ure_update_widgets_access" />
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
     
        return array('result'=>'success', 'message'=>'Widgets permissions for '+ $current_object, 'html'=>$html);
    }
    // end of get_html()
    

}
// end of URE_Widgets_Admin_View class