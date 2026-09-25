<?php

/**
 * @noinspection HttpUrlsUsage
 */

use PHPUnit\Framework\TestCase;
use Rdlv\WordPress\CacheBustAssets\PathBuster;
use Uri\InvalidUriException;

class PathBusterTest extends TestCase
{
	/**
	 * @throws InvalidUriException
	 */
	public function testIsCacheBusted(): void
	{
		$this->assertTrue(
			new PathBuster()->isCacheBusted(
				'http://example.org/app/theme/default/main.min.v1557248448.js'
			)
		);
	}

	/**
	 * @throws InvalidUriException
	 */
	public function testIsNotCacheBusted(): void
	{
		$this->assertFalse(
			new PathBuster()->isCacheBusted(
				'http://example.org/app/theme/default/main.min.js'
			)
		);
	}

	/**
	 * @throws InvalidUriException
	 */
	public function testIsNotCacheBustedWhenTimeIsNotAtTheEnd(): void
	{
		$this->assertFalse(
			new PathBuster()->isCacheBusted(
				'http://example.org/app/theme/default/main.v1557248699.min.js'
			)
		);
	}

	/**
	 * @throws InvalidUriException
	 */
	public function testAddTimeToUrl(): void
	{
		$time = '1557248558';
		$this->assertEquals(
			"http://example.org/main.v$time.js",
			new PathBuster()->addSignatureToUrl('http://example.org/main.js', $time)
		);
	}

	/**
	 * @throws InvalidUriException
	 */
	public function testAddTimeToUrlWithQueryString(): void
	{
		$time = '1557248558';
		$this->assertEquals(
			"http://example.org/main.v$time.js?param1=val1",
			new PathBuster()->addSignatureToUrl('http://example.org/main.js?param1=val1', $time)
		);
	}
}
