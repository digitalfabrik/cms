<?php
/**
 * Plugin Name: Remove HTTP 403 Forbidden
 * Description: Changes all HTTP 403 Forbidden codes to 200 OK for apache2 mod_substitute
 * Version: 0.1
 * Author: Sven Seeberg
 * Author URI: https://github.com/sven15
 * License: MIT
 */

function change_403_codes() {
    if(http_response_code () == 403) {
       http_response_code(200);
    }
}
add_action ('shutdown','change_403_codes');

?>
