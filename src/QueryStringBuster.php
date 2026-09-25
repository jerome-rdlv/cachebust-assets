<?php


namespace Rdlv\WordPress\CacheBustAssets;


use Uri\InvalidUriException;
use Uri\Rfc3986\Uri;

use function build_query;

class QueryStringBuster extends AbstractBuster
{
	/**
	 * @inerhitDoc
	 * @throws InvalidUriException
	 */
	public function isCacheBusted(string $url): bool
	{
		$uri = new Uri($url);
		parse_str($uri->getRawQuery() ?: '', $params);
		return !empty($params['v']);
	}

	/**
	 * @inerhitDoc
	 * @throws InvalidUriException
	 */
	public function removeCacheBusting($url): string
	{
		$uri = new Uri($url);
		parse_str($uri->getRawQuery() ?: '', $params);
		if (array_key_exists('v', $params)) {
			unset($params['v']);
		}
		return $uri->withQuery(http_build_query($params))->toString();
	}

	/**
	 * @inerhitDoc
	 * @throws InvalidUriException
	 */
	public function addSignatureToUrl(string $url, string $signature): string
	{
		$uri = new Uri($url);
		parse_str($uri->getRawQuery() ?: '', $params);
		// add cache busting fragment as query string parameter
		$params['v'] = $signature;
		return $uri->withQuery(http_build_query($params))->toString();
	}
}
