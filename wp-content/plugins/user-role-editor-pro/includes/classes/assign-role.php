<?php
/**
 * Project: User Role Editor plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * Greetings: some ideas and code samples for long runing cron job was taken from the "Broken Link Checker" plugin (Janis Elst).
 * License: GPL v2+
 * 
 * Assign role to the users without role stuff
 */
class URE_Assign_Role {
    
    const MAX_USERS_TO_PROCESS = 50;

    private static $counter = 0;    
    
    protected $lib = null;
    
    
    function __construct() {
        
        $this->lib = URE_Lib::get_instance();
    }
    // end of __construct()


    public function create_no_rights_role() {
        global $wp_roles;
        
        $role_id = 'no_rights';
        $role_name = 'No rights';
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        if (isset($wp_roles->roles[$role_id])) {
            return;
        }
        add_role($role_id, $role_name, array());
        
    }
    // end of create_no_rights_role()
        
    
    private function get_where_condition() {
        global $wpdb;

        $usermeta = $this->lib->get_usermeta_table_name();
        $id = get_current_blog_id();
        $blog_prefix = $wpdb->get_blog_prefix($id);
        $where = "where not exists (select user_id from {$usermeta}
                                          where user_id=users.ID and meta_key='{$blog_prefix}capabilities') or
                          exists (select user_id from {$usermeta}
                                    where user_id=users.ID and meta_key='{$blog_prefix}capabilities' and 
                                          (meta_value='a:0:{}' or meta_value is NULL))";
                                    
        return $where;                            
    }
    // end of get_where_condition()
    
    
    public function count_users_without_role() {
        
        global $wpdb;
    
        $users_quant = get_transient('ure_users_without_role');
        if (empty($users_quant)) {
            $where = $this->get_where_condition();
            $query = "select count(ID) from {$wpdb->users} users {$where}";
            $users_quant = $wpdb->get_var($query);
            set_transient('ure_users_without_role', $users_quant, 15);
        }
        
        return $users_quant;
    }
    // end of count_users_without_role()
    
    
    public function get_users_without_role($new_role='') {
        
        global $wpdb;
        
        $top_limit = self::MAX_USERS_TO_PROCESS;
        $where = $this->get_where_condition();
        $query = "select ID from {$wpdb->users} users
                    {$where}
                    limit 0, {$top_limit}";
        $users0 = $wpdb->get_col($query);        
        
        return $users0;        
    }
    // end of get_users_without_role()
    
    
    public function show_html() {
        
      $users_quant = $this->count_users_without_role();
      if ($users_quant==0) {
          return;
      }
      $button_number =  (self::$counter>0) ? '_2': '';
      
?>          
        &nbsp;&nbsp;<input type="button" name="move_from_no_role<?php echo $button_number;?>" id="move_from_no_role<?php echo $button_number;?>" class="button"
                        value="Without role (<?php echo $users_quant;?>)" onclick="ure_move_users_from_no_role_dialog()">
<?php
    if (self::$counter==0) {
?>
        <div id="move_from_no_role_dialog" class="ure-dialog">
            <div id="move_from_no_role_content" style="padding: 10px;"></div>                
        </div>
<?php
        self::$counter++;
    }
        
    }
    // end of show_html()
       
}
// end of URE_Assign_Role class