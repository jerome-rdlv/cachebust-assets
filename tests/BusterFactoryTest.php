<?php


use PHPUnit\Framework\TestCase;
use Rdlv\WordPress\CacheBustAssets\BusterFactory;
use Rdlv\WordPress\CacheBustAssets\PathBuster;
use Rdlv\WordPress\CacheBustAssets\QueryStringBuster;

class BusterFactoryTest extends TestCase
{
    public function testCreatePathBuster()
    {
        $factory = new BusterFactory();
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertInstanceOf(PathBuster::class, $factory->create(BusterFactory::MODE_PATH));
    }

    public function testCreateQueryStringBuster()
    {
        $factory = new BusterFactory();
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertInstanceOf(QueryStringBuster::class, $factory->create(BusterFactory::MODE_QUERY_STRING));
    }

    public function testUnknownBuster()
    {
        $mode = 'unknown-mode';
        $this->expectException(Exception::class);
        $factory = new BusterFactory();
        $factory->create($mode);
    }
}