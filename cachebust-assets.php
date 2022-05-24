<?php

/*
 * Plugin Name: CacheBust Assets
 * Plugin URI: https://github.com/jerome-rdlv/cachebust-assets
 * Description: Add cache busting fragment to assets URL.
 * Author: Jérôme Mulsant
 * Author URI: https://rue-de-la-vieille.fr/
 * License: MIT License
 * Version: GIT
*/

use Rdlv\WordPress\CacheBustAssets\BusterFactory;
use Rdlv\WordPress\CacheBustAssets\WordPressRootPath;
use Rdlv\WordPress\Registry\Registry;

// Prevent direct execution.
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists(BusterFactory::class)) {
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    } else {
        error_log('You need to install dependencies with `composer install`.');
        return;
    }
}

$buster = (new BusterFactory())->create(getenv('CACHEBUST_MODE') ?: BusterFactory::MODE_QUERY_STRING);

if (class_exists(Registry::class)) {
    Registry::set($buster, 'cachebuster');
}

add_action('init', function () use ($buster) {
    if (!apply_filters('cachebust_assets_enabled', !is_admin())) {
        return;
    }

    // home path resolution
    $home_url = get_option('home');
    $site_url = get_option('siteurl');
    $home_path = (new WordPressRootPath())->get($home_url, $site_url, ABSPATH);

    $buster->setHome($home_url, $home_path);
    $buster->setFilter(function ($url) {
        return apply_filters('cachebust_url', true, $url);
    });

    // default filters
    add_filter('cachebust_url', function ($cachebust, $url) {
        return strpos($url, '/wp/') === false;
    },         5, 2);
    add_filter('cachebust_assets_enabled', function () {
        return !is_admin() && $GLOBALS['pagenow'] !== 'wp-login.php';
    });

    add_filter('script_loader_src', [$buster, 'cacheBustUrl']);
    add_filter('style_loader_src', [$buster, 'cacheBustUrl']);

    add_filter('post_thumbnail_html', [$buster, 'cacheBustThumbnail']);
    add_filter('wp_calculate_image_srcset', [$buster, 'cacheBustSrcset'], 10, 3);

    add_filter('site_icon_meta_tags', [$buster, 'cacheBustFavicons']);

    // utilities
   
    /**
     * @deprecated Should get buster service from Registry
     */
    add_filter('cache_bust_url', [$buster, 'cacheBustUrl'], 10, 2);

    /**
     * @deprecated Should get buster service from Registry
     */
    add_filter('cache_bust_acf_image', [$buster, 'cacheBustAcfImage']);
});