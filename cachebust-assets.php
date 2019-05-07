<?php

/*
Plugin Name: CacheBust Assets
Description: Add cache busting fragment to assets URL.
Author: Jérôme Mulsant
Author URI: https://rue-de-la-vieille.fr/
License: MIT License
Version: GIT
*/

use Rdlv\WordPress\CacheBustAssets\BusterFactory;
use Rdlv\WordPress\CacheBustAssets\WordPressRootPath;

// exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Rdlv\WordPress\CacheBustAssets\BusterFactory')) {
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    } else {
        error_log('You need to install dependencies with `composer install`.');
        return;
    }
}

add_action('init', function () {

    if (!apply_filters('cachebust_assets_enabled', !is_admin())) {
        return;
    }
    
    // home path resolution
    $home_url = get_option('home');
    $site_url = get_option('siteurl');
    $home_path = (new WordPressRootPath())->get($home_url, $site_url, ABSPATH);
    
    $factory = new BusterFactory($home_url, $home_path);

    $buster = $factory->create(
        apply_filters('cachebust_assets_mode', BusterFactory::MODE_QUERY_STRING)
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