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

function ig_tree_view_labels ( $post_id, $post_status ) {
    $post_status = ( count(array($post_status)) > 0 && $post_status != 'publish' ? array($post_status) : array() );
    $return = apply_filters( 'ig-cms-tree-view-status', $post_status, $post_id );
    if( 0 == count($return) ) {
        return "publish";
    } else {
        return join(' ', $return);
    }
}