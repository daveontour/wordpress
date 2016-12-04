<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, and ABSPATH. You can find more information by visiting
 * {@link http://codex.wordpress.org/Editing_wp-config.php Editing wp-config.php}
 * Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wp');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'aiw2dihsf');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         ')sbSTBCcGrE1Oue.K,=+`7<1_MZMk@K=TR%ZD;Rs} uT9cj=+c&w~BM;5cGp{*s+');
define('SECURE_AUTH_KEY',  'u:Jq_6<.W]?LWm!}VeJYa:k<%Coe:SjU+ofp#~%CKE$`<S`Z-Z.%%w*>0aZitL7v');
define('LOGGED_IN_KEY',    'UumWG_lgVzW:MlMl,~/lX3<2b8Ah7N+,7# n`!g4 GPC!vQrOm6S-l1|F-3>;3gL');
define('NONCE_KEY',        'w s53vh|0+XZfssk`:+%=@Xh_^mT_%.Js,L4+xjM7 zy(SE<-@.3TfA_|Q-o9 Jh');
define('AUTH_SALT',        '|#s)D6VeC~36>@qB|#c<W:#|MivG.PTR-4FN|J[&xsxvOs=@XTGj(E_o&t3QgG1s');
define('SECURE_AUTH_SALT', 'vB_V,RYkZ=xvzwZA_Cn||*S~P-^2*+Q&D8&+;[^K+1YGB2_N!Jl8{O&vkV_a3JTG');
define('LOGGED_IN_SALT',   'AE=&8s51ishc_gp{4P%S=zl_itP(~@`0<|zi`jq2O,kL9&LqA0EbfkBcM+w>.[*2');
define('NONCE_SALT',       '>9F6/~3zX(#fJThX9[,c)*3S%V;tp!$Gh=TGi+qwKT|He7;0f!?ub)DOaZ./Q+{2');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', true);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
