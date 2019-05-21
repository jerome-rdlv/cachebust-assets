<?php


namespace Rdlv\WordPress\CacheBustAssets;

abstract class AbstractBuster
{
    private $homeUrl = '';
    private $homePath = '';

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

    public abstract function isCacheBusted($url);

    /**
     * @param string $path Resource path
     * @return false|int Resource last modification time, or false
     */
    public function getMtime($path)
    {
        if (!file_exists($path)) {
            return false;
        }
        return filemtime($path);
    }

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

    public function cacheBustUrl($url)
    {
        if (!$this->isLocal($url)) {
            // do not cachebust remote URL
            return $url;
        }

        if ($this->isCacheBusted($url)) {
            // URL is cachebusted already
            return $url;
        }

        $mtime = $this->getMtime($this->getPath($url));

        if ($mtime === false) {
            // error getting mtime
            return $url;
        }

        return $this->addTimeToUrl($url, $mtime);
    }

    /**
     * @param string $url The URL to add cache busting fragment to
     * @param integer $mtime The last modification time of the resource
     * @return string The cache busted URL
     */
    public abstract function addTimeToUrl($url, $mtime);

    public function cacheBustImageSrc($src)
    {
        $src[0] = $this->cacheBustUrl($src[0]);
        return $src;
    }

    /**
     * @param string $html
     * @return string
     */
    public function cacheBustThumbnail($html)
    {
        return preg_replace_callback('/src=(["\'])(?<url>.*?)\1/i', function ($m) {
            return sprintf('src="%s"', $this->cacheBustUrl($m['url']));
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

        $mtime = $this->getMtime($this->getPath($image_src));
        if ($mtime === false) {
            return $sources;
        }

        foreach ($sources as $key => &$source) {
            $source['url'] = $this->addTimeToUrl($source['url'], $mtime);
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
        $mtime = $this->getMtime($this->getPath($image['url']));
        if ($mtime === false) {
            return $image;
        }

        $image['url'] = $this->addTimeToUrl($image['url'], $mtime);

        foreach ($image['sizes'] as $key => $data) {
            if (is_string($image['sizes'][$key])) {
                $image['sizes'][$key] = $this->addTimeToUrl($data, $mtime);
            }
        }

        return $image;
    }
}
