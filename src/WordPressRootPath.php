<?php


namespace Rdlv\WordPress\CacheBustAssets;


class WordPressRootPath
{
    public function get($homeUrl, $siteUrl, $absPath)
    {
        if (!empty($homeUrl) && 0 !== strcasecmp($homeUrl, $siteUrl)) {
            $relPath = preg_replace('/[^\/]+/', '..', str_ireplace($homeUrl, '', $siteUrl));
            $relPath = str_replace('/', DIRECTORY_SEPARATOR, $relPath);

            // drop leading slash and assert trailing slash
            $relPath = trim($relPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $home_path = $this->canonicalize($absPath . $relPath);
        } else {
            $home_path = $absPath;
        }

        // assert trailing slash
        return rtrim($home_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * @see https://www.php.net/manual/en/function.realpath.php#84012
     * @param $path
     * @return array|mixed|string
     */
    public function canonicalize($path)
    {
        $path = preg_replace('/[\/\\\\]+/', DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $absolutes);
    }
}