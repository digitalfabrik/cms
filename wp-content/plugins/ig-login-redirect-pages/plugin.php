<?php
/**
 * Plugin Name: Login redirect Pages
 * Description: Redirects all users to 'All Pages' instead of the Dashboard after login and fixes a small problem inside of the Page Editor.
 * Version: 0.1
 * Author: Martin Schrimpf
 * Author URI: https://github.com/Meash
 * License: MIT
 */

// TODO: this should probably go to a custom theme (functions.php)

add_filter('login_redirect', 'login_redirect_pages');
function login_redirect_pages() {
	$url = esc_url_raw(admin_url('edit.php?post_type=page&page=cms-tpv-page-page&action=cms_tpv_remove_promo'));
	return $url;
}

// Fixes a translation Problem inside of the Page Editor.
add_action('do_meta_boxes', 'replace_featured_image_box');  
function replace_featured_image_box()  
{  
	remove_meta_box( 'postimagediv', 'page', 'side' );  
	add_meta_box('postimagediv', __('Beitragsbild'), 'post_thumbnail_meta_box', 'page', 'side', 'low');  
}  
?>
