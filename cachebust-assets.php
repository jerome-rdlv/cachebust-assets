<?php

add_action('wp_loaded', array(CacheBustAssets::getInstance(), 'init'));

class CacheBustAssets
{
    private static $instance = null;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init()
    {
        if ($this->doCacheBust()) {
            add_filter('script_loader_src', [$this, 'cacheBustScript']);
            add_filter('style_loader_src', [$this, 'cacheBustScript']);
            add_filter('cache_bust_src', [$this, 'cacheBustSrc']);
            add_filter('cache_bust_image', [$this, 'cacheBustImage']);

            add_filter('wp_get_attachment_image_attributes', [$this, 'cacheBustImageSrc'], 10, 3);
            add_filter('post_thumbnail_html', [$this, 'cacheBustThumbnail'], 10, 5);
            add_filter('wp_calculate_image_srcset', [$this, 'cacheBustSrcset'], 10, 5);
        }
    }

    private function doCacheBust()
    {
        return apply_filters('cachebust_assets', !is_admin());
    }

    public function cacheBustScript($src)
    {
        if (!$this->doCacheBust()) {
            return $src;
        }

        if (strpos($src, get_home_url()) === false) {
            // file out of domain, do not change
            return $src;
        }
        if (strpos($src, get_site_url() . '/wp-includes') !== false) {
            // wp file, do not change
            return $src;
        }

        return $this->cacheBustSrc($src);
    }

    public function cacheBustSrcset(
        /** @noinspection PhpUnusedParameterInspection */
        $sources,
        $size_array,
        $image_src,
        $image_meta,
        $attachment_id
    ) {
        if (!$this->doCacheBust()) {
            return $sources;
        }

        $path = wp_upload_dir()['basedir'] . '/' . $image_meta['file'];
        if (file_exists($path)) {
            $mtime = filemtime($path);
            if ($mtime) {
                foreach ($sources as $key => &$data) {
                    $data['url'] = $this->cacheBust($data['url'], $mtime);
                }
            }
        }

        return $sources;
    }

    public function cacheBustThumbnail($html)
    {
        if (!$this->doCacheBust()) {
            return $html;
        }

        $html = preg_replace_callback('/src="([^"]+)"/i', function ($matches) {
            if (preg_match('/\.v[^.]+\.[^.]$/', $matches[1])) {
                return $matches[0];
            } else {
                return 'src="' . $this->cacheBustSrc($matches[1]) . '"';
            }
        }, $html);
        return $html;
    }

    private function isLocal($url)
    {
        return strpos($url, get_home_url()) === 0;
    }

    public function cacheBustImage($image)
    {
        if (!$this->doCacheBust()) {
            return $image;
        }

        $path = $this->getPath($image['url']);
        if (file_exists($path)) {
            $mtime = filemtime($path);
            if ($mtime) {
                foreach ($image['sizes'] as $key => $data) {
                    if (is_string($image['sizes'][$key])) {
                        $image['sizes'][$key] = $this->cacheBust($data, $mtime);
                    }
                }
            }
        }

        return $image;
    }

    public function cacheBustSrc($src, $path = null)
    {
        if (!$this->doCacheBust()) {
            return $src;
        }

        $src = preg_replace('/\?.*$/', '', $src);
        if (!$path) {
            $path = $this->getPath($src);
        }

        // return if cache busted already
        if (preg_match('/\.v[0-9a-z]{8,}\.[^.]+$/', $path)) {
            return $src;
        }

        if (!file_exists($path)) {
            return $src;
        }

        // return if filemtime fails
        $mtime = filemtime($path);
        if ($mtime === false) {
            return $src;
        }

        return $this->cacheBust($src, $mtime);
    }

    public function cacheBustImageSrc($attr)
    {
        if (!$this->doCacheBust()) {
            return $attr;
        }

        $path = $this->getPath($attr['src']);
        if (file_exists($path)) {
            $mtime = filemtime($path);
            if ($mtime) {
                $attr['src'] = $this->cacheBust($attr['src'], $mtime);
            }
        }

        return $attr;
    }

    private function cacheBust($src, $mtime)
    {
        if (defined('CACHEBUST_PATH') && CACHEBUST_PATH) {
            // cache bust as url path fragment
            $src = preg_replace('/\?.*$/', '', $src);
            if ($mtime) {
                return preg_replace(
                    '/\.([^.]+)$/i',
                    '.v' . $mtime . '.\1',
                    $src
                );
            } else {
                return $src;
            }
        } else {
            // cache bust as query string parameter
            $parts = parse_url($src);
            return preg_replace(
                '/(\?.*|$)/',
                (isset($parts['query']) ? $parts['query'] . '&' : '?') . 'v=' . $mtime,
                $src
            );
        }
    }

    private function getPath($src)
    {
        if ($this->isLocal($src)) {
            $path = realpath(str_replace(get_home_url(), dirname($_SERVER['SCRIPT_FILENAME']), $src));
            return $path ? $path : $src;
        } else {
            return $src;
        }
    }
}
