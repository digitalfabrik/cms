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
//add_action ('shutdown','change_403_codes');

function _access_denied_splash200(){
	if ( ! is_user_logged_in() || is_network_admin() )
		return;

	$blogs = get_blogs_of_user( get_current_user_id() );

	if ( wp_list_filter( $blogs, array( 'userblog_id' => get_current_blog_id() ) ) )
		return;

	$blog_name = get_bloginfo( 'name' );

	if ( empty( $blogs ) )
		wp_die( sprintf( __( 'Bitte wählen Sie eine Kommune.' ), $blog_name ), 403 );

	$output = '<p>Es ist noch keine Kommune ausgew&auml;hlt worden.</p>';

	$output .= '<h3>' . __('Die f&uuml;r Sie w&auml;hlbaren Kommunen') . '</h3>';
	$output .= '<table>';

	foreach ( $blogs as $blog ) {
		$output .= '<tr>';
		$output .= "<td>{$blog->blogname}</td>";
		$output .= '<td><a href="' . esc_url( get_admin_url( $blog->userblog_id ) ) . '">' . __( 'Dashboard besuchen' ) . '</a>';
		$output .= '</tr>';
	}

	$output .= '</table>';

	wp_die( $output, 200 );
}
add_action( 'admin_page_access_denied', '_access_denied_splash200', 1 );

?>
