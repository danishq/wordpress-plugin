<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define( 'AUTH_KEY',          'n1&I9!<T7uff54g*+e-Q-^!U[@ azZ+@ECU$;IV*6b{8aH0CiM>upod2w)#8)7kf' );
define( 'SECURE_AUTH_KEY',   'u+DQ}U/DZ*pz~!evrOg!*!c9^Ho6E438M/W? aGterc82{1i-):$n,)gP:dY$D>N' );
define( 'LOGGED_IN_KEY',     'MUZ;Y6:UxxIEZ<j*LcyTeMPjo@q?R90/q-4Gf!+D{awZNptI424nm4S,baHx|7}M' );
define( 'NONCE_KEY',         'M&gl$0WB{b-$G]Viq^-o;|m+m`ITZFCbOw}<~w=5>Dm>iLfND Z(E0c(.b&CS,ZK' );
define( 'AUTH_SALT',         '_A#B@/*Nr6O=mnL;8J`-yl1[D8`quR(`9}D}W:vC7|Vn7F{8k|bGZG3QBHo{]i8/' );
define( 'SECURE_AUTH_SALT',  'Vkpj`{S)?7oU 7Y-w$<U(VXg%.`h]TQ1n!tj.j<13ep?/hCGSE<1m+a$^xg>civ!' );
define( 'LOGGED_IN_SALT',    ':C~I_^iaiFHU2ho.H3q_#pSmT&VJ$WQQZ|U{&rI]b1z6+e4Ds+>i>:-@`CTX8l0z' );
define( 'NONCE_SALT',        'JE-k}EO,#1ph{4iJ}8[HtUJM?wlTAY}RF$iklo~c?{^cT=h^4:8KW. ZqO`It*~9' );
define( 'WP_CACHE_KEY_SALT', '|yAjReiku%MSCg:KUyg8[yk[<e1}K.,JC#7p)RcM!Y@NvDFKsp:xx!RMHh@gVxjl' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}

if ( ! defined( 'WP_DEBUG_LOG' ) ) {
	define( 'WP_DEBUG_LOG', true );
}

if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
	define( 'WP_DEBUG_DISPLAY', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
