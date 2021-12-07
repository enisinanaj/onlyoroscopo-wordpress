<?php

/**
 * Advanced Ads.
 *
 * @package   Cache_Cleaner
 * @author    Eni Sinanaj <e.sinanaj@digitalhawks.it>
 * @link      https://newlinecode.com
 * @copyright 2013-2019 NewlineCode
 *
 * @wordpress-plugin
 * Plugin Name: CacheCleaner
 * Plugin URI: http://www.newlinecode.com/cachecleaner
 * Description: Clean FastCGI cache for NGinX and eventually Varnish Cache server 
 * Version: 1.0
 * Author: Eni Sinanaj
 * Author URI: http://www.newlinecode.com
 */

// load basic path to the plugin
define( 'CCLEANER_BASE', plugin_basename( __FILE__ ) ); // plugin base as used by WordPress to identify it
define( 'CCLEANER_BASE_PATH', plugin_dir_path( __FILE__ ) );
define( 'CCLEANER_BASE_URL', plugin_dir_url( __FILE__ ) );
define( 'CCLEANER_BASE_DIR', dirname( CCLEANER_BASE ) ); // directory of the plugin without any paths
// general and global slug, e.g. to store options in WP
define( 'CCLEANER_SLUG', 'wp-cache-cleaner' );
define( 'CCLEANER_URL', 'https://newlinecode.com/ccleaner-wp' );
define( 'CCLEANER_VERSION', '1.0' );

/*----------------------------------------------------------------------------*
 * Autoloading, modules and functions
 *----------------------------------------------------------------------------*/

// load public functions (might be used by modules, other plugins or theme)
//require_once CCLEANER_BASE_PATH . 'settings_page.php';

add_action( 'save_post', 'clean_cache' );
function clean_cache($post_ID = null, $post = null, $update = null) {
	
	$header = array (
        "Host: onlyoroscopo.com", // IMPORTANT
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3",
        "Accept-Encoding: gzip,deflate,sdch",
        "Accept-Language: it-IT,it;q=0.8,en-US;q=0.6,en;q=0.4",
        "Cache-Control: max-age=0",
        "Connection: keep-alive",
    );
	
	$curlOptionList = array(
			CURLOPT_URL                     => 'http://40.113.156.139',
			CURLOPT_HTTPHEADER              => $header,
			CURLOPT_CUSTOMREQUEST           => "XCGFULLBAN",
			CURLOPT_VERBOSE                 => true,
			CURLOPT_RETURNTRANSFER          => true,
			CURLOPT_NOBODY                  => true,
			CURLOPT_CONNECTTIMEOUT_MS       => 2000,
	);
	
    $curlHandler = curl_init();
    curl_setopt_array( $curlHandler, $curlOptionList );
    curl_exec( $curlHandler );
    curl_close( $curlHandler );
}

/*if ( is_admin() ){ // admin actions
  add_action( 'admin_menu', 'add_mymenu' );
  add_action( 'admin_init', 'register_mysettings' );
} else {
  // non-admin enqueues, actions, and filters
}

function register_mysettings() { // whitelist options
  register_setting( 'myoption-group', 'new_option_name' );
  register_setting( 'myoption-group', 'some_other_option' );
  register_setting( 'myoption-group', 'option_etc' );
}
*/
