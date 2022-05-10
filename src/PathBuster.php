<?php


namespace Rdlv\WordPress\CacheBustAssets;


class PathBuster extends AbstractBuster
{
    /**
     * @inerhitDoc
     */
    public function isCacheBusted($url): bool
    {
        return !!preg_match('/\.v[0-9a-z]+\.[^.]+$/', parse_url($url, PHP_URL_PATH));
    }

    /**
     * @inerhitDoc
     */
    public function addSignatureToUrl(string $url, string $signature): string
    {
        $parts = parse_url($url);
        // add cache busting fragment as url path fragment
        $parts['path'] = preg_replace('/(\.[^.]+)$/', '.v' . $signature . '\1', $parts['path']);
        return $this->buildUrl($parts);
    }
}