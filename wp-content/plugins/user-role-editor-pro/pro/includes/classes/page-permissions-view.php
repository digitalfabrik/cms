<?php
class URE_Page_Permissions_View {
    
    private $caps = array();
    private $site_path_pos = 0;
    
    
    function __construct() {
        
        $this->site_path_pos = strlen(ABSPATH);
        
        //add_action('wp_after_admin_bar_render', array($this, 'set_hooks'));
        add_action('admin_init', array($this, 'set_hooks'));
        
    }
    // end of __construct()
    
    
    public function set_hooks() {
        if (!current_user_can('ure_edit_roles')) {
            return;
        }
        
        add_action('map_meta_cap', array($this, 'monitor'), 10, 2);
        add_action('admin_footer', array($this, 'show'));
        
    }
    // end of set_hooks()
    
    
    private function clear_path($cap) {
                        
        for($i=5;$i<count($this->caps[$cap]);$i++) {
            if (isset($this->caps[$cap][$i]['file'])) {
                $this->caps[$cap][$i]['file'] = substr($this->caps[$cap][$i]['file'], $this->site_path_pos);
            }
        }
        
    }
    // end of clear_path()


    private function from_admin_menu($details) {
        
        foreach($details as $caller) {
            if(!isset($caller['file'])) {
                continue;
            }
            if (strpos($caller['file'], 'wp-admin/menu-header.php')!==false) {  // left side admin menu
                return true;
            }
            if (strpos($caller['file'], 'wp-includes/admin-bar.php')!==false) { //  top admin menu bar
                return true;
            }
        }
        
        return false;
    }
    // end of from_admin_menu()
    
    
    public function monitor($caps, $cap) {
        global $pagenow;
        
        if (empty($caps)) { //  $cap is not required, as in case with 'edit_user', when user tries to edit himself: always allowed.
            return $caps;
        }        
        
        $caps_list = array_values($caps);
        $capability = $caps_list[0];
        if (isset($this->caps[$capability])) {
            return $caps;
        }
                
        if ($cap=='administrator') {    // We should catch the capabilities only
            return $caps;
        }
        
        if ($cap=='update_core' && $pagenow!=='update-core.php')  {
            return $caps;
        }
                
        if ($cap=='manage_woocommerce') {
            $pages = array('woocommerce', 'wc-settings', 'wc-status', 'wc-addons');
            if (isset($_GET['page']) && in_array($_GET['page'], $pages)) {
                $this->caps[$cap] = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
            }
            return $caps;
        }
                
        $details = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
        if ($this->from_admin_menu($details)) {
            return $caps;
        }
        
        $this->caps[$capability] = $details;
        $this->clear_path($capability);
                
        return $caps;
    }
    // end of monitor()
    
    
    private function get_file_id($cap, $file, $i) {
        $file_id = str_replace(array(DIRECTORY_SEPARATOR, '-'), '_', $file);
        $file_id = str_replace('.php', '', $file_id);        
        $id = $cap .'_'. $file_id .'_'. $i;
        
        return $id;
    }
    // end of get_file_id()
    
    
    private function show_quote_from_source($file_path, $line, $file_id) {
        $full_path = ABSPATH . $file_path;
        try {
            if (file_exists($full_path)) {
                $code = file($full_path, FILE_IGNORE_NEW_LINES);
            } else {
                $code = false;
            }
        } catch (Exception $ex) {
            $code = false;
            syslog(LOG_WARNING, $ex->getMessage());
        }    
        if (empty($code)) {
            return;
        }
?>
    <div id="source_<?php echo $file_id;?>" style="display:none; margin: 10px; padding: 5px 40px 5px 5px; border: 1px solid #CCCCCC;">
<?php
        $start = $line - 2;
        $stop = min(count($code), $line + 1);
        for($i=$start; $i<$stop; $i++) {
            $line_number = $i + 1;
            $style = ($line_number==$line) ? 'style="font-weight: bold;"' : '';            
            echo '<span '. $style .'>'. str_pad($line_number, 5, ' ') .':&nbsp;'. esc_html($code[$i]) .'</span><br>'. PHP_EOL;
        }
?>
    </div>   
<?php        
        
    }
    // end of show_quote_from_source()
    

    public function show() {
?>      
    <div id="ure_list_page_permissions" style="display:none; float: left; margin-left: 160px; margin-bottom: 60px; padding-left: 20px;">
            <h3><?php esc_html_e('User capabilities checked for this page:', 'user-role-editor'); ?></h3>
            <hr/>
<?php            
        $caps = array_keys($this->caps);
        asort($caps);
        foreach($caps as $cap) {
            echo '<a href="javascript:void(0);" onclick="ure_toggle_cap_details(\''. $cap .'\');">'. $cap .'</a><br>'. PHP_EOL;
            echo '<div id="cap_det_'. $cap .'" style="display:none;margin-left:20px;">'.PHP_EOL;
            for($i=5;$i<count($this->caps[$cap]);$i++) {
                $class = isset($this->caps[$cap][$i]['class']) ? ' '. $this->caps[$cap][$i]['class'] . $this->caps[$cap][$i]['type'] : '';
                $file = isset($this->caps[$cap][$i]['file']) ? $this->caps[$cap][$i]['file'] : '';
                $line = isset($this->caps[$cap][$i]['line']) ? $this->caps[$cap][$i]['line'] : '';                
                echo '<div style="display:block;">'. ($i-4) .' : '. 
                     $class . $this->caps[$cap][$i]['function'] .'()'; 
                if (!empty($file)) {
                    $file_id = $this->get_file_id($cap, $file, $i);
                    echo ', '. $file .', line #'. $line .
                        '&nbsp;&nbsp;&nbsp;<a href="javascript:void(0);" onclick="ure_toggle_code_source(\''. $file_id .'\');">More...</a>'. PHP_EOL;
                    $this->show_quote_from_source($file, $line, $file_id);
                } else {   
                    echo PHP_EOL;
                }
                echo '</div>'. PHP_EOL;
            }
            echo '</div>'.PHP_EOL;
        }
?>        
            <hr/>
    </div>    
    <div id="ure_page_permissions_link" style="position: absolute; bottom: 30px; float: left; margin-left: 160px; margin-bottom: 10px; padding-left: 20px;"> 
        <a href="javascript:void(0);" onclick="ure_toggle_page_permissions();"><?php esc_html_e('View page permissions', 'user-role-editor');?></a>
    </div>
<script>
    function ure_toggle_page_permissions() {
        jQuery('#ure_list_page_permissions').toggle();
        jQuery('html, body').animate({ scrollTop: (jQuery('#ure_page_permissions_link').offset().top) },500); 
    }
    
    function ure_toggle_cap_details(cap) {
        jQuery('#cap_det_'+ cap).toggle();
    }
    
    function ure_toggle_code_source(file_id) {
        jQuery('#source_'+ file_id).toggle();
    }
</script>    
<?php
    }
    // end of show()

}
// end of URE_List_Used_Capabilities class