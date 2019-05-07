<?php


use PHPUnit\Framework\TestCase;
use Rdlv\WordPress\CacheBustAssets\QueryStringBuster;

class QueryStringBusterTest extends TestCase
{
    public function testIsCacheBusted()
    {
        $this->assertTrue((new QueryStringBuster())->isCacheBusted(
            'http://example.org/app/theme/default/main.min.js?param1=val1&v=1557248782&param2=val2'
        ));
    }
    
    public function testIsNotCacheBusted()
    {
        $this->assertFalse((new QueryStringBuster())->isCacheBusted(
            'http://example.org/app/theme/default/main.min.js'
        ));
    }
    
    public function testAddTimeToUrl()
    {
        $time = 1557248558;
        $this->assertEquals(
            "http://example.org/main.js?v=$time",
            (new QueryStringBuster())->addTimeToUrl('http://example.org/main.js', $time)
        );
    }

    public function testAddTimeToUrlWithQueryString()
    {
        $time = 1557248558;
        $this->assertEquals(
            "http://example.org/main.js?param1=val1&v=$time",
            (new QueryStringBuster())->addTimeToUrl('http://example.org/main.js?param1=val1', $time)
        );
    }
}