<?php

/**
 * @noinspection HttpUrlsUsage
 * @noinspection PhpUnhandledExceptionInspection
 */

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Rdlv\WordPress\CacheBustAssets\AbstractBuster;

class AbstractBusterTest extends TestCase
{
	private string $rootUrl;

	public function testMtimeSignature(): void
	{
		touch($this->rootUrl . '/test.js', 1557240509);
		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$this->assertEquals(1557240509, $buster->getSignature($this->rootUrl . '/test.js'));
	}

	public function testMd5Signature(): void
	{
		$content = 'Lorem ipsum dolor sit amet';
		file_put_contents($this->rootUrl . '/test.js', $content);
		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$this->assertEquals(
			md5($content),
			$buster->getSignature($this->rootUrl . '/test.js', AbstractBuster::SIGNATURE_MD5)
		);
	}

	public function testSha1Signature(): void
	{
		$content = 'Lorem ipsum dolor sit amet';
		file_put_contents($this->rootUrl . '/test.js', $content);
		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$this->assertEquals(
			sha1($content),
			$buster->getSignature($this->rootUrl . '/test.js', AbstractBuster::SIGNATURE_SHA1)
		);
	}

	public function testIsLocal(): void
	{
		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$buster->setHome('http://example.org/', '/var/www');
		$this->assertTrue($buster->isLocal('http://example.org/app/theme/default/main.js'));
	}

	public function testIsNotLocal(): void
	{
		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$buster->setHome('http://example.org/', '/var/www');
		$this->assertFalse($buster->isLocal('http://example.com/app/theme/default/main.js'));
	}

	public function testPathResolution(): void
	{
		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$buster->setHome('http://example.org/', '/var/www/');
		$this->assertEquals(
			'/var/www/app/theme/default/main.js',
			$buster->getPath('http://example.org/app/theme/default/main.js')
		);
	}

	public function testPathResolutionWithQueryString(): void
	{
		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$buster->setHome('http://example.org/', '/var/www/');
		$this->assertEquals(
			'/var/www/app/theme/default/main.js',
			$buster->getPath('http://example.org/app/theme/default/main.js?ver=5.1.1')
		);
	}

	public function testHomeUrlNormalization(): void
	{
		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$buster->setHome('http://example.org', '/var/www/');
		$this->assertEquals(
			'/var/www/main.js',
			$buster->getPath('http://example.org/main.js')
		);
	}

	public function testHomePathNormalization(): void
	{
		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$buster->setHome('http://example.org/', '/var/www');
		$this->assertEquals(
			'/var/www/main.js',
			$buster->getPath('http://example.org/main.js')
		);
	}

	public function testNotReadyError(): void
	{
		set_error_handler(static function (int $errno): never {
			throw new Exception('warning', $errno);
		}, E_USER_WARNING);
		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$this->expectExceptionMessage('warning');
		$buster->cacheBustUrl('http://example.org/main.js');
		restore_error_handler();
	}

	public function testCacheBustUrl(): void
	{
		touch($this->rootUrl . '/main.js', 1557245182);
		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$buster->method('addSignatureToUrl')->willReturn('cache-busted-url');
		$buster->setHome('http://example.org/', $this->rootUrl);
		$this->assertEquals(
			'cache-busted-url',
			$buster->cacheBustUrl('http://example.org/main.js')
		);
	}

	public function testUnchangeUrlForNotExistentAsset(): void
	{
		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$buster->method('addSignatureToUrl')->willReturn('cache-busted-url');
		$buster->setHome('http://example.org/', $this->rootUrl);
		$url = 'http://example.org/main.js';
		$this->assertEquals($url, $buster->cacheBustUrl($url));
	}

	public function testCacheBustImageSrc(): void
	{
		$buster = $this->getMockForAbstractClass(AbstractBuster::class, [], '', true, true, true, [
			'cacheBustUrl',
		]);
		$buster->method('cacheBustUrl')->willReturn('cache-busted-url');
		$src = ['http://example.org/image.jpg', 800, 600];
		$this->assertEquals(
			'cache-busted-url',
			$buster->cacheBustImageSrc($src)[0]
		);
	}

