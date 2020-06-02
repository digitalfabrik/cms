<?php
/*
 * Access restriction to plugins administration
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */

class URE_Plugins_Access {
    
    const CAPABILITY = 'ure_plugins_activation_access';
            
    private $lib = null;
    private $user = null;
    
    public function __construct() {
            
        $this->lib = URE_Lib_Pro::get_instance();
        new URE_Plugins_Access_Role();
        $this->user = new URE_Plugins_Access_User();
                        
        add_action('admin_head', array($this, 'prohibited_links_redirect'));        
        add_filter('all_plugins', array($this, 'restrict_plugins_list'));
        
    }
    // end of __construct()
    
                       
    private function redirect() {
    // its late to use wp_redirect() as WP sent some headers already, so we use JavaScript for redirection
?>
<script>
    document.location.href = '<?php echo admin_url('plugins.php'); ?>';
</script>
<?php                    
        die;            
        
    }
    // end of redirect()
    
    
    public function prohibited_links_redirect() {
        
        if ($this->lib->is_super_admin() || current_user_can(self::CAPABILITY)) {
            return;
        }
        
        $current_user = wp_get_current_user();        
        if (!$this->user->can_activate_plugins($current_user)) {        
            return;   
        }
        
        if (!$this->lib->is_right_admin_path('plugins.php?action')) {
            return;
        }    
        
        $data = $this->user->get_data();
        if (empty($data['plugins'])) {                
            return;
        }
        
        $model = $data['model'];
        $plugins = explode(',', $data['plugins']);
        
        // extract plugin id
        $args = wp_parse_args($_SERVER['REQUEST_URI'], array() );    
        if (!isset($args['plugin'])) {
            return;
        }
        
        $redirect = false;
        if ($model==1) {    //  Allow selected
            if ( !in_array($args['plugin'], $plugins) ) {    
                $redirect = true;   // access to this plugin is prohibited - redirect user back to the plugins list
            }
        } else {    // Allow not selected
            if ( in_array($args['plugin'], $plugins) ) {    
                $redirect = true;   // access to this plugin is prohibited - redirect user back to the plugins list
            }    
        }
        
        if ($redirect) {
            $this->redirect();
        }
                                    
    }
    // end of prohibited_links_redirect()

                
  /** 
   * Hide the prohibited plugins from current user
   * 
   * @param type array $plugins plugins list
   * @return type array $plugins updated plugins list
   */
  public function restrict_plugins_list($plugins) {

        // if multi-site, then allow plugin activation for network superadmins and, if that's specially defined, - for single site administrators too    
        if ($this->lib->is_super_admin() || current_user_can(self::CAPABILITY)) {
            return $plugins;
        }

        $data = $this->user->get_data();
        if (empty($data['plugins'])) {
            return $plugins;
        }
        
        $plugins_list = explode(',', $data['plugins']);
        $model = $data['model'];

        // exclude prohibited plugins from the list
        foreach (array_keys($plugins) as $key) {
            if ($model == 1) {
                if (!in_array($key, $plugins_list)) {   // Allowed selected
                    unset($plugins[$key]);
                }
            } else {
                if (in_array($key, $plugins_list)) {    // Allowed not selected
                    unset($plugins[$key]);
                }
            }
        }

        return $plugins;
    }
    // end of restrict_plugins_list()
  
}
// end of URE_Plugins_Access