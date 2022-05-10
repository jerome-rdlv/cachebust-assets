<?php

/** @noinspection PhpUnhandledExceptionInspection */

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Rdlv\WordPress\CacheBustAssets\AbstractBuster;

class AbstractBusterTest extends TestCase
{
    private $rootUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootUrl = vfsStream::setup()->url();
    }

    public function testMtimeSignature()
    {
        touch($this->rootUrl . '/test.js', 1557240509);
        /** @var AbstractBuster $buster */
        $buster = $this->getMockForAbstractClass(AbstractBuster::class);
        $this->assertEquals(1557240509, $buster->getSignature($this->rootUrl . '/test.js'));
    }

    public function testMd5Signature()
    {
        $content = 'Lorem ipsum dolor sit amet';
        file_put_contents($this->rootUrl . '/test.js', $content);
        /** @var AbstractBuster $buster */
        $buster = $this->getMockForAbstractClass(AbstractBuster::class);
        $this->assertEquals(md5($content),
                            $buster->getSignature($this->rootUrl . '/test.js', AbstractBuster::SIGNATURE_MD5));
    }

    public function testSha1Signature()
    {
        $content = 'Lorem ipsum dolor sit amet';
        file_put_contents($this->rootUrl . '/test.js', $content);
        /** @var AbstractBuster $buster */
        $buster = $this->getMockForAbstractClass(AbstractBuster::class);
        $this->assertEquals(sha1($content),
                            $buster->getSignature($this->rootUrl . '/test.js', AbstractBuster::SIGNATURE_SHA1));
    }

    public function testBuildUrlWithHttps()
    {
        /** @var AbstractBuster $buster */
        $buster = $this->getMockForAbstractClass(AbstractBuster::class);
        $url = 'https://example.org/main.js';
        $this->assertEquals($url, $buster->buildUrl(parse_url($url)));
    }

    public function testBuildUrlWithPort()
    {
        /** @var AbstractBuster $buster */
        $buster = $this->getMockForAbstractClass(AbstractBuster::class);
        $url = 'http://example.org:8080/main.js';
        $this->assertEquals($url, $buster->buildUrl(parse_url($url)));
    }

    public function testBuildUrlWithQueryString()
    {
        /** @var AbstractBuster $buster */
        $buster = $this->getMockForAbstractClass(AbstractBuster::class);
        $url = 'http://example.org/main.js?param1=val1&param2=val2';
        $this->assertEquals($url, $buster->buildUrl(parse_url($url)));
    }

    public function testBuildUrlWithHash()
    {
        /** @var AbstractBuster $buster */
        $buster = $this->getMockForAbstractClass(AbstractBuster::class);
        $url = 'http://example.org/main.js#chapter-3';
        $this->assertEquals($url, $buster->buildUrl(parse_url($url)));
    }

    public function testIsLocal()
    {
        /** @var AbstractBuster $buster */
        $buster = $this->getMockForAbstractClass(AbstractBuster::class);
        $buster->setHome('http://example.org/', '/var/www');
        $this->assertTrue($buster->isLocal('http://example.org/app/theme/default/main.js'));
    }

    public function testIsNotLocal()
    {
        /** @var AbstractBuster $buster */
        $buster = $this->getMockForAbstractClass(AbstractBuster::class);
        $buster->setHome('http://example.org/', '/var/www');
        $this->assertFalse($buster->isLocal('http://example.com/app/theme/default/main.js'));
    }

    public function testPathResolution()
    {
        /** @var AbstractBuster $buster */
        $buster = $this->getMockForAbstractClass(AbstractBuster::class);
        $buster->setHome('http://example.org/', '/var/www/');
        $this->assertEquals(
            '/var/www/app/theme/default/main.js',
            $buster->getPath('http://example.org/app/theme/default/main.js')
        );
    }

    public function testPathResolutionWithQueryString()
    {
        /** @var AbstractBuster $buster */
        $buster = $this->getMockForAbstractClass(AbstractBuster::class);
        $buster->setHome('http://example.org/', '/var/www/');
        $this->assertEquals(
            '/var/www/app/theme/default/main.js',
            $buster->getPath('http://example.org/app/theme/default/main.js?ver=5.1.1')
        );
    }

    public function testHomeUrlNormalization()
    {
        /** @var AbstractBuster $buster */
        $buster = $this->getMockForAbstractClass(AbstractBuster::class);
        $buster->setHome('http://example.org', '/var/www/');
        $this->assertEquals(
            '/var/www/main.js',
            $buster->getPath('http://example.org/main.js')
        );
    }

    public function testHomePathNormalization()
    {
        /** @var AbstractBuster $buster */
        $buster = $this->getMockForAbstractClass(AbstractBuster::class);
        $buster->setHome('http://example.org/', '/var/www');
        $this->assertEquals(
            '/var/www/main.js',
            $buster->getPath('http://example.org/main.js')
        );
    }

    public function testCacheBustUrl()
    {
        touch($this->rootUrl . '/main.js', 1557245182);
        $mock = $this->getMockForAbstractClass(AbstractBuster::class);
        $mock->method('addSignatureToUrl')->willReturn('cache-busted-url');

        /** @var AbstractBuster $buster */
        $buster = $mock;
        $buster->setHome('http://example.org/', $this->rootUrl);
        $this->assertEquals(
            'cache-busted-url',
            $buster->cacheBustUrl('http://example.org/main.js')
        );
    }

    public function testUnchangeUrlForNotExistentAsset()
    {
        $mock = $this->getMockForAbstractClass(AbstractBuster::class);
        $mock->method('addSignatureToUrl')->willReturn('cache-busted-url');

        /** @var AbstractBuster $buster */
        $buster = $mock;
        $buster->setHome('http://example.org/', $this->rootUrl);
        $url = 'http://example.org/main.js';
        $this->assertEquals($url, $buster->cacheBustUrl($url));
    }

    public function testCacheBustImageSrc()
    {
        $mock = $this->getMockForAbstractClass(AbstractBuster::class, [], '', true, true, true, [
            'cacheBustUrl',
        ]);
        $mock->method('cacheBustUrl')->willReturn('cache-busted-url');

        /** @var AbstractBuster $buster */
        $buster = $mock;
        $src = ['http://example.org/image.jpg', 800, 600];
        $this->assertEquals(
            'cache-busted-url',
            $buster->cacheBustImageSrc($src)[0]
        );
    }

    public function testCacheBustThumbnail()
    {
        // create image file
        mkdir($this->rootUrl . '/wp-content/uploads/image.jpg', 0777, true);
        touch($this->rootUrl . '/main.js', 1557247393);

        $mock = $this->getMockForAbstractClass(AbstractBuster::class);
        $mock->method('addSignatureToUrl')->willReturn('cache-busted-url');

        /** @var AbstractBuster $buster */
        $buster = $mock;
        $buster->setHome('http://example.org/', $this->rootUrl);

        $this->assertEquals(
            '<img alt="" src="cache-busted-url" width="300" height="200">',
            $buster->cacheBustThumbnail('<img alt="" src="http://example.org/wp-content/uploads/image.jpg" width="300" height="200">')
        );
    }

    public function testCacheBustSrcset()
    {
        // create image file
        mkdir($this->rootUrl . '/wp-content/uploads/image.jpg', 0777, true);
        touch($this->rootUrl . '/main.js', 1557245182);

        $mock = $this->getMockForAbstractClass(AbstractBuster::class);
        $mock->method('addSignatureToUrl')->willReturnOnConsecutiveCalls(
            'cache-busted-source-1',
            'cache-busted-source-2'
        );

        /** @var AbstractBuster $buster */
        $buster = $mock;
        $buster->setHome('http://example.org/', $this->rootUrl);

        $this->assertEquals(
            [
                ['url' => 'cache-busted-source-1'],
                ['url' => 'cache-busted-source-2'],
            ],
            $buster->cacheBustSrcset(
                [
                    ['url' => 'http://example.org/wp-content/uploads/image-800x600.jpg'],
                    ['url' => 'http://example.org/wp-content/uploads/image-1600x1200.jpg'],
                ],
                null,
                'http://example.org/wp-content/uploads/image.jpg'
            )
        );
    }

    public function testCacheBustAcfImage()
    {
        $mock = $this->getMockForAbstractClass(AbstractBuster::class, [], '', true, true, true, [
            'getSignature',
        ]);
        $mock->method('getSignature')->willReturn('1557247935');

        // we test only cacheBustAcfImage, not the underlying methods
        $mock->method('addSignatureToUrl')->willReturnOnConsecutiveCalls(
            'cache-busted-url-main',
            'cache-busted-url-size-800',
            'cache-busted-url-size-1600'
        );

        /** @var AbstractBuster $buster */
        $buster = $mock;
        $buster->setHome('http://example.org/', $this->rootUrl);

        $image = [
            'url'   => 'http://example.org/wp-content/uploads/image.jpg',
            'sizes' => [
                800  => 'http://example.org/wp-content/uploads/image-800x600.jpg',
                1600 => 'http://example.org/wp-content/uploads/image-1600x1200.jpg',
            ],
        ];

        $this->assertEquals(
            [
                'url'   => 'cache-busted-url-main',
                'sizes' => [
                    800  => 'cache-busted-url-size-800',
                    1600 => 'cache-busted-url-size-1600',
                ],
            ],
            $buster->cacheBustAcfImage($image)
        );
    }

    public function testCacheBustFavicons()
    {
        $mock = $this->getMockForAbstractClass(AbstractBuster::class, [], '', true, true, true, [
            'cacheBustUrl',
        ]);
        $mock->method('cacheBustUrl')->willReturn('cache-busted-url');
        /** @var AbstractBuster $buster */
        $buster = $mock;
        $tag = '<link rel="icon" href="%s" sizes="32x32" />';
        $this->assertEquals(
            [sprintf($tag, 'cache-busted-url')],
            $buster->cacheBustFavicons([sprintf($tag, 'https://example.org/cropped-favicon-32x32.png')])
        );
    }

    public function testFilter()
    {
        $mock = $this->getMockForAbstractClass(AbstractBuster::class, [], '', true, true, true, [
            'isLocal',
            'isCacheBusted',
            'getSignature',
            'addSignatureToUrl',
        ]);
        $mock->method('isLocal')->willReturn(true);
        $mock->method('isCacheBusted')->willReturn(false);
        $mock->method('getSignature')->willReturn('signature');
        $mock->method('addSignatureToUrl')->willReturn('cache-busted-url');

        $url = 'http://example.org/image.jpg';

        /** @var AbstractBuster $buster */
        $buster = $mock;
        $buster->setHome('http://example.org/', $this->rootUrl);

        $this->assertEquals('cache-busted-url', $buster->cacheBustUrl($url));
        $buster->setFilter(function (string $url) {
            return false;
        });
        $this->assertEquals($url, $buster->cacheBustUrl($url));
    }
}