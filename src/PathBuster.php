<?php


namespace Rdlv\WordPress\CacheBustAssets;


class PathBuster extends AbstractBuster
{
    public function isCacheBusted($url)
    {
        return !!preg_match('/\.v[0-9a-z]+\.[^.]+$/', parse_url($url, PHP_URL_PATH));
    }

    public function addSignatureToUrl($url, $time)
    {
        $parts = parse_url($url);
        // add cache busting fragment as url path fragment
        $parts['path'] = preg_replace('/(\.[^.]+)$/', '.v' . $time . '\1', $parts['path']);
        return $this->buildUrl($parts);
    }
}