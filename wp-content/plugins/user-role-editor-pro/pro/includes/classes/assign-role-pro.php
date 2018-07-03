<?php
// Currently this module is not used
/**
 * Project: User Role Editor Pro plugin
 * Assign role to users without role by WP_CRON in background
 * Author: Vladimir Garagulia
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * Greetings: some ideas and code samples for long running CRON job was taken from the "Broken Link Checker" plugin (Janis Elst).
 * License: GPL v2+
 * 
 * Assign role to the users without role stuff
 */
class URE_Assign_Role_Pro extends URE_Assign_Role {
    
    const CRON_ACTION_HOOK = 'ure_assign_role_to_users_without_role';
    const MAX_EXECUTION_TIME = 60000;    // 1 minute in milliseconds
    const TARGET_USAGE_FRACTION = 0.25;
       
    
    private function register_cron_job($new_role, $users) {
        
        if (count($users)>self::MAX_USERS_TO_PROCESS) {
            $users1 = array_slice($users, 0, self::MAX_USERS_TO_PROCESS);
            $users2 = array_slice($users, self::MAX_USERS_TO_PROCESS, 65000);
            $job_data = new stdClass();
            $job_data->users = $users2;
            $job_data->new_role = $new_role;
            update_site_option('ure_assign_role_job', $job_data, true);
            
            // register scheduled event to WP cron 
            if (!wp_next_scheduled(self::CRON_ACTION_HOOK)) {
                wp_schedule_event(time(), 'hourly', self::CRON_ACTION_HOOK);
            }
            
        } else {
            $users1 = $users;
        }

        return $users1;
                
    }
    // end of register_cron_job()

    
    public function get_users_without_role($new_role='') {
    
        $users = parent::get_users_without_role($new_role);        
        // $users = $this->register_cron_job($new_role, $users);
        
        
        return $users;
        
    }
    // end of get_users_without_role()

    
    public function get_users_queued() {
        if (!wp_next_scheduled(URE_Assign_Role::CRON_ACTION_HOOK)) {
            return 0;
        }
        $job_data = get_site_option('ure_assign_role_job');
        if (empty($job_data->users)) {
            return 0;
        }
        
        $users_queued = count($job_data->users);
        
        return $users_queued;
    }
    // end of get_users_queued()
        
        
    /**
     * Prepare to run job
     */
    private function job_init() {
        
        // Close the session to prevent lock-ups with other PHP threads.
        if (session_id()!='') {
            session_write_close();
        }
    
        if (!URE_Mutex::get(self::CRON_ACTION_HOOK)) {
        			 // Another instance of URE is working already
            return;
        }
        
        if (URE_Utils::server_too_busy()) {            
            return;
        }
        
        URE_Utils::start_timer();
        
        // As we will sleep sometime to minimize server load
        set_time_limit( self::MAX_EXECUTION_TIME * 2 );
        
        // Don't stop the script when the connection is closed
        ignore_user_abort( true );
        
        if (!headers_sent()
            && (defined('DOING_AJAX') && constant('DOING_AJAX'))
            && (!defined('WP_DEBUG') || !constant('WP_DEBUG')) ) {
            @ob_end_clean(); //Discard the existing buffer, if any
            header("Connection: close");
            ob_start();
            echo ('Connection closed'); // This could be anything
            $size = ob_get_length();
            header("Content-Length: $size");
            ob_end_flush(); // Strange behaviour, will not work
            flush();        // Unless both are called !
        }
        
    }
    // end of job_init()
    
    
    private function job_cleanup($job_data) {
        
        update_site_option('ure_assign_role_job', $job_data, true);
        URE_Mutex::release(self::CRON_ACTION_HOOK);
        
    }
    // end of job_cleanup()

    
    private function assign_role_to_user($user_id, $new_role) {
        
        $user = get_user_by('id', $user_id);
        if (!in_array($new_role, $user->caps)) {
            $user->add_role($new_role);        
        }
        
    }
    // end of assign_role_to_user()
    
    
    /**
     * Assign role to the users without role
     */
    public function make() {        
        
        $this->job_init();        
        
        $job_data = get_site_option('ure_assign_role_job');
        if (empty($job_data->users)) {
            return;
        }
                        
        $debug = $this->lib->get('debug');
        $users_processed = 0;        
        foreach($job_data->users as $key=>$user_id) {
            if (!$debug) {
                URE_Utils::sleep_to_maintain_ratio(self::TARGET_USAGE_FRACTION);
            }
            
            $this->assign_role_to_user($user_id, $job_data->new_role);
            unset($job_data->users[$key]);
            $users_processed++;
 
            if (!$debug) {
                // Check if we still have some execution time left
                if ( (URE_Utils::get_execution_time()>self::MAX_EXECUTION_TIME) ||
                     URE_Utils::server_too_busy() || 
                     ($users_processed>MAX_USERS_TO_PROCESS) ) {
                    // let's stop
                    $this->job_cleanup($job_data);
                    return;
                }                
            }
        }   // foreach()                
    
        $this->job_cleanup($job_data);
    }
    // end of assign_role_to_users_without_role()
    
}
// end of URE_Assign_Role_Pro class