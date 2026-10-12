<?php


namespace Rdlv\WordPress\CacheBustAssets;


use Uri\InvalidUriException;
use Uri\Rfc3986\Uri;

class PathBuster extends AbstractBuster
{
	private const string FRAGMENT_REGEX = '/(\.v[0-9a-z]+)(\.[^.]+)$/';

	/**
	 * @inerhitDoc
	 */
	public function isCacheBusted(string $url): bool
	{
		try {
			return !!preg_match(self::FRAGMENT_REGEX, new Uri($url)->getPath());
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
			return $uri->withPath(preg_replace(self::FRAGMENT_REGEX, '\2', $uri->getPath()))->toString();
		} catch (InvalidUriException) {
			return $url;
		}
	}

	/**
	 * @inerhitDoc
	 */
	public function addSignatureToUrl(string $url, $signature): string
	{
		try {
			$uri = new Uri($url);
			// add cache busting fragment as url path fragment
			return $uri->withPath(preg_replace('/(\.[^.]+)$/', '.v' . $signature . '\1', $uri->getPath()))->toString();
		} catch (InvalidUriException) {
			return $url;
		}
	}
}
