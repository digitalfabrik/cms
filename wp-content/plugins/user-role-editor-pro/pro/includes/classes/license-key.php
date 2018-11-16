<?php
/*
 * License key operations support class of User Role Editor WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v3
 * 
 */

class URE_License_Key {

    private $lib = null;
    
    public function __construct($lib) {
        
        $this->lib = $lib;
    }
    // end of __construct()
    
    public function is_editable() {
        
        if (defined('URE_LICENSE_KEY')) { 
            $result = false;
        } else {
            $result = true;
        }
        
        return $result;
        
    }
    // end of is_license_key_editable()

    
    /**
     * Returns license key value
     * @return string
     */
    public function get() {
        if ($this->is_editable()) {
            $license_key = $this->lib->get_option('license_key', '');
        } else {
            $license_key = URE_LICENSE_KEY;
        }
        
        return $license_key;
    }
    // end of get()

    
    private function decode_license_state($license_state) {
        switch ($license_state) {
            case 'active': {
                $result = esc_html__('Active', 'user-role-editor');
                break;
            }
            case 'expired': {
                $result = esc_html__('Expired', 'user-role-editor');
                break;
            }
            default: {
                $result = esc_html__('Invalid', 'user-role-editor');
                break;
            }
        }
        
        return $result;
    } 
    // end of decode_license_state()
    
    
    public function validate($license_key) {
        
        $url = URE_UPDATE_URL .'?action=get_metadata&slug=user-role-editor-pro&license_key='.$license_key;
        $answer = wp_remote_get($url, array('timeout' => 15));
        if (is_wp_error($answer)) {
            $error_message = $answer->get_error_message();
            $result = array('state'=>'invalid', 'text'=>'Something went wrong: '. $error_message);
        } else {
            if ($answer['response']['code']==200) {
                $plugin_data = json_decode($answer['body']);
                $result = array(
                    'state'=>$plugin_data->license_state,
                    'text'=>$this->decode_license_state($plugin_data->license_state)
                );                                
            } else {
                $result = array('state'=>'invalid', 'text'=>$answer['response']['code'] .' '. $answer['response']['message']);
            }
        }
        
        return $result;
    }
    // end of validate_license_key()
    
    
}
// end of class URE_License_Key