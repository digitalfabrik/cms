<?php

namespace CWWP\DisableNewUserEmails;

/**
 * Kick it off adding hooks.
 */
function bootstrap() {
	add_action( 'password_reset', __NAMESPACE__ . '\\is_first_reset' );
	add_filter( 'newuser_notify_siteadmin', __NAMESPACE__ . '\\ms_new_user_notify' );
}

/**
 * Replacement of wp_new_user_notification for WP 4.2 and earlier.
 *
 * @param int    $user_id        The new user's user ID.
 * @param string $plaintext_pass The user's plaintext password.
 */
function wp_new_user_notification_lt_wp43( $user_id, $plaintext_pass = '' ) {
	if ( empty( $plaintext_pass ) ) {
		return;
	}

	$user       = get_userdata( $user_id );
	$user_login = stripslashes( $user->user_login );
	$user_email = stripslashes( $user->user_email );

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

	/* translators: %s: User login. */
	$message = sprintf( __( 'Username: %s', 'disable-new-user-notifications' ), $user_login ) . "\r\n";
	/* translators: %s: User password. */
	$message .= sprintf( __( 'Password: %s', 'disable-new-user-notifications' ), $plaintext_pass ) . "\r\n";
	$message .= wp_login_url() . "\r\n";

	/* translators: %s: Site title. */
	wp_mail( $user_email, sprintf( __( '[%s] Your username and password', 'disable-new-user-notifications' ), $blogname ), $message );
}

/**
 * Replacement of wp_new_user_notification for WP 4.3 and later.
 *
 * @param int    $user_id    User ID.
 * @param null   $deprecated Not used (argument deprecated).
 * @param string $notify     Optional. Type of notification that should happen. Accepts 'admin' or an empty
 *                           string (admin only), 'user', or 'both' (admin and user). Default empty.
 */
function wp_new_user_notification_gte_wp43( $user_id, $deprecated = null, $notify = '' ) {
	if ( null !== $deprecated ) {
		_deprecated_argument( __FUNCTION__, '4.3.1' );
	}

	// `$deprecated was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't sent a user notification.
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
		// Warning: Do not `use` this as it's unavailable when the file is required!
		$wp_hasher = new \PasswordHash( 8, true );
	}
	$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
	$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

	/* translators: %s: User login. */
	$message  = sprintf( __( 'Username: %s', 'disable-new-user-notifications' ), $user->user_login ) . "\r\n\r\n";
	$message .= __( 'To set your password, visit the following address:', 'disable-new-user-notifications' ) . "\r\n\r\n";
	$message .= '<' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' ) . ">\r\n\r\n";

	$message .= wp_login_url() . "\r\n";

	/* translators: %s: Site title. */
	wp_mail( $user->user_email, sprintf( __( '[%s] Your username and password info', 'disable-new-user-notifications' ), $blogname ), $message );
}

/**
 * Setter and getter for first reset.
 *
 * When setting this runs on the `password_reset` action. The user object
 * is passed and if the default password nag is set then the password reset
 * is considered as happening during the registration process.
 *
 * When getting, the static value is returned. This is used to determine if
 * the email should be sent, see wp_password_change_notification().
 *
 * @param WP_User|null $user User object when setting, null when getting.
 * @return bool Whether password is been set for the first time.
 */
function is_first_reset( $user = null ) {
	static $is_first_reset = false;

	if (
		null !== $user &&
		$user->default_password_nag
	) {
		$is_first_reset = true;
	}

	return $is_first_reset;
}

/**
 * Replacement of wp_password_change_notification() for WordPress.
 *
 * Notify the blog admin of a user changing password, normally via email.
 *
 * This modifies the function so that users setting their password during the
 * registration process does not trigger an email to the admin. Emails for
 * subsequent password changes continue to be sent.
 *
 * The function checks for the default password nag meta key, via is_first_reset(), and
 * if it's true then prevents the email from been sent to the administrator.
 *
 * @param WP_User $user User object.
 */
