<?php
/*
Plugin Name: CMS Tree Page View Extensions
*/

add_filter( 'custom_menu_order', 'wpse_73006_submenu_order' );

function wpse_73006_submenu_order( $menu_ord )
{
    global $submenu;
    $arr = array();
    $arr[] = $submenu['edit.php?post_type=page'][11];
    $arr[] = $submenu['edit.php?post_type=page'][5];
    $arr[] = $submenu['edit.php?post_type=page'][10];
    $submenu['edit.php?post_type=page'] = $arr;
    return $menu_ord;
}

