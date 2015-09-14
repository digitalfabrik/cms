<?php

/* 
 * DEPRECATED FILE!
 * 
 * please use $sitepress->get_desktop_language_selector() instead of including this file. 
 */

global $sitepress;

if ( $sitepress === null
     || ( function_exists ( 'wpml_home_url_ls_hide_check' ) && wpml_home_url_ls_hide_check () )
) {
    return;
}

echo $sitepress->get_desktop_language_selector ();