function wp_password_change_notification( $user ) {
	/*
	 * Do not send notification of password change during registration process or
	 * if the administrators is setting their own password.
	 */
	if (
		is_first_reset() ||
		0 === strcasecmp( $user->user_email, get_option( 'admin_email' ) )
	) {
		return;
	}

	/* translators: %s: User name. */
	$message = sprintf( __( 'Password changed for user: %s', 'disable-new-user-notifications' ), $user->user_login ) . "\r\n";
	// The blogname option is escaped with esc_html() on the way into the database in sanitize_option().
	// We want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

	$wp_password_change_notification_email = array(
		'to'      => get_option( 'admin_email' ),
		/* translators: Password change notification email subject. %s: Site title. */
		'subject' => __( '[%s] Password Changed', 'disable-new-user-notifications' ),
		'message' => $message,
		'headers' => '',
	);

	/**
	 * Filters the contents of the password change notification email sent to the site admin.
	 *
	 * @since 4.9.0
	 *
	 * @param array   $wp_password_change_notification_email {
	 *     Used to build wp_mail().
	 *
	 *     @type string $to      The intended recipient - site admin email address.
	 *     @type string $subject The subject of the email.
	 *     @type string $message The body of the email.
	 *     @type string $headers The headers of the email.
	 * }
	 * @param WP_User $user     User object for user whose password was changed.
	 * @param string  $blogname The site title.
	 */
	$wp_password_change_notification_email = apply_filters( 'wp_password_change_notification_email', $wp_password_change_notification_email, $user, $blogname );

	wp_mail(
		$wp_password_change_notification_email['to'],
		wp_specialchars_decode( sprintf( $wp_password_change_notification_email['subject'], $blogname ) ),
		$wp_password_change_notification_email['message'],
		$wp_password_change_notification_email['headers']
	);
}

/**
 * Prevent wp_mail() from sending by invalidating the from address.
 *
 * This changes the from address in wp_mail() to an empty value in
 * order to prevent the email from sending.
 *
 * As this is intended to run only when sending new user notifications
 * to site admins, the function then unregisters itself from running
 * on subsequent wp_mail() calls.
 *
 * This function runs on the `wp_mail_from()` filter.
 *
 * @param string $from_address The default from address for new user notifications.
 * @return string An invalid email address, an empty string.
 */
function ms_new_user_error_out( $from_address ) {
	// Remove filter to prevent this running on subsequent emails.
	remove_filter( 'wp_mail_from', __NAMESPACE__ . '\\ms_new_user_error_out' );
	return '';
}

/**
 * Preempt wp_mail() to prevent it from sending.
 *
 * This causes wp_mail() to return true prior to sending the email.
 * As this is intended to run only when sending new user notifications
 * to site admins, the function then unregisters itself from running
 * on subsequent wp_mail() calls.
 *
 * This function runs on the `pre_wp_mail` filter.
 *
 * @param null|bool $pre Prior preempted value, null if not preempted by another plugin.
 * @return bool Prempted value if previously set, otherwise true to preempt in this plugin.
 */
function return_true_once( $pre ) {
	remove_filter( 'pre_wp_mail', __NAMESPACE__ . '\\return_true_once', 5 );

	if ( null !== $pre ) {
		// Defer to other plugin.
		return $pre;
	}

	// Fake a successful send.
	return true;
}

/**
 * Prevent notifying admins of new users by invalidating from address.
 *
 * This runs in newuser_notify_siteadmin() to set a hook up to prevent
 * wp_mail() from sending.
 *
 * Prior to WP 5.7 it sets the from address as an invalid email, thus
 * causing wp_mail() to fail to send.
 *
 * In WP 5.7 and above it preempts wp_mail() to indicate a successful send.
 *
 * This function runs on the `newuser_notify_siteadmin` filter.
 *
 * @param string $msg The message to be sent to the site administrator.
 * @return string The unchanged message.
 */
function ms_new_user_notify( $msg ) {
	if ( version_compare( $GLOBALS['wp_version'], '5.7-alpha', '<' ) ) {
		// Invalidate from address prior to WP 5.7.
		add_filter( 'wp_mail_from', __NAMESPACE__ . '\\ms_new_user_error_out' );
		return $msg;
	}

	// Preempt wp_mail() in 5.7 and above.
	add_filter( 'pre_wp_mail', __NAMESPACE__ . '\\return_true_once', 5 );
	return $msg;
}
