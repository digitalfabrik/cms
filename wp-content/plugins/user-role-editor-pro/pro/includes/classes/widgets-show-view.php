<?php

/*
 * User Role Editor WordPress plugin
 * Class URE_Widgets_Show_View - user interface for Widgets Show Access add-on
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Widgets_Show_View {

    private $lib = null;

    
    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        
        add_action('sidebar_admin_setup', array($this, 'override_callback'));        
        add_action('admin_enqueue_scripts', array($this, 'add_js'));        
        add_action('admin_footer-widgets.php', array($this, 'add_dialog_container'));
        
        add_filter('admin_head-widgets.php', array('URE_Widgets_Show_Controller', 'save'));
    }
    // end of __construct()
    
    
    // Set own callback for the widgets in order to add own interface elements to them
    public function override_callback() {
        global $wp_registered_widgets, $wp_registered_widget_controls;

        foreach ($wp_registered_widgets as $id => $widget) {
            if (!isset($wp_registered_widget_controls[$id])) {
                continue;
                //wp_register_widget_control($id,$widget['name'], array($this, 'dummy'));
            }
            $wp_registered_widget_controls[$id]['ure_callback_redirect'] = $wp_registered_widget_controls[$id]['callback'];
            $wp_registered_widget_controls[$id]['callback'] = array($this, 'add_ui_control');
            // push the widget id to the params array (as it's not in the main params so not provided to the callback, 
            // we will pop it later their, at 'add_ui_control' method)
            array_push($wp_registered_widget_controls[$id]['params'],$id);	
        }
    }
    // end of override_callback()

        
    public function dummy() {}
    
    /**
     * Add user interface elements for access control to every widget
     * 
     */
    public function add_ui_control() {
        global $wp_registered_widget_controls;  

        $params = func_get_args();
        if (empty($params)) {
            return;
        }
        $id = array_pop($params);
        
        // call to the original callback 1st
        $callback = $wp_registered_widget_controls[$id]['ure_callback_redirect'];
        if (is_callable($callback)) {
            call_user_func_array($callback, $params);
        }
        
        // dealing with multiple widgets - get the number. if -1 this is the 'template' for the admin interface
        $id_disp = $id;
        if (!empty($params) && isset($params[0]['number'])) {	
            $number = $params[0]['number'];
            if ($number==-1) {
                $number = "__i__"; 
                $value = '';                
            }
            $id_disp = $wp_registered_widget_controls[$id]['id_base'] .'-'. $number;
        }
        $id_disp .= '_ure_access';
        
        // output our own user interface controls:
        echo '<p><button id="'. $id_disp .'" name="'. $id_disp  .'">'. esc_html__('Access','user_role_editor') .'</button></p>';
    }
    // end of add_ui_control()


    public function add_js() {
        if (!$this->lib->is_right_admin_path('widgets.php')) {
            return;
        } 

        wp_enqueue_style('wp-jquery-ui-dialog');
        
        wp_enqueue_script('jquery-ui-dialog', '', array('jquery-ui-core','jquery-ui-button', 'jquery') );
        wp_register_script( 'ure-widgets-show-access', plugins_url('/pro/js/widgets-show-access.js', URE_PLUGIN_FULL_PATH ) );
        wp_enqueue_script ( 'ure-widgets-show-access' );
        wp_localize_script( 'ure-widgets-show-access', 'ure_data_widgets_show_access',
                array(
                    'access' => esc_html__('Access', 'user-role-editor'),
                    'cancel' => esc_html__('Cancel', 'user-role-editor'),
                    'dialog_title' => esc_html__('Do not show', 'user-role-editor'),
                    'update_button' => esc_html__('Update', 'user-role-editor'),
                    'wp_nonce' => wp_create_nonce('user-role-editor'),
                ));
        
    }
    // end of add_js()
    
    
    public function add_dialog_container() {
        
        if (!$this->lib->is_right_admin_path('widgets.php')) {
            return;
        }
?>
        <div id="ure_widgets_show_access_dialog" class="ure-modal-dialog">
            <div id="ure_widgets_show_access_container">
            </div>    
        </div>
<?php        
        
    }
    // end of add_dialog_container()

    
    public function get_html() {
        global $wp_roles;
        
        $widget_id = filter_input(INPUT_POST, 'widget_id', FILTER_SANITIZE_STRING);
        $data = URE_Widgets_Show_Controller::load($widget_id);       
        
        ob_start();
?>
        <form name="ure_widgets_show_access_form" id="ure_widgets_show_access_form" method="POST" action="" >
            <span style="font-weight: bold;"><?php echo esc_html_e('for:', 'user-role-editor');?></span>&nbsp;&nbsp;
            <input type="radio" name="ure_access_model" id="ure_access_model_selected" value="1" 
                <?php echo ($data['access_model']==1) ? 'checked="checked"' : '';?> > <label for="ure_access_model_selected"><?php esc_html_e('Selected', 'user-role-editor');?></label> 
            <input type="radio" name="ure_access_model" id="ure_access_model_not_selected" value="2" 
                <?php echo ($data['access_model']==2) ? 'checked="checked"' : '';?> > <label for="ure_access_model_not_selected"><?php esc_html_e('Not Selected', 'user-role-editor');?></label>
            <hr/>
            <table id="ure_widgets_show_access_table">
                <tr>
                    <th>
                        <input type="checkbox" id="ure_widgets_show_access_select_all">
                        <th><?php esc_html_e('Role', 'user-role-editor');?></th>
                    </th>    
                </tr>
<?php                
        foreach($wp_roles->roles as $role_id=>$role_data) {    
            if ($role_id=='administrator') {
                continue;
            }
            if (in_array($role_id, $data['roles'])) {
                $role_selected = 'checked';
            } else {
                $role_selected = '';
            }
?>            
                <tr>
                    <td><input type="checkbox" id="<?php echo 'ure_role_'. $role_id;?>" name="<?php echo 'ure_role_'. $role_id;?>" class="ure-cb-column" value="1" <?php echo $role_selected;?>></td>
                    <td><?php echo $role_data['name'] .' ('. $role_id .')'; ?></td>
                </tr>    
<?php                
        }   // foreach()

            if (in_array(URE_Widgets_Show_Controller::NO_ROLE, $data['roles'])) {
                $role_selected = 'checked';
            } else {
                $role_selected = '';
            }
?>        
                <tr>
                    <td><input type="checkbox" id="<?php echo 'ure_role_'. URE_Widgets_Show_Controller::NO_ROLE;?>" name="<?php echo 'ure_role_'. URE_Widgets_Show_Controller::NO_ROLE;?>" class="ure-cb-column" value="1" <?php echo $role_selected;?>></td>
                    <td><?php esc_html_e('No role for this site', 'user-role-editor'); ?></td>
                </tr>                        
            </table>    
            <input type="hidden" name="action" id="action" value="ure_update_widgets_show_access_data" />            
            <input type="hidden" name="ure_widget_id" id="ure_widget_id" value="<?php echo $widget_id;?>" />
            <?php wp_nonce_field('user-role-editor', 'ure_nonce'); ?>
        </form>
<?php
        $html = ob_get_contents();
        ob_end_clean();
        
        $answer = array('result'=>'success', 'widget_title'=>$widget_id, 'html'=>$html);
        
        return $answer;
    }
    // end of get_html()
    
    

    
}
// end of URE_Widgets_Show class