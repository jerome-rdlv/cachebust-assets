<?php


namespace Rdlv\WordPress\CacheBustAssets;


class QueryStringBuster extends AbstractBuster
{
    public function isCacheBusted($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        return !empty($params['v']);
    }

    public function addSignatureToUrl($url, $signature)
    {
        $parts = parse_url($url);
        // add cache busting fragment as query string parameter
        $parts['query'] = (isset($parts['query']) ? $parts['query'] . '&' : '') . 'v=' . $signature;
        return $this->buildUrl($parts);
    }

}