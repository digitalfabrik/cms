<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

$configuration = parse_ini_file("config.ini", true);

// ** MySQL settings - You can get this info from your web host ** //
$db_configuration = $configuration['database'];
/** The name of the database for WordPress */
define('DB_NAME', $db_configuration['name']);

/** MySQL database username */
define('DB_USER', $db_configuration['user']);

/** MySQL database password */
define('DB_PASSWORD', $db_configuration['password']);

/** MySQL hostname */
define('DB_HOST', $db_configuration['host']);

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', $db_configuration['charset']);

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', $db_configuration['collate']);

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'put your unique phrase here');
define('SECURE_AUTH_KEY', 'put your unique phrase here');
define('LOGGED_IN_KEY', 'put your unique phrase here');
define('NONCE_KEY', 'put your unique phrase here');
define('AUTH_SALT', 'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT', 'put your unique phrase here');
define('NONCE_SALT', 'put your unique phrase here');
define( 'OTGS_INSTALLER_SITE_KEY_WPML', 'your-site-key' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);
// Enable Debug logging to the /wp-content/debug.log file
define('WP_DEBUG_LOG', true);

/* Trash */
define('EMPTY_TRASH_DAYS', 99999); // "never" delete content

/* Multisite */
$multisite_config = $configuration['multisite'];
define('WP_ALLOW_MULTISITE', true);
define('MULTISITE', true);
define('SUBDOMAIN_INSTALL', false);
define('DOMAIN_CURRENT_SITE', $multisite_config['domain_current_site']);
define('PATH_CURRENT_SITE', $multisite_config['path_current_site']);
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);

/* Translation */
$translation_config = $configuration['translation-microsoft'];
define('TRANSLATION_MICROSOFT_CLIENT_ID', $translation_config['client-id']);
define('TRANSLATION_MICROSOFT_CLIENT_SECRET', $translation_config['client-secret']);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH'))
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

define( 'AUTOMATIC_UPDATER_DISABLED', true );
