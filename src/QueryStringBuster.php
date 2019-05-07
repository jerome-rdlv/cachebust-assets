<?php


namespace Rdlv\WordPress\CacheBustAssets;


class QueryStringBuster extends AbstractBuster
{
    public function isCacheBusted($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        return !empty($params['v']);
    }

    public function addTimeToUrl($url, $mtime)
    {
        $parts = parse_url($url);
        // add cache busting fragment as query string parameter
        $parts['query'] = (isset($parts['query']) ? $parts['query'] . '&' : '') . 'v=' . $mtime;
        return $this->buildUrl($parts);
    }

}