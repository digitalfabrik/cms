<?php
/**
 * Plugin Name: Login redirect Pages
 * Description: Redirects all users to 'All Pages' instead of the Dashboard after login
 * Version: 0.1
 * Author: Martin Schrimpf
 * Author URI: https://github.com/Meash
 * License: MIT
 */

add_filter('login_redirect', 'login_redirect_pages');
function login_redirect_pages() {
	$url = esc_url(admin_url('edit.php?post_type=page&page=cms-tpv-page-page'));
	return $url;
}

add_action('admin_menu', 'remove_dashboard_menu');
function remove_dashboard_menu() {
	remove_menu_page('index.php');
}
