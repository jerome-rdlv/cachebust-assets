<?php


namespace Rdlv\WordPress\CacheBustAssets;


use Uri\InvalidUriException;
use Uri\Rfc3986\Uri;

class QueryStringBuster extends AbstractBuster
{
	/**
	 * @inerhitDoc
	 */
	public function isCacheBusted(string $url): bool
	{
		try {
			$uri = new Uri($url);
			parse_str($uri->getRawQuery() ?: '', $params);
			return !empty($params['v']);
		} catch (InvalidUriException) {
			return false;
		}
	}

	/**
	 * @inerhitDoc
	 */
	public function removeCacheBusting(string $url): string
	{
		try {
			$uri = new Uri($url);
			parse_str($uri->getRawQuery() ?: '', $params);
			if (array_key_exists('v', $params)) {
				unset($params['v']);
			}
			return $uri->withQuery(http_build_query($params))->toString();
		} catch (InvalidUriException) {
			return $url;
		}
	}

	/**
	 * @inerhitDoc
	 */
	public function addSignatureToUrl(string $url, string $signature): string
	{
		try {
			$uri = new Uri($url);
			parse_str($uri->getRawQuery() ?: '', $params);
			// add cache busting fragment as query string parameter
			$params['v'] = $signature;
			return $uri->withQuery(http_build_query($params))->toString();
		} catch (InvalidUriException) {
			return $url;
		}
	}
}
