<?php
/* Project: User Role Editor Pro WordPress plugin
 * Import user roles to the current site from CSV file
 * Author: Vladimir Garagulia
 * Email: support@role-editor.com
 * Site: https://role-editor.com
 * License: GPL v.3
 */
 
 class URE_Import_Roles_CSV {
                                 
    function __construct() {
         
        add_action( 'ure_settings_tools_show', array( $this, 'show' ) );
        add_action('ure_load_js', array($this, 'add_js'));
    }
    // end of __construct()
     
    
    public function add_js() {
        if ( !current_user_can('ure_import_roles') ) {
            return;
        }
        
        $post_action = $this->lib->get_request_var('action', 'post');
        if ( $post_action===URE_Import_Single_Role::NEXT_SITE_ACTION ) {
            /**
             * It's time to start a process for import role and add-ons to every site of the multi-site network one by one by AJAX
             * It will take data from the main site, to where role and add-on data were imported already
             **/
            $action = URE_Import_Single_Role::NEXT_SITE_ACTION;
            $sites_json = isset( $_POST['sites'] ) ? wp_unslash( $_POST['sites'] ) : '[]';
            $sites = json_decode( $sites_json );
            $prev_site = esc_html( $this->lib->get_request_var('prev_site', 'post') );
            $next_site = esc_html( $this->lib->get_request_var('next_site', 'post') );
            $addons_json = isset( $_POST['addons'] ) ? wp_unslash( $_POST['addons'] ) : '[]';
            $addons = json_decode( $addons_json );
            $message = esc_html( $this->lib->get_request_var('message', 'post') );
        } else {
            $action = '';
            $sites = array();
            $addons = array();
            $prev_site = '';
            $next_site = '';
            $message = '';
        }
        
        wp_register_script( 'ure-import-csv', plugins_url( '/pro/js/import-roles-csv.js', URE_PLUGIN_FULL_PATH ), array(), URE_VERSION );
        wp_enqueue_script ( 'ure-import-csv' );       
        wp_localize_script( 'ure-import-csv', 'ure_data_import',
                array(
                    'import_roles' => esc_html__('Import', 'user-role-editor'),
                    'import_roles_title' => esc_html__('Import Role', 'user-role-editor'),
                    'importing_to'=>esc_html__('Importing to', 'user_role_editor'),                    
                    'action'=>$action,
                    'sites'=>$sites,
                    'addons'=>$addons,
                    'prev_site'=>$prev_site,
                    'next_site'=>$next_site,
                    'message'=>$message
                ));
    }
    // end of add_js()
    
        
    public function show($tab_idx) {

        if ( !current_user_can('ure_import_roles') ) {
            return;
        }
        
        $lib = URE_Lib::get_instance();
        $multisite = $lib->get('multisite');               
        $link = URE_Settings::get_settings_link();
?>               

        <div style="margin: 10px 0 10px 0; border: 1px solid green; padding: 0 10px 10px 10px; text-align:left;">
            <form name="ure_import_roles_csv_form" id="ure_import_roles_csv_form" method="post" action="<?php echo $link; ?>?page=settings-<?php echo URE_PLUGIN_FILE; ?>" >
                <h3><?php esc_html_e('Import User Roles from CSV file', 'user-role-editor'); ?></h3>            
            <div style="padding:10px;">
                <input type="file" name="roles_file" id="roles_file" style="width: 350px;"/>
            </div>      

<?php
        if ( is_multisite() && is_main_site( get_current_blog_id() ) && is_super_admin() ) {
            $hint = esc_html__('If checked, then apply action to ALL sites of this Network');        
?>          
            <div style="clear: left; padding:10px;" id="ure_import_to_all_div">
                <input type="checkbox" name="ure_import_to_all" id="ure_import_to_all" value="1" 
                    title="<?php echo $hint;?>" onclick="ure_import_to_all_onclick(this);"/>
                <label for="ure_import_to_all" title="<?php echo $hint;?>"><?php esc_html_e('Apply to All Sites', 'user-role-editor');?></label>
            </div>                    
                    
<?php
        }
?>
                    
                <br><br>
                <button id="ure_import_roles_csv_button" style="width: 100px;" title="<?php esc_html_e('Impport user roles from CSV', 'user-role-editor'); ?>"><?php esc_html_e('Import', 'user-role-editor'); ?></button> 
    <?php wp_nonce_field('user-role-editor'); ?>
                <input type="hidden" name="ure_settings_tools_exec" value="1" />
                <input type="hidden" name="ure_import_roles_csv_exec" value="1" />
                <input type="hidden" name="ure_tab_idx" value="<?php echo $tab_idx; ?>" />
            </form>                
        </div>    

        <div id="ure_import_roles_status_dialog" class="ure-modal-dialog">
            <div id="ure_import_roles_status_container"></div>
            <div id="ure_ajax_import" style="display:none;"><img src="<?php echo URE_PLUGIN_URL .'images/ajax-loader.gif'?>" /></div>    
        </div>

<?php            
        
    }
    // end of show()
    
}
 // end of URE_Import_Roles_CSV
