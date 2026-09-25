<?php


namespace Rdlv\WordPress\CacheBustAssets;


use Uri\InvalidUriException;
use Uri\Rfc3986\Uri;

class PathBuster extends AbstractBuster
{
	private const string FRAGMENT_REGEX = '/(\.v[0-9a-z]+)(\.[^.]+)$/';

	/**
	 * @inerhitDoc
	 * @throws InvalidUriException
	 */
	public function isCacheBusted($url): bool
	{
		return !!preg_match(self::FRAGMENT_REGEX, new Uri($url)->getPath());
	}

	/**
	 * @inerhitDoc
	 * @throws InvalidUriException
	 */
	public function removeCacheBusting($url): string
	{
		$uri = new Uri($url);
		return $uri->withPath(preg_replace(self::FRAGMENT_REGEX, '\2', $uri->getPath()))->toString();
	}

	/**
	 * @inerhitDoc
	 * @throws InvalidUriException
	 */
	public function addSignatureToUrl(string $url, $signature): string
	{
		$uri = new Uri($url);
		// add cache busting fragment as url path fragment
		return $uri->withPath(preg_replace('/(\.[^.]+)$/', '.v' . $signature . '\1', $uri->getPath()))->toString();
	}
}
