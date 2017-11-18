<?php
/**
 * Plugin Name: cms.integreat-app.de domain fix
 * Description: Replaces domain names where appropriate
 * Version: 1.0
 * Author: Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

add_filter( 'wp_mail', 'ig_mail_filter', 1000);
add_filter( 'password_change_email', 'ig_mail_filter', 1000);
function ig_mail_filter( $args ) {
	$new_wp_mail = array(
		'to'          => $args['to'],
		'subject'     => $args['subject'],
		'message'     => str_replace("http://vmkrcmar21.informatik.tu-muenchen.de/wordpress", "https://cms.integreat-app.de", $args['message']),
		'headers'     => $args['headers'],
	);
	return $new_wp_mail;
}

?>
