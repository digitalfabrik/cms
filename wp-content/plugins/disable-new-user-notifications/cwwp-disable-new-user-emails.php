<?php
/**
 * Plugin Name: Disable New User Notifications
 * Plugin URI:  https://thomasgriffin.io
 * Description: Disables new user notification emails.
 * Author:      Thomas Griffin
 * Author URI:  http://thomasgriffin.io
 * Version:     2.0.0
 * Requires at least: 4.6
 * Requires PHP: 5.3
 * License: GNU General Public License v2.0 or later
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

use CWWP\DisableNewUserEmails;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/inc/namespace.php';

if ( ! function_exists( 'wp_new_user_notification' ) ) {
	function wp_new_user_notification( $user_id, $plaintext_pass = null, $notify = '' ) {
		if ( version_compare( $GLOBALS['wp_version'], '4.3', '<' ) ) {
			if ( null === $plaintext_pass ) {
				$plaintext_pass = '';
			}
			return DisableNewUserEmails\wp_new_user_notification_lt_wp43( $user_id, $plaintext_pass );
		}

		// $plaintext_pass is deprecated.
		return DisableNewUserEmails\wp_new_user_notification_gte_wp43( $user_id, $plaintext_pass, $notify );
	}
}

if ( ! function_exists( 'wp_password_change_notification' ) ) {
	function wp_password_change_notification( $user ) {
		return DisableNewUserEmails\wp_password_change_notification( $user );
	}
}

DisableNewUserEmails\bootstrap();
