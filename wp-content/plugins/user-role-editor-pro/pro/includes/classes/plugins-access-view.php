<?php
/*
 * Access restriction to plugins administration
 * Base View
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */

class URE_Plugins_Access_View {
    
    /**
     * Show every plugin name from a new line
     * @param string $plugins_str
     * @return string
     */
    static public function format_plugins_list($plugins_str) {

        $plugins_list = explode(',', $plugins_str);
        $plugins = get_plugins();
        $plugins_names = '';
        foreach ($plugins as $key => $plugin) {
            if (!in_array($key, $plugins_list)) {
                continue;
            }
            if (!empty($plugins_names)) {
                $plugins_names .= PHP_EOL;
            }
            $plugins_names = $plugins_names . $plugin['Name'];
        }

        return $plugins_names;
    }
    // end of format_plugins_list()
    
    
    /**
     * Show selection model
     * @param int $model
     */
    static public function get_model_html($model) {
        ob_start();
?>        
    <span style="font-weight: bold;"><?php echo esc_html_e('Allow plugins:', 'user-role-editor');?></span>&nbsp;&nbsp;
    <input type="radio" name="ure_plugins_access_model" id="ure_plugins_access_model_selected" value="1" <?php checked(1, $model); ?> >&nbsp;
    <label for="ure_plugins_access_model_selected"><?php esc_html_e('Selected', 'user-role-editor');?></label> 
    <input type="radio" name="ure_plugins_access_model" id="ure_plugins_access_model_not_selected" value="2" <?php checked(2, $model); ?> >&nbsp; 
    <label for="ure_plugins_access_model_not_selected"><?php esc_html_e('Not Selected', 'user-role-editor');?></label>
    
<?php
        $html = ob_get_clean();
        
        return $html;
    }
    // end of show_model()
    
    
    /**
     * Builds plugins list HTML markup
     * 
     * @param array $plugins - list of plugins selected in the full list of plugins
     * @return string
     */
    static public function get_plugins_list_html($plugins) {
    
        ob_start();
        
        $all_plugins = get_plugins();
?>            
<table id="ure_plugins_access_table">
    <th><input type="checkbox" id="ure_plugins_access_select_all"></th>
    <th><?php esc_html_e('Plugin Name','user-role-editor');?></th>
    <th><?php esc_html_e('Path', 'user-role-editor');?></th>    
<?php
        foreach($all_plugins as $plugin_id=>$plugin) {
            $cb_id = pathinfo($plugin_id, PATHINFO_DIRNAME);
            if ($cb_id=='.') {
                $cb_id = pathinfo($plugin_id, PATHINFO_FILENAME);
            }
?>
    <tr>
        <td>   
<?php     
            $checked = in_array($plugin_id, $plugins) ? 'checked' : '';
            $cb_class = 'ure-cb-column';
?>
            <input type="checkbox" name="<?php echo $cb_id;?>" id="<?php echo $cb_id;?>" class="<?php echo $cb_class;?>" 
                <?php echo $checked;?> value="<?php echo $plugin_id;?>"/>
        </td>
        <td id="<?php echo $cb_id;?>-title"><?php echo $plugin['Name'];?></td>
        <td id="<?php echo $cb_id;?>-id"><?php echo $plugin_id;?></td>
    </tr>        
<?php
        }   // foreach($all_plugins)
?>
</table>        
        
<?php   
        $html = ob_get_clean();
        
        return $html;
    }
    // end of show_plugins_list()
}
// end of URE_Plugins_Access_View