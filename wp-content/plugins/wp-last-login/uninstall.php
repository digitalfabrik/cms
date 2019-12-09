<?php
/**
 * Uninstall.
 *
 * @package WP Last Login
 */

// Don't uninstall unless you absolutely want to!
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	wp_die( 'WP_UNINSTALL_PLUGIN undefined.' );
}

$user_ids = get_users( array(
	'blog_id' => '',
	'fields'  => 'ID',
) );

foreach ( $user_ids as $user_id ) {
	// phpcs:ignore WordPress.VIP.RestrictedFunctions.user_meta_delete_user_meta
	delete_user_meta( $user_id, 'wp-last-login' );
}


/* Goodbye! Thank you for having me! */
