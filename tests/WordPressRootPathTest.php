<?php


use PHPUnit\Framework\TestCase;
use Rdlv\WordPress\CacheBustAssets\WordPressRootPath;

class WordPressRootPathTest extends TestCase
{
    public function testGetRootPathWithWordPressInDocumentRoot()
    {
        $this->assertEquals('/var/www/', (new WordPressRootPath())->get(
            'http://example.org',
            'http://example.org',
            '/var/www/'
        ));
    }
    
    public function testGetRootPathWithSubfolderInstallation()
    {
        $this->assertEquals('/var/www/blog/', (new WordPressRootPath())->get(
            'http://example.org/blog/',
            'http://example.org/blog/',
            '/var/www/blog/'
        ));
    }
    
    public function testGetRootPathWithWordPressInSubfolder()
    {
        $this->assertEquals('/var/www/', (new WordPressRootPath())->get(
            'http://example.org',
            'http://example.org/wp',
            '/var/www/wp/'
        ));
    }
}