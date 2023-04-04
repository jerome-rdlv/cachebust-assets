<?php


namespace Rdlv\WordPress\CacheBustAssets;

abstract class AbstractBuster
{
    const SIGNATURE_TIME = 'timestamp';
    const SIGNATURE_MD5 = 'md5';
    const SIGNATURE_SHA1 = 'sha1';

    private ?string $homeUrl = null;

    private ?string $homePath = null;

    /** @var callable */
    private $filter;

    /**
     * @param string $homeUrl
     * @param string $homePath
     * @return $this
     */
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

    /**
     * @param callable $filter
     * @return $this
     */
    public function setFilter(callable $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * @param string $url
     * @return bool
     */
    public abstract function isCacheBusted(string $url): bool;

    /**
     * @see https://www.php.net/manual/en/function.parse-url.php
     * @param array $parts
     * @return string
     */
    public function buildUrl(array $parts): string
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ':' . $parts['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * @param string $url
     * @return bool
     */
    public function isLocal(string $url): bool
    {
        return strpos($url, $this->homeUrl) === 0;
    }

    /**
     * For a given URL, return the file system path.
     * @param string $url The URL to convert
     * @return string|null The local resource path if it exists, null otherwise
     */
    public function getPath(string $url): ?string
    {
        // remove query string and hash fragment from URL
        $urlParts = parse_url($url);
        unset($urlParts['query']);
        unset($urlParts['fragment']);
        $url = $this->buildUrl($urlParts);

        return $this->homePath . substr($url, strlen($this->homeUrl));
    }

    /**
     * @param string $path File path
     * @param string $mode
     * @return string|null
     */
    public function getSignature(string $path, string $mode = self::SIGNATURE_TIME): ?string
    {
        if (!file_exists($path)) {
            return null;
        }
        switch ($mode) {
            case self::SIGNATURE_MD5:
                return md5_file($path);
            case self::SIGNATURE_SHA1:
                return sha1_file($path);
            case self::SIGNATURE_TIME:
            default:
                return (string)filemtime($path);
        }
    }

    /**
     * @param string $url
     * @param string $mode
     * @return string
     */
    public function cacheBustUrl(string $url, string $mode = self::SIGNATURE_TIME): string
    {
        if ($this->homeUrl === null || $this->homePath === null) {
            error_log("Cachebust-assets error: homeUrl and homePath should be defined. Maybe you called cacheBustUrl to early?");
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

        $signature = $this->getSignature($this->getPath($url), $mode);

        if ($signature === null) {
            // error getting mtime
            return $url;
        }

        return $this->addSignatureToUrl($url, $signature);
    }

    /**
     * @param string $url URL to add cache busting fragment to
     * @param integer $signature File signature
     * @return string Cache busted URL
     */
    public abstract function addSignatureToUrl(string $url, string $signature): string;

    public function cacheBustImageSrc(array $src): array
    {
        $src[0] = $this->cacheBustUrl($src[0]);
        return $src;
    }

    /**
     * @param string $html
     * @return string
     */
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
     * @param array $sources
     * @param $sizeArray
     * @param string $imageSrc
     * @return array
     */
    public function cacheBustSrcset(array $sources, $sizeArray, string $imageSrc): array
    {
        if (!$this->isLocal($imageSrc)) {
            return $sources;
        }

        $signature = $this->getSignature($this->getPath($imageSrc));
        if ($signature === null) {
            return $sources;
        }

        foreach ($sources as &$source) {
            $source['url'] = $this->addSignatureToUrl($source['url'], $signature);
        }

        return $sources;
    }

    /**
     * Add cache busting fragment to an image ACF field sources
     * @param array $image
     * @return array
     */
    public function cacheBustAcfImage(array $image): array
    {
        $signature = $this->getSignature($this->getPath($image['url']));
        if ($signature === null) {
            return $image;
        }

        $image['url'] = $this->addSignatureToUrl($image['url'], $signature);

        foreach ($image['sizes'] as $key => $data) {
            if (is_string($image['sizes'][$key])) {
                $image['sizes'][$key] = $this->addSignatureToUrl($data, $signature);
            }
        }

        return $image;
    }

    /**
     * @param array $meta_tags
     * @return array
     */
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
