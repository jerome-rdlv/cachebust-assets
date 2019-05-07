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

    function canonicalize($path)
    {
        $path = explode('/', $path);
        $keys = array_keys($path, '..');

        foreach ($keys as $keypos => $key) {
            array_splice($path, $key - ($keypos * 2 + 1), 2);
        }

        $path = implode('/', $path);
        $path = str_replace('./', '', $path);
        return $path;
    }
}