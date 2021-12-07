<?php

/**
 * Facebook Open Graph Meta tags creator.
 *
 * @package   Facebook_OpenGraph
 * @author    Eni Sinanaj <e.sinanaj@digitalhawks.it>
 * @link      https://newlinecode.com
 * @copyright 2013-2019 NewlineCode
 *
 * @wordpress-plugin
 * Plugin Name: Facebook Open Graph Manager
 * Plugin URI: http://www.newlinecode.com/fb-opengraph
 * Description:  Create Facebook Open Graph meta tags to the header  
 * Version: 1.0
 * Author: Eni Sinanaj
 * Author URI: http://www.newlinecode.com
 */

// load basic path to the plugin
define( 'FB_OGRAPH_BASE', plugin_basename( __FILE__ ) ); // plugin base as used by WordPress to identify it
define( 'FB_OGRAPH_BASE_PATH', plugin_dir_path( __FILE__ ) );
define( 'FB_OGRAPH_BASE_URL', plugin_dir_url( __FILE__ ) );
define( 'FB_OGRAPH_BASE_DIR', dirname( FB_OGRAPH_BASE ) ); // directory of the plugin without any paths
// general and global slug, e.g. to store options in WP
define( 'FB_OGRAPH_SLUG', 'wp-cache-cleaner' );
define( 'FB_OGRAPH_URL', 'https://newlinecode.com/fb_opengraph_markup' );
define( 'FB_OGRAPH_VERSION', '1.0' );


use Yoast\WP\SEO\Presenters\Image_Presenter;
use Yoast\WP\SEO\Presenters\Title_Presenter;

/*----------------------------------------------------------------------------*
 * Autoloading, modules and functions
 *----------------------------------------------------------------------------*/

// load public functions (might be used by modules, other plugins or theme)
// require_once FB_OGRAPH_BASE_PATH . 'settings_page.php';

add_action('wp_head', 'addOpenGraphMarkup');
		
function remove_image_presenter( $presenters ) {
	if ($_REQUEST["segno"] == null) {
		return;
	}
	
    return array_map( function( $presenter ) {
//         if ( ! $presenter instanceof Image_Presenter && ! $presenter instanceof Title_Presenter ) {
//             return $presenter;
//         }
		return;
    }, $presenters );
}

add_action( 'wpseo_frontend_presenters', 'remove_image_presenter' );

function addOpenGraphMarkup() {	
    $start_time = microtime(true);
	$post = get_post();
	
	if ($post == null || $_REQUEST["segno"] != null) {
		$cat = get_category_by_slug($_REQUEST["segno"]);
		$posts = get_posts(array("category" => $cat->cat_ID, "numberposts" => 1));
		$post = $posts[0];
	} else {
		return;
	}
	
	$post_categories = wp_get_post_categories($post->ID);
	$post_tax = wp_get_post_tags($post->ID);
	
	$elements = explode("-", $post->post_name);
	$periodo = $elements[1];
	$segno = $elements[2];
	
	$imagePath = strtolower($segno) . "_categoria";
	$imageMeta = '<meta property="og:image" content="' . get_site_url() 
		. '/wp-content/uploads/2020/12/' . $imagePath . '.png?v='. time() .'" />' . "\n";

	if (false && $category->name == 'Giornaliero') {
		$imagePath .= "-" . $stelline;	
	}
	
	$metaTags = "<!-- OG Tags down --> \n";
	$metaTags .= '<meta property="og:title" content="' . $post->post_title . '" />' . "\n";
	$metaTags .= '<meta property="og:site_name" content="Only Oroscopo" />' . "\n";
	$metaTags .= '<title>Only Oroscopo - Oroscopo ' . $periodo . '</title>' . "\n";
	$metaTags .= '<meta property="og:url" content="' . get_site_url() . "/" . $periodo . '/?segno=' . $_REQUEST["segno"] . '" />' . "\n";
	$metaTags .= '<meta property="og:type" content="article" />' . "\n";
	$metaTags .= $imageMeta;
	$metaTags .= '<meta property="og:description" content="Leggi l\'oroscopo! Clicca sulla foto!" />' . "\n";
	$metaTags .= '<meta property="og:image:width" content="849" />' . "\n";
	$metaTags .= '<meta property="og:image:height" content="441" />' ."\n";
	
	echo $metaTags . "\n";
}

