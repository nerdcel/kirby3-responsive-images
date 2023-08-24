<?php declare(strict_types=1);

use Kirby\Cms\File;
use Kirby\Cms\Page;
use PHPUnit\Framework\TestCase;

/*
 * Test ResponsiveImages class
 */

final class ResponsiveImageTest extends TestCase
{
    private File $fileMock;

    public function __construct($arg)
    {
        parent::__construct($arg);

        // Load Kirby
        (new Kirby\Cms\App([
            'roots' => [
                'index' => __DIR__.'/kirby',
                'media' => __DIR__.'/kirby/media',
                'content' => __DIR__.'/kirby/content',
            ],
        ]));

        $this->fileMock = $this->createFileMock();
    }

    public function testCanBeCreatedFromPng(): void
    {
        $responsiveImagesInstance = new Nerdcel\ResponsiveImages\ResponsiveImages([
            'configPath' => __DIR__.'/config/',
            'configFile' => 'responsive-img-empty.json',
            'quality' => 85,
            'defaultWidth' => 1024,
            'allowedRoles' => [
                'admin',
            ],
        ]);

        $image = $responsiveImagesInstance->makeResponsiveImage('test', $this->fileMock, 'test', false, '', 'webp');
        preg_match_all('@src="([^"]+)"@', $image, $match);

        $this->assertTrue(isset($match[1][0]));
        $this->assertIsString($image);
        $this->assertStringContainsString('src', $image);
        $this->assertStringContainsString('.webp', $image);
    }

    public function testCanBeCreatedFromPngWithConfig(): void
    {
        $responsiveImagesInstance = new Nerdcel\ResponsiveImages\ResponsiveImages([
            'configPath' => __DIR__.'/config/',
            'configFile' => 'responsive-img.json',
            'quality' => 85,
            'defaultWidth' => 1024,
            'allowedRoles' => [
                'admin',
            ],
        ]);

        $image = $responsiveImagesInstance->makeResponsiveImage('test', $this->fileMock, 'test', false, '', 'webp');
        preg_match_all('@src="([^"]+)"@', $image, $match);

        $this->assertTrue(isset($match[1][0]));
        $this->assertIsString($image);
        $this->assertStringContainsString('src', $image);
        $this->assertStringContainsString('srcset', $image);
        $this->assertStringContainsString('.webp', $image);
    }

    private function createFileMock()
    {
        $mockedPage = Page::factory([
            'title' => 'testpage',
            'slug' => 'testpage',
        ]);
        $mockedFile = File::factory([
            'filename' => 'test.png', 'parent' => $mockedPage,
            'content' => [file_get_contents(__DIR__.'/kirby/content/testpage/test.png')],
        ]);

        return $mockedFile;
    }
}

