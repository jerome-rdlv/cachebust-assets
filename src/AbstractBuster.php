<?php


namespace Rdlv\WordPress\CacheBustAssets;

abstract class AbstractBuster
{
    const SIGNATURE_TIME = 'timestamp';
    const SIGNATURE_MD5 = 'md5';
    const SIGNATURE_SHA1 = 'md5';

    /** @var string */
    private $homeUrl = '';

    /** @var string */
    private $homePath = '';

    /** @var callable */
    private $filter;

    public function setHome($homeUrl, $homePath)
    {
        if (strpos($homeUrl, '/', -1) === false) {
            $homeUrl .= '/';
        }
        $this->homeUrl = $homeUrl;

        if (strpos($homePath, '/', -1) === false) {
            $homePath .= '/';
        }
        $this->homePath = $homePath;
    }

    public function setFilter(callable $filter)
    {
        $this->filter = $filter;
    }

    public abstract function isCacheBusted($url);

    /**
     * @see https://www.php.net/manual/en/function.parse-url.php
     * @param $parts
     * @return string
     */
    public function buildUrl($parts)
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = isset($parts['host']) ? $parts['host'] : '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user = isset($parts['user']) ? $parts['user'] : '';
        $pass = isset($parts['pass']) ? ':' . $parts['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parts['path']) ? $parts['path'] : '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    public function isLocal($url)
    {
        return strpos($url, $this->homeUrl) === 0;
    }

    /**
     * For a given URL, return the file system path.
     * @param string $url The URL to convert
     * @return string|null The local resource path if it exists, null otherwise
     */
    public function getPath($url)
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
     * @return false|string
     */
    public function getSignature($path, $mode = self::SIGNATURE_TIME)
    {
        if (!file_exists($path)) {
            return false;
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

    public function cacheBustUrl($url, $mode = self::SIGNATURE_TIME)
    {
        if (!$this->isLocal($url)) {
            // do not cachebust remote URL
            return $url;
        }

        if ($this->isCacheBusted($url)) {
            // URL is cachebusted already
            return $url;
        }

        if ($this->filter && !call_user_func($this->filter, $url)) {
            return $url;
        }

        $signature = $this->getSignature($this->getPath($url), $mode);

        if ($signature === false) {
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
    public abstract function addSignatureToUrl($url, $signature);

    public function cacheBustImageSrc($src)
    {
        $src[0] = $this->cacheBustUrl($src[0]);
        return $src;
    }

    /**
     * @param array $attr Image attributes array: src, class, alt, sizes, srcset
     * @return array
     */
    public function cacheBustImageAttributes($attr)
    {
        $attr['src'] = $this->cacheBustUrl($attr['src']);
        return $attr;
    }

    /**
     * @param string $html
     * @return string
     */
    public function cacheBustThumbnail($html)
    {
        return preg_replace_callback('/ src=(?<quote>["\'])(?<url>.*?)\1/i', function ($m) {
            return sprintf(' src=%1$s%2$s%1$s', $m['quote'], $this->cacheBustUrl($m['url']));
        }, $html);
    }

    /**
     * @param array $sources
     * @param $size_array
     * @param $image_src
     * @return array
     */
    public function cacheBustSrcset(
        /** @noinspection PhpUnusedParameterInspection */
        $sources,
        $size_array,
        $image_src
    ) {
        if (!$this->isLocal($image_src)) {
            return $sources;
        }

        $mtime = $this->getSignature($this->getPath($image_src));
        if ($mtime === false) {
            return $sources;
        }

        foreach ($sources as $key => &$source) {
            $source['url'] = $this->addSignatureToUrl($source['url'], $mtime);
        }

        return $sources;
    }

    /**
     * Add cache busting fragment to an image ACF field sources
     * @param array $image
     * @return array
     */
    public function cacheBustAcfImage($image)
    {
        $mtime = $this->getSignature($this->getPath($image['url']));
        if ($mtime === false) {
            return $image;
        }

        $image['url'] = $this->addSignatureToUrl($image['url'], $mtime);

        foreach ($image['sizes'] as $key => $data) {
            if (is_string($image['sizes'][$key])) {
                $image['sizes'][$key] = $this->addSignatureToUrl($data, $mtime);
            }
        }

        return $image;
    }

    public function cacheBustFavicons($meta_tags)
    {
        return array_map(function ($meta_tag) {
            return preg_replace_callback('/ (?<att>href|content)=(?<quote>["\'])(?<url>.*?)\2/i', function ($m) {
                return sprintf(' %1$s=%2$s%3$s%2$s', $m['att'], $m['quote'], $this->cacheBustUrl($m['url']));
            }, $meta_tag);
        }, $meta_tags);
    }
}
