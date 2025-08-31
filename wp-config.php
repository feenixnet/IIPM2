<?php
define( 'WP_CACHE', true );

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'new-iipm' );

/** Database username */
define( 'DB_USER', 'admin1' );

/** Database password */
define( 'DB_PASSWORD', 'Admin!@#$1234' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

// SMTP Configuration
define('SMTP_USER', '833a6e001@smtp-brevo.com');
define('SMTP_PASS', 'jqRvyQA2mBSLn0NX');
define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_FROM', 'no-reply@revguy.co');
define('SMTP_NAME', 'IIPM Portal');
define('SMTP_PORT', '587');
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);
define('SMTP_DEBUG', 0); // Set to 2 for debugging if needed

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '|,V_fl3c[Dr`DD$<ZbqGCt]GQ2AehMn( %hYikQ<vR;x.[^su{,]T=E6i+;zP^[y' );
define( 'SECURE_AUTH_KEY',  'K9X;_r#dK4}!%9Sdpo/CpDHRlvyXtE(A|.rYd~;MNH,}@VQvQ>~o6fdV+apU]Za4' );
define( 'LOGGED_IN_KEY',    'Q^JU,(`rlMqwJ!/tL/kGrOkUmRBeW7v(K-3[P%SXl-r<:D8S&E{3imDWN_AvWK[,' );
define( 'NONCE_KEY',        'h~pF~U4sp[v7v%?lr!9$D]_g]e(}.T9V>gyH{x_%A#h#ot]D<p:]d1w7;O]kqw5Y' );
define( 'AUTH_SALT',        '/#w--wROP746-OLyM7)5Mjw.c9?,5Wi6E.tATX_6|tBCIZK)rnj^!95mj5kQ|_<q' );
define( 'SECURE_AUTH_SALT', 'k03*mvNzwu*kh_y)Z!V}_^pZT469P_glL4EQzGZ94`rUO}Tws_t{ss#Xmj%^%YKV' );
define( 'LOGGED_IN_SALT',   '`Er&YyTOh`9YM#k([|b? RG=8h|q8^GbZfUW^1}T#)Co`r%ez}-Ob)@+6QX`tu*8' );
define( 'NONCE_SALT',       'G_Xj5UJH6uU]]RB{U UW]30q4jmjraQ.<CX;:WrY48=+@tZW+7x?hd2  D<~+9&V' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

/* Add any custom values between this line and the "stop editing" line. */

define('WP_HOME','http://localhost/new-iipm');
define('WP_SITEURL','http://localhost/new-iipm');

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
