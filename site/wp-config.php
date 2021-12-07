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
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** MySQL database username */
define( 'DB_USER', 'wordpress' );

/** MySQL database password */
define( 'DB_PASSWORD', '12688ab' );

/** MySQL hostname */
define( 'DB_HOST', 'db' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '#:,tbM?Ip]fOW1/-]&6hMP3K0ES3Wh%(N4rx+b2Ak,qRR5VTi(PHtI-CFw.^MD:o' );
define( 'SECURE_AUTH_KEY',  'Zd(-,{&v=;/2F}9: |9|1p{y^RR9@3k}^(~>9`Z%+XH-02^oVI=zA8wGo{yGqv0N' );
define( 'LOGGED_IN_KEY',    'OI.&3nFZnXijo.bH4vyF7`j<a0u4CrU c$Iy}<D=?c0R*srtQg`&:u0fNa>yYX--' );
define( 'NONCE_KEY',        '3t9Z24I8.y_s2 9Gk!~<a1< c4#n6vS<TzRc0AhQk8-E}wT;>g:,DUfY(,p?cd5h' );
define( 'AUTH_SALT',        'k@hiDjtxVdW_m!f1ijUKz23N1_p.`EE?`+2N UP8CwD]MH+U7I3We}nffgj=PY{:' );
define( 'SECURE_AUTH_SALT', 'GI-kd5j>y}PBUjFZec8*!WD^5/xK_wQ&Y J:Fl_d7W^!:aawy`ozp{Ct8U^_stA ' );
define( 'LOGGED_IN_SALT',   ' I.y8P1v#v57|<me>39f(=obs<G6O,o#k|%-FJEbuNcuSPkMOuHNSN{0Doh::ar]' );
define( 'NONCE_SALT',       '!.v+YPj;c{]$;[c|SxzmEi#mv68V/TkLRD^Ws`8yC6cNY^ERj!@]_d{}!Kv-s#c0' );

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
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if (strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false)
       $_SERVER['HTTPS']='on';

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
