<?php
/* Project: User Role Editor Pro WordPress plugin
 * Exports all user roles from the current site to CSV file with one click
 * Author: Vladimir Garagulia
 * Email: support@role-editor.com
 * Site: https://role-editor.com
 * License: GPL v.3
 */
 
 class URE_Export_Roles_CSV {
                                 
    function __construct() {
         
        add_action( 'ure_settings_tools_show', array( $this, 'show' ) );
        add_action( 'admin_init', array($this, 'act') );
         
    }
    // end of __construct()
     
        
    public function show($tab_idx) {

        $lib = URE_Lib::get_instance();
        $multisite = $lib->get('multisite');
        $link = URE_Settings::get_settings_link();

        if (!$multisite || (is_main_site(get_current_blog_id()) || ( is_network_admin() && $lib->is_super_admin() ) )) {
            if (current_user_can('ure_reset_roles')) {
?>               

                    <div style="margin: 10px 0 10px 0; border: 1px solid green; padding: 0 10px 10px 10px; text-align:left;">
                        <form name="ure_export_roles_csv_form" id="ure_export_roles_csv_form" method="post" action="<?php echo $link; ?>?page=settings-<?php echo URE_PLUGIN_FILE; ?>" >
                            <h3><?php esc_html_e('Export User Roles to CSV file', 'user-role-editor'); ?></h3>            
<?php
                  if ( $multisite && is_network_admin() ) {
                        esc_html_e('Roles will be exported only from the main site.', 'user-role-editor');
                  }
?>          
                            <br><br>
                            <button id="ure_export_roles_csv_button" style="width: 100px;" title="<?php esc_html_e('Export user roles to CSV', 'user-role-editor'); ?>"><?php esc_html_e('Export', 'user-role-editor'); ?></button> 
                <?php wp_nonce_field('user-role-editor'); ?>
                            <input type="hidden" name="ure_settings_tools_exec" value="1" />
                            <input type="hidden" name="ure_export_roles_csv_exec" value="1" />
                            <input type="hidden" name="ure_tab_idx" value="<?php echo $tab_idx; ?>" />
                        </form>                
                    </div>    

                <?php
            }
        }
    }

    // end of show()


    private function caps_to_string($capabilities) {

        if (empty($capabilities)) {
            return '';
        }
        $caps = array();
        foreach ($capabilities as $cap => $allowed) {
            if ($allowed) {
                $caps[] = $cap;
            }
        }

        $caps_str = implode(',', $caps);

        return $caps_str;
    }
    // end of caps_to_string()


    private static function send_data( $data ) {
        
        $timestamp = date('_Y-m-d_h_i_s', current_time('timestamp'));       
        $file_name = 'wp_roles'. $timestamp .'.csv';
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false); // required for certain browsers 
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'. $file_name .'"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($data));
        echo $data;
        
    }
    // end of send_data()
    
    
    private function is_applicable() {
        global $pagenow;
                        
        $settings_page = URE_Settings::get_settings_link();
        if ( $pagenow!==$settings_page ) {
            return false; 
        }
        if ( !isset($_GET['page']) || $_GET['page']!=='settings-user-role-editor-pro.php' ) {
            return false;
        }
        
        $lib = URE_Lib_Pro::get_instance();
        $export_roles_csv = $lib->get_request_var( 'ure_export_roles_csv_exec', 'post', 'int');
        if ( $export_roles_csv!=1 ) {
            return false;
        }
        
        if ( !current_user_can('ure_export_roles') ) {
            return false;
        }
        
        return true;
    }
    // end of is_applicable()
    
    
    public function act() {
        if (!$this->is_applicable()) {
            return;
        }
        
        $wp_roles = wp_roles();
        $roles = $wp_roles->roles;
        $data = '"role_id";"role_name";"capabilities"' . PHP_EOL;
        foreach ($roles as $role_id => $role) {
            $caps = $this->caps_to_string($role['capabilities']);
            $data .= sprintf('"%s","%s","%s"', $role_id, $role['name'], $caps) . PHP_EOL;
        }
        $this->send_data($data);
        exit;
    }
    // end of act()
    
}
 // end of URE_Export_Roles_CSV
