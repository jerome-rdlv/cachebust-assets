<?php


use PHPUnit\Framework\TestCase;
use Rdlv\WordPress\CacheBustAssets\WordPressRootPath;

class WordPressRootPathTest extends TestCase
{
	public function testGetRootPathWithWordPressInDocumentRoot(): void
	{
		$this->assertEquals(
			'/var/www/',
			(new WordPressRootPath())->get(
				'http://example.org',
				'http://example.org',
				'/var/www/'
			)
		);
	}

	public function testGetRootPathWithSubfolderInstallation(): void
	{
		$this->assertEquals(
			'/var/www/blog/',
			(new WordPressRootPath())->get(
				'http://example.org/blog/',
				'http://example.org/blog/',
				'/var/www/blog/'
			)
		);
	}

	public function testGetRootPathWithWordPressInSubfolder(): void
	{
		$this->assertEquals(
			'/var/www/',
			(new WordPressRootPath())->get(
				'http://example.org',
				'http://example.org/wp',
				'/var/www/wp/'
			)
		);
	}

	public function testCanonicalizeWithBackslashes(): void
	{
		$this->assertEquals(
			'/var/www/app',
			(new WordPressRootPath())->canonicalize('\var\www\app')
		);
	}

	public function testCanonicalizeWithDoubleSlashes(): void
	{
		$this->assertEquals(
			'/var/www/app',
			(new WordPressRootPath())->canonicalize('/var/www//app')
		);
	}

	public function testCanonicalizeWithSeveralParents(): void
	{
		$this->assertEquals(
			'/var/www/app/themes/defaults',
			(new WordPressRootPath())->canonicalize(
				'/var/htdocs/web/../../www/app/plugins/../themes/defaults/assets/..'
			)
		);
	}
}
