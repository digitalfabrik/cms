<?php
/*
 * Class to import single role
 * Project: User Role Editor WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v3
 * 
*/

class URE_Import_Single_Role {
    const NEXT_SITE_ACTION = 'import-role-next-site';

    // reference to the code library object
    protected $lib = null;
    
    // reference to the URE_Editor object
    protected $editor = null;
    
    // what addons to import
    private $addons = null;
    // role ID to import
    private $role_id = null;
    // data to import
    private $data = null;
    // Sites pending for the imported role update
    private $sites = null;
    // Site to which import was successful
    private $prev_site = null;
    // Next site name to import role to under WordPress multisite
    private $next_site = null;
    
    
    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        $this->editor = URE_Editor::get_instance();
        
        add_action('ure_role_edit_toolbar_service', array($this, 'add_toolbar_buttons'));
        add_action('ure_load_js', array($this, 'add_js'));
        add_action('ure_dialogs_html', array('URE_Import_Roles_View', 'dialog_html'));
        add_action('ure_process_user_request', array($this, 'notification'));
        add_action('ure_process_user_request', array($this, 'import'));                
        
    }
    // end of __construct()
    
    
    public function add_toolbar_buttons() {

        if (!current_user_can('ure_import_roles')) {
            return;
        }
?>

        <button id="ure_import_roles_button" class="ure_toolbar_button" title="Import role from local disk">Import</button>
<?php
        
    }
    // end of add_toolbar_buttons()
    
    
    public function add_js() {
        if (!current_user_can('ure_import_roles')) {
            return;
        }
        
        $post_action = $this->lib->get_request_var('action', 'post');
        if ($post_action===URE_Import_Single_Role::NEXT_SITE_ACTION) {
            /**
             * It's time to start a process for import role and add-ons to every site of the multi-site network one by one by AJAX
             * It will take data from the main site, to where role and add-on were imported already
             **/
            $action = URE_Import_Single_Role::NEXT_SITE_ACTION;
            $sites_json = isset($_POST['sites']) ? wp_unslash($_POST['sites']) : '[]';
            $sites = json_decode($sites_json);
            $prev_site = esc_html($this->lib->get_request_var('prev_site', 'post'));
            $next_site = esc_html($this->lib->get_request_var('next_site', 'post'));
            $addons_json = isset($_POST['addons']) ? wp_unslash($_POST['addons']) : '[]';
            $addons = json_decode($addons_json);
            $user_role = $this->lib->get_request_var('user_role', 'post');
            $message = esc_html($this->lib->get_request_var('message', 'post'));
        } else {
            $action = '';
            $sites = array();
            $addons = array();
            $prev_site = '';
            $next_site = '';            
            $user_role = '';
            $message = '';
        }
        
        wp_register_script( 'ure-import', plugins_url( '/pro/js/import-single-role.js', URE_PLUGIN_FULL_PATH ), array(), URE_VERSION );
        wp_enqueue_script ( 'ure-import' );       
        wp_localize_script( 'ure-import', 'ure_data_import', 
                array(
                    'import_roles' => esc_html__('Import', 'user-role-editor'),
                    'import_roles_title' => esc_html__('Import Role', 'user-role-editor'),
                    'importing_to'=>esc_html__('Importing to', 'user_role_editor'),
                    'select_file_with_roles' => esc_html__('Select file with role data', 'user-role-editor'),
                    'action'=>$action,
                    'sites'=>$sites,
                    'addons'=>$addons,
                    'prev_site'=>$prev_site,
                    'next_site'=>$next_site,
                    'user_role'=>$user_role,
                    'message'=>$message
                ));
    }
    // end of add_js()
            
    
    private function base64_dec_value(&$value, $key) {
        if (is_string($value)) {
            $value = base64_decode($value);
        }
    }
    // end of base64_dec_value()
           
                    
    public function notification() {
        $action = $this->lib->get_request_var('action', 'post');
        if ($action!=='roles_import_note') {
            return;
        }
        
        $result = $this->lib->get_request_var('result', 'post', 'int');
        $message = esc_html($this->lib->get_request_var('message', 'post'));
        if (empty($message)) {
            if ($result==1) {
                $message = esc_html__('Role was imported successfully', 'user-role-editor');
            } else {
                $message = esc_html__('Unknown error: Role import was failed', 'user-role-editor');
            }
        }
        
        $this->editor->set_notification( $message );
    }
    // end of notification()   
    
               
    private function redirect($result) {
        
        $reload_link = wp_get_referer();
        $reload_link = esc_url_raw(remove_query_arg('action', $reload_link));
        if (empty($this->sites)) {        
?>    
        	<script type="text/javascript" >
             jQuery.ure_postGo('<?php echo $reload_link; ?>', 
                      { action: 'roles_import_note', 
                        user_role: '<?php echo $this->role_id;?>',  
                        result: <?php echo ($result->success ? 1 : 0);?>,  
                        message: '<?php echo $result->message;?>',                        
                        ure_nonce: ure_data.wp_nonce} );
        	</script>  
<?php
        } else {
            $sites_json = json_encode($this->sites);
            $addons_json = json_encode($this->addons);
?>            
        	<script type="text/javascript" >
             jQuery.ure_postGo('<?php echo $reload_link; ?>', 
                      { action: '<?php echo URE_Import_Single_Role::NEXT_SITE_ACTION;?>',
                        user_role: '<?php echo $this->role_id;?>',  
                        result: <?php echo ($result->success ? 1 : 0);?>,  
                        sites: '<?php echo $sites_json;?>',                        
                        prev_site: '<?php echo $this->prev_site;?>',
                        next_site: '<?php echo $this->next_site;?>',
                        addons: '<?php echo $addons_json;?>',
                        message: '<?php echo $result->message;?>',
                        ure_nonce: ure_data.wp_nonce} );
        	</script>  
<?php                                   
        }
                
        exit;
        
    }
    // end of redirect()                      
            
    
    // for security reasons: prevent deletion of administrator role itself or its critical capabilities 
    // via use of import of broken/changed file
    private function validate_role() {
        
        $result = $this->lib->init_result();
        if (!isset($this->data['roles']) || !is_array($this->data['roles']) || count($this->data['roles'])==0) {   // not valid roles array
            $result->message = esc_html__('Import failure: Role file is broken or has invalid format.', 'user-role-editor');
            return $result;
        }
        
        if (!isset($this->data['roles'][$this->role_id])) {
            $result->message = esc_html__('Import failure: Role ID was not found', 'user-role-editor') .' : '. esc_html($this->role_id);
            return $result;
        }
        
        $role = $this->data['roles'][$this->role_id];
        $result = URE_Import_Validator::validate_role($this->role_id, $role);
        if (!$result->success) {
            return $result;
        }
        if (count($role['capabilities'])>0) {
            $result = URE_Import_Validator::validate_capabilities($this->role_id, $role['capabilities']);
            if (!$result->success) {
                return $result;
            }                        
        }                            
        
        $result->success = true;
        
        return $result;
    }
    // end of validate_role()
                                

    private function update_current_site() {
        global $wpdb;
        
        // Role
        $wp_roles = $this->lib->get_user_roles();        
        $wp_roles[$this->role_id] = $this->data['roles'][$this->role_id];
        $wp_roles = URE_Import_Validator::full_access_for_admin($wp_roles, $this->role_id);
        $option_name = $wpdb->prefix . 'user_roles';
        update_option($option_name, $wp_roles);        
        
        // Add-ons
        if (empty($this->addons)) {
            return;
        }
        foreach($this->addons as $addon) {
            if (!isset($this->data['addons'][$addon])) {
                continue;
            }
            $data = get_option($addon, array());
            $data[$this->role_id] = $this->data['addons'][$addon];
            update_option($addon, $data, false);
        }
        
    }
    // end of update_current_site()    
    
    
    private function get_sites_id_except_main() {
                
        $site_ids = $this->lib->get_blog_ids();
        $main_id = $this->lib->get('main_blog_id');
        $list = array();
        foreach($site_ids as $site_id) {
            if ($site_id!==$main_id) {
                $list[] = $site_id;
            }            
        }

        return $list;
    }
    // end of get_sites_id_except_main()
             
    
    static private function get_blog_name($blog_id) {
        global $wpdb;
        
        $options_table = $wpdb->prefix . $blog_id .'_options';
        $query = "SELECT option_value FROM {$options_table} WHERE option_name='blogname' LIMIT 0, 1";
        $name = $wpdb->get_var($query);
        
        return $name;

    }
    // end of get_blog_name()
    
    
    protected function save() {
        
        $this->update_current_site();
        $apply_to_all = $this->lib->get_request_var('ure_import_to_all', 'post', 'checkbox');
        $multisite = $this->lib->get('multisite');
        if ($apply_to_all && $multisite && is_super_admin()) {
            $this->sites = $this->get_sites_id_except_main();        
            if (count($this->sites)>0) {
                $this->prev_site = get_bloginfo('name');
                $this->next_site = URE_Import_Single_Role::get_blog_name($this->sites[0]);
            }
        }        
    }
    // end of save()          
    
    
    /*
     * Only files with single role data inside are supported
     */
    public function import() {
        global $wpdb;
        
        if ($_POST['action']!=='import-roles' || !isset($_FILES['roles_file'])) {
            return;
        }   
        $result = $this->lib->init_result();
        $result->success = false;
        if (empty($_POST['ure_nonce']) || !wp_verify_nonce($_POST['ure_nonce'], 'user-role-editor')) {
            $result->message = esc_html__('Wrong nonce. Action prohibitied.', 'user-role-editor');
            $this->redirect($result);
        }
                        
        if (!current_user_can('ure_import_roles')) {            
            $result->message = esc_html__('You do not have sufficient permissions to import roles.', 'user-role-editor');
            $this->redirect($result);
        }
                        
        $upload_dir = wp_upload_dir();
        if (!empty($upload_dir['error'])) {
            $result->message = esc_html__('File upload error.', 'user-role-editor') .' '. $upload_dir['error'];
            $this->redirect($result);
        }

        $upload_file = $upload_dir['path'] . '/roles-data.tmp';
        if (!move_uploaded_file($_FILES['roles_file']['tmp_name'], $upload_file)) {
            $result->message = esc_html__('File upload error. Can not write to', 'user-role-editor') .' '. $upload_file;
            $this->redirect($result);
        }
        
        $enc_data = file_get_contents($upload_file);
        unlink($upload_file);
        $dec_data = base64_decode( $enc_data );
        $tmp_data = json_decode( $dec_data, true );
        if ( empty( $tmp_data ) ) {
            $result->message = esc_html__( 'Import failure: Role file is broken or has invalid format - ', 'user-role-editor') . json_last_error_msg();
            $this->redirect($result);
        }
        array_walk_recursive($tmp_data, array($this, 'base64_dec_value'));
        $this->data = $tmp_data;
        reset($this->data['roles']);
        $this->role_id = key($this->data['roles']);                
                
        // check array structure
        $result = $this->validate_role();
        if ($result->success) { 
            $this->addons = URE_Import_Validator::what_addons_to_import();
            $this->save();
            if ($wpdb->last_error) {
                $result->message = esc_html__('There was database error during role import', 'user-role-editor') .': '. $wpdb->last_error;
            } else {
                $result->message = esc_html__('Role was imported', 'user-role-editor') .': '. $this->role_id;
            }
        }           
                
        $this->redirect($result);
                                
    }
    // end of import()    
    
    
    static private function validate_import_to_site_request() {
        
        $lib = URE_Lib_Pro::get_instance();
        $site_id = $lib->get_request_var('site_id', 'post', 'int');
        if (empty($site_id)) {
            $error_message = esc_html__('Missed site ID', 'user-role-editor');
            return array('result'=>'error', 'message'=>$error_message);
        }
        if (!is_numeric($site_id)) {
            $error_message = esc_html__('Wrong site ID', 'user-role-editor') .' - '. esc_html($site_id);
            return array('result'=>'error', 'message'=>$error_message);
        }
        $site = URE_Import_Single_Role::get_blog_name($site_id);
        if (empty($site)) {
            $error_message = esc_html__('Site does not exist', 'user-role-editor') .' - '. esc_html($site_id);
            return array('result'=>'error', 'message'=>$error_message);
        }
        
        $next_site_id = $lib->get_request_var('next_site_id', 'post', 'int');
        if (empty($next_site_id) && !is_numeric($next_site_id)) {
            $error_message = esc_html__('Wrong next site ID', 'user-role-editor') .' - '. esc_html($next_site_id);
            return array('result'=>'error', 'message'=>$error_message);
        }        
        if (!empty($next_site_id)) {
            $next_site = URE_Import_Single_Role::get_blog_name($site_id);
            if (empty($next_site)) {
                $error_message = esc_html__('Site does not exist', 'user-role-editor') .' - '. esc_html($next_site_id);
                return array('result'=>'error', 'message'=>$error_message);
            }
        } else {
            $next_site = 'Done';
        }
        
        $user_role = $lib->get_request_var('user_role', 'post');
        $wp_roles = wp_roles();
        if (!isset($wp_roles->roles[$user_role])) {
            $error_message = esc_html__('Wrong role ID', 'user-role-editor') .' - '. esc_html($user_role);
            return array('result'=>'error', 'message'=>$error_message);
        }
        
        if (!empty($_POST['addons'])) {
            $addons = json_decode(wp_unslash($_POST['addons']));
            if (!is_array($addons)) {
                $addons = array();
            }
        } else {
            $addons = array();
        }
        
        
        $result = array(
            'result'=>'success', 
            'site_id'=>$site_id, 
            'next_site_id'=>$next_site_id, 
            'next_site'=>$next_site, 
            'user_role'=>$user_role,
            'addons'=>$addons);
        
        return $result;        
    }
    // end of validate_import_to_site_request()
    
    
    /**
     * Load add-ons data for a single role from the main site
     */
    static private function load_addons_data_for_role($user_role, $addons_to_import) {
        
        $addons = URE_Import_Validator::what_addons_to_import($addons_to_import);
        $addons_data = array();
        if (!empty($addons)) {            
            foreach($addons as $addon) {
                $data = get_option($addon, array());
                $addons_data[$addon] = $data[$user_role];
            }
        }
     
        return $addons_data;
    }
    // end of load_addons_data_for_role() 


    private static function update_addons_data_for_role($user_role, $addons_data) {
        
        if (empty($addons_data)) {
            return;
        }
        
        foreach($addons_data as $addon_key=>$addon_data) {
            $data = get_option($addon_key, array());            
            $data[$user_role] = $addon_data;
            update_option($addon_key, $data, false);
        }

    }
    // end of update_addons_data_for_role()
    
    
    static public function import_to_site() {
        global $wpdb;
        
        $result = self::validate_import_to_site_request();
        if ($result['result']!=='success') {
            return $result;
        }
        
        $lib = URE_Lib_Pro::get_instance();
        
        $user_role = $result['user_role'];
        // Role
        $wp_roles = $lib->get_user_roles();
        $role_data = $wp_roles[$user_role];
        // Add-ons for role
        $addons_data = self::load_addons_data_for_role($user_role, $result['addons']);

        $site_id = $result['site_id'];
        $old_site = $wpdb->blogid;
        switch_to_blog($site_id);

        // Role
        $wp_roles = $lib->get_user_roles();        
        $wp_roles[$user_role] = $role_data;
        $wp_roles = URE_Import_Validator::full_access_for_admin($wp_roles, $user_role);
        $option_name = $wpdb->prefix . 'user_roles';
        update_option($option_name, $wp_roles);
        // Add-ons for role
        self::update_addons_data_for_role($user_role, $addons_data);
        
        switch_to_blog($old_site);
        
        return array('result'=>'success', 'message'=>'Role was imported successfully', 'next_site'=>$result['next_site']);
    }
    // end of import_to_site()
    
}
// end of URE_Import_Single_Role class
