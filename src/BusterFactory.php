<?php


namespace Rdlv\WordPress\CacheBustAssets;


use Exception;

class BusterFactory
{
    const MODE_PATH = 'path';
    const MODE_QUERY_STRING = 'query_string';

    private $homeUrl;
    private $homePath;
    private $filter;

    public function __construct($homeUrl, $homePath, callable $filter = null)
    {
        $this->homeUrl = $homeUrl;
        $this->homePath = $homePath;
        $this->filter = $filter;
    }

    /**
     * @param string $mode Busting mode
     * @return AbstractBuster
     * @throws Exception
     */
    public function create($mode)
    {
        switch ($mode) {
            case self::MODE_PATH:
                $buster = new PathBuster();
                break;
            case self::MODE_QUERY_STRING:
                $buster = new QueryStringBuster();
                break;
            default:
                throw new Exception(sprintf("Mode '%s' not supported", $mode));
        }
        $buster->setHome($this->homeUrl, $this->homePath);

        if ($this->filter) {
            $buster->setFilter($this->filter);
        }
        return $buster;
    }
}