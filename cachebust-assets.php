<?php

/*
Plugin Name: CacheBust Assets
Description: Add cache busting fragment to assets URL.
Author: Jérôme Mulsant
Author URI: https://rue-de-la-vieille.fr/
License: MIT License
*/

use Rdlv\WordPress\CacheBustAssets\BusterFactory;

// exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {

    if (!apply_filters('cachebust_assets', !is_admin())) {
        return;
    }

    /* get home path
     * taken from wp-admin/includes/file.php:103
     */
    $home = set_url_scheme(get_option('home'), 'http');
    $siteurl = set_url_scheme(get_option('siteurl'), 'http');
    if (!empty($home) && 0 !== strcasecmp($home, $siteurl)) {
        $wp_path_rel_to_home = str_ireplace($home, '', $siteurl); /* $siteurl - $home */
        $pos = strripos(
            str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']),
            trailingslashit($wp_path_rel_to_home)
        );
        $home_path = substr($_SERVER['SCRIPT_FILENAME'], 0, $pos);
        $home_path = trailingslashit($home_path);
    } else {
        $home_path = ABSPATH;
    }
    $home_path = str_replace('\\', '/', $home_path);
    
    $factory = new BusterFactory($home, $home_path);

    $buster = $factory->create(
        apply_filters('cache_bust_assets_path', false)
    );

    add_filter('script_loader_src', [$buster, 'cacheBustUrl']);
    add_filter('style_loader_src', [$buster, 'cacheBustUrl']);

    add_filter('wp_get_attachment_image_attributes', [$buster, 'cacheBustImageSrc']);
    add_filter('post_thumbnail_html', [$buster, 'cacheBustThumbnail']);
    add_filter('wp_calculate_image_srcset', [$buster, 'cacheBustSrcset'], 10, 3);

    // utilities
    add_filter('cache_bust_src', [$buster, 'cacheBustSrc']);
    add_filter('cache_bust_image', [$buster, 'cacheBustAcfImage']);

});