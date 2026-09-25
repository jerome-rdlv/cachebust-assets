<?php


use PHPUnit\Framework\TestCase;
use Rdlv\WordPress\CacheBustAssets\QueryStringBuster;
use Uri\InvalidUriException;

class QueryStringBusterTest extends TestCase
{
	/**
	 * @throws InvalidUriException
	 */
	public function testIsCacheBusted(): void
	{
		$this->assertTrue(
			new QueryStringBuster()->isCacheBusted(
				'http://example.org/app/theme/default/main.min.js?param1=val1&v=1557248782&param2=val2'
			)
		);
	}

	/**
	 * @throws InvalidUriException
	 */
	public function testIsNotCacheBusted(): void
	{
		$this->assertFalse(
			new QueryStringBuster()->isCacheBusted(
				'http://example.org/app/theme/default/main.min.js'
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
			"http://example.org/main.js?v=$time",
			new QueryStringBuster()->addSignatureToUrl('http://example.org/main.js', $time)
		);
	}

	/**
	 * @throws InvalidUriException
	 */
	public function testAddTimeToUrlWithQueryString(): void
	{
		$time = '1557248558';
		$this->assertEquals(
			"http://example.org/main.js?param1=val1&v=$time",
			new QueryStringBuster()->addSignatureToUrl('http://example.org/main.js?param1=val1', $time)
		);
	}
}
