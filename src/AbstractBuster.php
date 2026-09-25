<?php


namespace Rdlv\WordPress\CacheBustAssets;

use Exception;
use Throwable;
use Uri\InvalidUriException;
use Uri\Rfc3986\Uri;

abstract class AbstractBuster
{
	public const string SIGNATURE_TIME = 'timestamp';
	public const string SIGNATURE_MD5 = 'md5';
	public const string SIGNATURE_SHA1 = 'sha1';

	private ?string $homeUrl = null;
	private ?string $homePath = null;

	/** @var ?callable $filter */
	private $filter = null;

	public function setHome(string $homeUrl, string $homePath): AbstractBuster
	{
		if ($homeUrl && strpos($homeUrl, '/', -1) === false) {
			$homeUrl .= '/';
		}
		$this->homeUrl = $homeUrl;

		if ($homePath && strpos($homePath, '/', -1) === false) {
			$homePath .= '/';
		}
		$this->homePath = $homePath;
		return $this;
	}

	public function setFilter(callable $filter): self
	{
		$this->filter = $filter;
		return $this;
	}

	public function cacheBustImageSrc(array $src): array
	{
		$src[0] = $this->cacheBustUrl($src[0]);
		return $src;
	}

	public function cacheBustUrl(string $url, string $mode = self::SIGNATURE_TIME): string
	{
		if (!$this->ready()) {
			trigger_error(
				"Cachebust-assets error: homeUrl and homePath should be defined. Maybe you called cacheBustUrl to early?",
				E_USER_WARNING
			);
		}

		if (!$this->isLocal($url)) {
			// do not cache-bust remote URL
			return $url;
		}

		if ($this->isCacheBusted($url)) {
			// URL is cache-busted already
			return $url;
		}

		if ($this->filter && !call_user_func($this->filter, $url)) {
			return $url;
		}

		try {
			$signature = $this->getSignature($this->getPath($url), $mode);
			return $this->addSignatureToUrl($url, $signature);
		} catch (Throwable) {
			return $url;
		}
	}

	public function ready(): bool
	{
		return $this->homeUrl !== null && $this->homePath !== null;
	}

	public function isLocal(string $url): bool
	{
		return str_starts_with($url, $this->homeUrl);
	}

	abstract public function isCacheBusted(string $url): bool;

	abstract public function removeCacheBusting(string $url): string;

	/**
	 * @throws Exception
	 */
	public function getSignature(string $path, string $mode = self::SIGNATURE_TIME): string
	{
		if (!file_exists($path)) {
			throw new Exception(sprintf('File does not exist: %s', $path));
		}
		return match ($mode) {
			self::SIGNATURE_MD5 => md5_file($path),
			self::SIGNATURE_SHA1 => sha1_file($path),
			default => (string)filemtime($path),
		};
	}

	/**
	 * For a given URL, return the file system path.
	 * @throws InvalidUriException
	 */
	public function getPath(string $url): string
	{
		// remove query string and hash fragment from URL
		return ($this->homePath ?? '') . substr(
				new Uri($url)->withQuery(null)->withFragment(null)->toString(),
				strlen($this->homeUrl ?? '')
			);
	}

	/**
	 * @param string $url URL to add cache busting fragment to
	 * @param string $signature File signature
	 * @return string Cache busted URL
	 */
	abstract public function addSignatureToUrl(string $url, string $signature): string;

	public function cacheBustThumbnail(string $html): string
	{
		return preg_replace_callback(
			'/ src=(?<quote>["\'])(?<url>.*?)\1/i',
			function ($m) {
				return sprintf(
					' src=%1$s%2$s%1$s',
					$m['quote'],
					$this->cacheBustUrl($m['url'])
				);
			},
			$html
		);
	}

	/**
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function cacheBustSrcset(array $sources, $sizeArray, string $imageSrc): array
	{
		if (!$this->isLocal($imageSrc)) {
			return $sources;
		}

		try {
			$signature = $this->getSignature($this->getPath($imageSrc));
			foreach ($sources as &$source) {
				$source['url'] = $this->addSignatureToUrl($source['url'], $signature);
			}
		} catch (Throwable) {
		}

		return $sources;
	}

	/**
	 * Add cache busting fragment to an image ACF field sources
	 */
	public function cacheBustAcfImage(array $image): array
	{
		if (!$this->isLocal($image['url'])) {
			return $image;
		}

		try {
			$signature = $this->getSignature($this->getPath($image['url']));
			$image['url'] = $this->addSignatureToUrl($image['url'], $signature);
			foreach ($image['sizes'] as $key => $data) {
				if (is_string($image['sizes'][$key])) {
					$image['sizes'][$key] = $this->addSignatureToUrl($data, $signature);
				}
			}
		} catch (Throwable) {
		}

		return $image;
	}

	public function cacheBustFavicons(array $meta_tags): array
	{
		return array_map(function ($meta_tag) {
			return preg_replace_callback(
				'/ (?<att>href|content)=(?<quote>["\'])(?<url>.*?)\2/i',
				function ($m) {
					return sprintf(
						' %1$s=%2$s%3$s%2$s',
						$m['att'],
						$m['quote'],
						$this->cacheBustUrl($m['url'])
					);
				},
				$meta_tag
			);
		}, $meta_tags);
	}
}
