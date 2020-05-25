<?php
/**
 * Plugin Presence Checker
 *
 * Checks if supported plugin is active
 */
class URE_Plugin_Presence {

    private static $active_plugins = null;

    private static $plugin_ids = array(
        'contact-form-7'=>'contact-form-7/wp-contact-form-7.php',
        'download-monitor'=>'download-monitor/download-monitor.php',
        'duplicate-post'=>'duplicate-post/duplicate-post.php',
        'enable-media-replace'=>'enable-media-replace/enable-media-replace.php',
        'eventon'=>'eventON/eventon.php',
        'global-content-blocks'=>'global-content-blocks/global-content-blocks.php',
        'gravity-forms'=>'gravityforms/gravityforms.php',
        'ninja-forms'=>'ninja-forms/ninja-forms.php',
        'unitegallery'=>'unitegallery/unitegallery.php',
        'ultimate-member'=>'ultimate-member/index.php',
        'visual-composer'=>'js_composer/js_composer.php',
        'woocommerce'=>'woocommerce/woocommerce.php',
        'woocommerce-bookings'=>'woocommerce-bookings/woocommmerce-bookings.php',
        'wp-mail-smtp'=>'wp-mail-smtp/wp_mail_smtp.php',
        'wpml'=>'sitepress-multilingual-cms/sitepress.php',
        'wcmp'=>'dc-woocommerce-multi-vendor/dc_product_vendor.php'
        );
    
    
    private static function get_plugin_ids() {
        
        $ids = apply_filters('ure_plugin_presense_get_plugin_ids', self::$plugin_ids);
        
        return $ids;
    }
    // end of get_plugin_ids()

    /**
     * Returns true if plugin $plugin_id is active
     * @param string $plugin_id - plugin ID, for example 'woocommerce/woocommerce.php'
     * @return type
     */
    public static function is_active($plugin_id) {
        
        $is_active = apply_filters('ure_plugin_presense_is_active', array($plugin_id=>false));
        if (is_array($is_active) && isset($is_active[$plugin_id]) && $is_active[$plugin_id]) {
            return true;
        }
        
        $plugin_ids = self::get_plugin_ids();
        if (!isset($plugin_ids[$plugin_id])) {
            syslog(LOG_NOTICE, 'URE_Plugin_Presence::is_active(): Plugin ID is unknown: '. $plugin_id);
            return false;
        }                
        
        if (self::$active_plugins===null) {
            self::$active_plugins = array();
        }        
        $file_id = $plugin_ids[$plugin_id];
        if (isset(self::$active_plugins[$file_id])) {
            return self::$active_plugins[$file_id];
        }
        
        $full_list = (array) get_option('active_plugins', array());
        if (is_multisite()) {
            $list1 = get_site_option('active_sitewide_plugins', array());
            if (!empty($list1)) {
                $full_list = array_merge($full_list, array_keys($list1));
            }
        }
        $result = in_array($file_id, $full_list);
        self::$active_plugins[$file_id] = $result;

        return $result;
    }
    // end of is_active()

}
// end of URE_Plugin_Presence
