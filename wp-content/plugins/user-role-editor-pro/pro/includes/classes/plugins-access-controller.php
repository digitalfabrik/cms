<?php
/*
 * Access restriction to plugins administration
 * Data controller
 * Project: User Role Editor Pro WordPress plugin
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 * 
 */
 
 class URE_Plugins_Access_Controller {
 
    public static function validate_model($model) {
        
        $model = (int) $model;
        if ($model!==1 && $model!==2) {
            $model = 1; //  Selected
        }
        
        return $model;        
        
    }
    // end of validate_model() 
    
    /**
     * Build plugins list access restriction: comma separated plugins directories names list
     * 
     * @param string $plugins_str
     * @return string
     */
    public static function validate_plugins($plugins_str) {
                 
        $plugins_list = explode(',', $plugins_str);
        if (count($plugins_list)>0) {
            $installed_plugins = get_plugins();
            $validated_list = array();
            foreach ($plugins_list as $plugin) {
                $plugin = trim($plugin);
                if (isset($installed_plugins[$plugin])) {
                    $validated_list[] = $plugin;
                }
            }
            $plugins_str1 = implode(',', $validated_list);
        } else {
            $plugins_str1 = '';
        }

        return $plugins_str1;
    }
    // end of validate_plugins()
 
 
 }
 // end of URE_Plugins_Access_Controller
