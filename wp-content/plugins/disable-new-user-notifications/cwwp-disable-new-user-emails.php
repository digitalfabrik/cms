<?php
/**
 * Plugin Name: Disable New User Notifications
 * Plugin URI:  https://thomasgriffin.io
 * Description: Disables new user notification emails.
 * Author:      Thomas Griffin
 * Author URI:  http://thomasgriffin.io
 * Version:     1.0.2
 *
 * Disable New User Notifications is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Disable New User Notifications is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Disable New User Notifications. If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'wp_new_user_notification' ) ) {
	if ( version_compare( $GLOBALS['wp_version'], '4.3', '<' ) ) {
		// This definition is for WP versions before 4.3. 4.3 broke this function badly, and 4.4 did no better at fixing it.
		function wp_new_user_notification( $user_id, $plaintext_pass = '' ) {
			if ( empty( $plaintext_pass ) ) {
				return;
			}

			$user 		= get_userdata( $user_id );
			$user_login = stripslashes( $user->user_login );
			$user_email = stripslashes( $user->user_email );

			// The blogname option is escaped with esc_html on the way into the database in sanitize_option
			// we want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

			$message  = sprintf( __( 'Username: %s' ), $user_login ) . "\r\n";
			$message .= sprintf( __( 'Password: %s' ), $plaintext_pass ) . "\r\n";
			$message .= wp_login_url() . "\r\n";

			wp_mail( $user_email, sprintf( __( '[%s] Your username and password' ), $blogname ), $message );
		}
	} else {
		// This definition is for WP versions after 4.3.
		function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {
			if ( $deprecated !== null ) {
				_deprecated_argument( __FUNCTION__, '4.3.1' );
			}

			// `$deprecated was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't sent a user notifcation.
			if ( 'admin' === $notify || ( empty( $deprecated ) && empty( $notify ) ) ) {
				return;
			}

			global $wpdb, $wp_hasher;
			$user = get_userdata( $user_id );

			// The blogname option is escaped with esc_html on the way into the database in sanitize_option
			// we want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

			// Generate something random for a password reset key.
			$key = wp_generate_password( 20, false );

			/** This action is documented in wp-login.php */
			do_action( 'retrieve_password_key', $user->user_login, $key );

			// Now insert the key, hashed, into the DB.
			if ( empty( $wp_hasher ) ) {
				require_once ABSPATH . WPINC . '/class-phpass.php';
				$wp_hasher = new PasswordHash( 8, true );
			}
			$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
			$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

			$message = sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
			$message .= __( 'To set your password, visit the following address:' ) . "\r\n\r\n";
			$message .= '<' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' ) . ">\r\n\r\n";

			$message .= wp_login_url() . "\r\n";

			wp_mail( $user->user_email, sprintf( __( '[%s] Your username and password info' ), $blogname ), $message );
		}
	}
}

require plugin_dir_path( __FILE__ ) . 'utils.php';