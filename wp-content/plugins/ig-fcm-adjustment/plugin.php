<?php
/**
 * Plugin Name: WP FCM adjustment for Integreat
 * Description: Add Integreat specific data to FCM messages
 * Version: 1.0
 * Author: Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */


function ig_fcm_messages ( $fields ) {
    $fields['data']['lanCode'] = $fields['data']['language'];
    $fields['data']['city'] = $fields['data']['blog_id'];
    unset($fields['data']['language']);
    unset($fields['data']['blog_id']);
    return $fields;
}
add_filter( 'fcm_fields', 'ig_fcm_messages', 10, 3 );

?>
