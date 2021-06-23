<?php
/*
 * Class to export currently selected role
 * Project: User Role Editor WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v3
 * 
*/

class URE_Export_Single_Role {

    // reference to the code library object
    protected $lib = null;
    // array with roles to export
    private $roles = null;
    
    
    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();        
        $this->editor = URE_Editor::get_instance();
        
        add_action('ure_role_edit_toolbar_service', array($this, 'add_toolbar_buttons'));
        add_action('ure_load_js', array($this, 'add_js'));
        add_action('admin_init', array($this, 'export'));        
        
    }
    // end of __construct()
    
    
    public function add_toolbar_buttons() {

        if (!current_user_can('ure_export_roles')) {
            return;
        }
?>
                
        <button id="ure_export_roles_button" class="ure_toolbar_button" title="Export current role to local disk">Export</button> 
<?php        
        
    }
    // end of add_toolbar_buttons()
    
    
    public function add_js() {
        if (!current_user_can('ure_export_roles')) {
            return;
        }
        wp_register_script( 'ure-export', plugins_url( '/pro/js/export-single-role.js', URE_PLUGIN_FULL_PATH ), array(), URE_VERSION );
        wp_enqueue_script ( 'ure-export' );
        wp_localize_script( 'ure-export', 'ure_data_export', 
                array(
                    'export_roles' => esc_html__('Export', 'user-role-editor')
                ));
    }
    // end of add_js()
    
    
    private function base64_enc_value(&$value, $key) {
        if (is_string($value)) {
            $value = base64_encode($value);
        }
    }
    // end of base64_enc_value()
            
    
    private function is_it_applicable() {
        global $pagenow;
        
        if ($pagenow!=='users.php') {
            return false; 
        }
        if (!isset($_GET['page']) || $_GET['page']!=='users-'. URE_PLUGIN_FILE) {
            return false;
        }
        if (!isset($_POST['action']) || $_POST['action']!=='export-roles') {
            return false;
        }
                
        return true;
    }
    // end of is_it_applicable()
    
    
    private function is_request_valid($current_role) {
        
        if (empty($current_role)) {
            echo esc_html__('Role ID is empty', 'user-role-editor');
            return false;
        }        
        
        if (empty($_POST['ure_nonce']) || !wp_verify_nonce($_POST['ure_nonce'], 'user-role-editor')) {
            echo esc_html__('Wrong nonce. Action prohibitied.', 'user-role-editor');
            return false;
        }
                
        if (!current_user_can('ure_export_roles')) {
            echo esc_html__('You do not have sufficient permissions to export roles.', 'user-role-editor');
            return false;
        }
        $this->roles = $this->lib->get_user_roles();
        if (!isset($this->roles[$current_role])) {
            echo esc_html__('Role requested for export does not exist', 'user-role-editor') .' - '. esc_html($current_role);
            return false;
        }
        
        return true;
    }
    // end of is_request_valid()
    
    
    private function load_addon_data($current_role, $data_key) {
        
        switch ($data_key) {
            case URE_Admin_Menu::ACCESS_DATA_KEY: {
                $data = URE_Admin_Menu::load_data_for_role($current_role);
                break;
            }
            case URE_Widgets_Admin_Controller::ACCESS_DATA_KEY: {             
                $data = URE_Widgets_Admin_Controller::load_data_for_role($current_role);
                break;
            }
            case URE_Meta_Boxes::ACCESS_DATA_KEY: {             
                $data = URE_Meta_Boxes::load_data_for_role($current_role);
                break;
            }
            case URE_Other_Roles::ACCESS_DATA_KEY: {             
                $data = URE_Other_Roles::load_data_for_role($current_role);
                break;
            }
            case URE_Posts_Edit_Access_Role::ACCESS_DATA_KEY: {             
                $data = URE_Posts_Edit_Access_Role_Controller::load_data($current_role);
                break;
            }
            case URE_Plugins_Access_Role::ACCESS_DATA_KEY: {             
                $data = URE_Plugins_Access_Role_Controller::load_data($current_role);
                break;
            }
            case URE_Content_View_Restrictions_Controller::ACCESS_DATA_KEY: {             
                $data = URE_Content_View_Restrictions_Controller::load_data_for_role($current_role);
                break;
            }
            default: {
                $data = array();                
            }            
        }
        
        return $data;
    }
    // end of load_addon_data()
    
    /*
     * Exclude from export the capabilities with not supported characters inside
     * This will prevent URE from generating errors later, during import
     */
    public static function validate_caps($role) {
        
        $caps = array();
        foreach($role['capabilities'] as $cap=>$granted) {
            $filtered = URE_Import_Validator::sanitize_capability($cap);
            if ($filtered===$cap) {
                $caps[$cap] = $granted;
            }
        }
        $role['capabilities'] = $caps;
        
        return $role;
    }
    // end of validate_caps()
    
    
    private function load_data($current_role) {
        
        // having in mind that $this->lib->roles was initialized by $this->is_request_valid() call
        if ( empty( $this->roles ) ) {
            $this->roles = $this->lib->get_user_roles();
        }
        if ( !isset( $this->roles[$current_role] ) ) {
            return false;
        }
        $role_tmp = self::validate_caps( $this->roles[$current_role] );
        $role = array($current_role => $role_tmp);
        $data = array('roles'=>$role); 
        
        // Access data from add-ons
        $addons_manager = URE_Addons_Manager::get_instance();
        $active_addons = $addons_manager->get_active();
        $data['addons'] = array();
        foreach($active_addons as $addon) {
            if (isset($addon->access_data_key) && $addon->exportable) {
                $addon_data = $this->load_addon_data($current_role, $addon->access_data_key);
                $data['addons'][$addon->access_data_key] = $addon_data;
            }
        }
        
        return $data;
    }
    // end of load_data()
    
    
    private function encode_data($data) {
        
        array_walk_recursive($data, array($this, 'base64_enc_value'));
        $json_data = json_encode($data);
        // Safari trancates 2 last charatcters of downloaded file - closing '}', which leads to JSON syntax error. 
        // Let's add 2 trailing spaces to the end of data string in hope to fix this issue.
        $json_data .= '  '; 
        $enc_data = base64_encode($json_data);
        
        return $enc_data;
    }
    // end of encode_data()
    
    
    private function send_data($current_role, $data) {
        
        $timestamp = date('_Y-m-d_h_i_s', current_time('timestamp'));       
        $file_name = 'ure-'. $current_role . $timestamp .'.dat';

        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false); // required for certain browsers 
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'. $file_name .'"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($data));

        echo $data;
    }
    // end of send_data()
    
    
    public function export() {        
        
        if (!$this->is_it_applicable()) {
            return;
        }                
                                
        $current_role = $this->lib->get_request_var( 'current_role', 'post' );
        if ( !$this->is_request_valid( $current_role ) ) {
            exit;
        }                
        
        $data = $this->load_data( $current_role );
        if ( $data===false ) {
            echo esc_html__('Export data error.', 'user-role-editor');
            exit;
        }
        
        $enc_data = $this->encode_data($data);
        $this->send_data($current_role, $enc_data);

        exit;                
    }
    // end of export()
            
}
// end of URE_Export_Single_Role class
