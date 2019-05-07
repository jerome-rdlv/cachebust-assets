<?php


use PHPUnit\Framework\TestCase;
use Rdlv\WordPress\CacheBustAssets\PathBuster;

class PathBusterTest extends TestCase
{
    public function testIsCacheBusted()
    {
        $this->assertTrue((new PathBuster())->isCacheBusted(
            'http://example.org/app/theme/default/main.min.v1557248448.js'
        ));
    }
    
    public function testIsNotCacheBusted()
    {
        $this->assertFalse((new PathBuster())->isCacheBusted(
            'http://example.org/app/theme/default/main.min.js'
        ));
    }

    public function testIsNotCacheBustedWhenTimeIsNotAtTheEnd()
    {
        $this->assertFalse((new PathBuster())->isCacheBusted(
            'http://example.org/app/theme/default/main.v1557248699.min.js'
        ));
    }
    
    public function testAddTimeToUrl()
    {
        $time = 1557248558;
        $this->assertEquals(
            "http://example.org/main.v$time.js",
            (new PathBuster())->addTimeToUrl('http://example.org/main.js', $time)
        );
    }

    public function testAddTimeToUrlWithQueryString()
    {
        $time = 1557248558;
        $this->assertEquals(
            "http://example.org/main.v$time.js?param1=val1",
            (new PathBuster())->addTimeToUrl('http://example.org/main.js?param1=val1', $time)
        );
    }
}