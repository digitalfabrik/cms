<?php
/*
 * Class to output import roles dialog HTML
 * Project: User Role Editor WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v3
 * 
*/

class URE_Import_Roles_View {

    public static function dialog_html() {
        if (!current_user_can('ure_import_roles')) {
            return;
        }
?>   
<div id="ure_import_roles_dialog" class="ure-modal-dialog">
    <form name="ure_import_roles_form" id="ure_import_roles_form" method="post" enctype="multipart/form-data">  
        <div style="padding:10px;">
            <input type="file" name="roles_file" id="roles_file" style="width: 350px;"/>
        </div>      
<?php		
	if (is_multisite() && is_main_site( get_current_blog_id() ) && is_super_admin()) {
			$hint = esc_html__('If checked, then apply action to ALL sites of this Network');
?>
        <div style="clear: left; padding:10px;" id="ure_import_to_all_div">
            <input type="checkbox" name="ure_import_to_all" id="ure_import_to_all" value="1" 
                title="<?php echo $hint;?>" onclick="ure_importToAllOnClick(this)"/>
            <label for="ure_import_to_all" title="<?php echo $hint;?>"><?php esc_html_e('Apply to All Sites', 'user-role-editor');?></label>
        </div>
<?php
}		    
?>        
        <div style="clear: left; padding:10px;">
            <strong><?php esc_html_e('Together with selected add-ons data:', 'user-role-editor'); ?></strong><br/>
            <span style="font-size:smaller;"><?php esc_html_e('(addon settings are imported in case addon is presented at the imported file)', 'user-role-editor'); ?></span><br/>
<?php
    $addons_manager = URE_Addons_Manager::get_instance();
    $active_addons = $addons_manager->get_active();
    foreach($active_addons as $addon) {
        if (isset($addon->access_data_key) && $addon->exportable) {
?>
            <input type="checkbox" name="<?php echo $addon->access_data_key;?>" 
                   id="<?php echo $addon->access_data_key;?>" value="1" checked="checked"/>
            <label for="<?php echo $addon->access_data_key;?>" ><?php esc_html_e($addon->replicator_title, 'user-role-editor');?></label><br/>
<?php
        }
    }
?>
        </div>    
        <input type="hidden" name="action" id="action" value="import-roles" />          
    </form>    
</div>     

<div id="ure_import_roles_status_dialog" class="ure-modal-dialog">
    <div id="ure_import_roles_status_container"></div>
    <div id="ure_ajax_import" style="display:none;"><img src="<?php echo URE_PLUGIN_URL .'images/ajax-loader.gif'?>" /></div>    
</div>    

<?php        
    }
    // end of dialog_html()


}
// end of URE_Import_Roles_View class