	public function testCacheBustThumbnail(): void
	{
		// create image file
		mkdir($this->rootUrl . '/wp-content/uploads/image.jpg', 0777, true);
		touch($this->rootUrl . '/main.js', 1557247393);

		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$buster->method('addSignatureToUrl')->willReturn('cache-busted-url');
		$buster->setHome('http://example.org/', $this->rootUrl);

		$this->assertEquals(
			'<img alt="" src="cache-busted-url" width="300" height="200">',
			$buster->cacheBustThumbnail(
				'<img alt="" src="http://example.org/wp-content/uploads/image.jpg" width="300" height="200">'
			)
		);
	}

	public function testCacheBustSrcset(): void
	{
		// create image file
		mkdir($this->rootUrl . '/wp-content/uploads/image.jpg', 0777, true);
		touch($this->rootUrl . '/main.js', 1557245182);

		$buster = $this->getMockForAbstractClass(AbstractBuster::class);
		$buster->method('addSignatureToUrl')->willReturnOnConsecutiveCalls(
			'cache-busted-source-1',
			'cache-busted-source-2'
		);
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

	public function testCacheBustAcfImage(): void
	{
		$buster = $this->getMockForAbstractClass(AbstractBuster::class, [], '', true, true, true, [
			'getSignature',
		]);
		$buster->method('getSignature')->willReturn('1557247935');

		// we test only cacheBustAcfImage, not the underlying methods
		$buster->method('addSignatureToUrl')->willReturnOnConsecutiveCalls(
			'cache-busted-url-main',
			'cache-busted-url-size-800',
			'cache-busted-url-size-1600'
		);
		$buster->setHome('http://example.org/', $this->rootUrl);

		$image = [
			'url' => 'http://example.org/wp-content/uploads/image.jpg',
			'sizes' => [
				800 => 'http://example.org/wp-content/uploads/image-800x600.jpg',
				1600 => 'http://example.org/wp-content/uploads/image-1600x1200.jpg',
			],
		];

		$this->assertEquals(
			[
				'url' => 'cache-busted-url-main',
				'sizes' => [
					800 => 'cache-busted-url-size-800',
					1600 => 'cache-busted-url-size-1600',
				],
			],
			$buster->cacheBustAcfImage($image)
		);
	}

	public function testCacheBustFavicons(): void
	{
		$buster = $this->getMockForAbstractClass(AbstractBuster::class, [], '', true, true, true, [
			'cacheBustUrl',
		]);
		$buster->method('cacheBustUrl')->willReturn('cache-busted-url');
		$tag = '<link rel="icon" href="%s" sizes="32x32" />';
		$this->assertEquals(
			[sprintf($tag, 'cache-busted-url')],
			$buster->cacheBustFavicons([sprintf($tag, 'https://example.org/cropped-favicon-32x32.png')])
		);
	}

	public function testFilter(): void
	{
		$buster = $this->getMockForAbstractClass(AbstractBuster::class, [], '', true, true, true, [
			'isLocal',
			'isCacheBusted',
			'getSignature',
			'addSignatureToUrl',
		]);
		$buster->method('isLocal')->willReturn(true);
		$buster->method('isCacheBusted')->willReturn(false);
		$buster->method('getSignature')->willReturn('signature');
		$buster->method('addSignatureToUrl')->willReturn('cache-busted-url');

		$url = 'http://example.org/image.jpg';

		$buster->setHome('http://example.org/', $this->rootUrl);

		$this->assertEquals('cache-busted-url', $buster->cacheBustUrl($url));
		$buster->setFilter(function () {
			return false;
		});
		$this->assertEquals($url, $buster->cacheBustUrl($url));
	}

	protected function setUp(): void
	{
		parent::setUp();
		$this->rootUrl = vfsStream::setup()->url();
	}
}
