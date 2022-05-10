<?php


namespace Rdlv\WordPress\CacheBustAssets;


class QueryStringBuster extends AbstractBuster
{
    /**
     * @inerhitDoc
     */
    public function isCacheBusted(string $url): bool
    {
        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $params);
        return !empty($params['v']);
    }

    /**
     * @inerhitDoc
     */
    public function addSignatureToUrl(string $url, string $signature): string
    {
        $parts = parse_url($url);
        // add cache busting fragment as query string parameter
        $parts['query'] = (isset($parts['query']) ? $parts['query'] . '&' : '') . 'v=' . $signature;
        return $this->buildUrl($parts);
    }

}