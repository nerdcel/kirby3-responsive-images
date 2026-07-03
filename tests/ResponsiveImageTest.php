<?php declare(strict_types=1);

use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\App;
use PHPUnit\Framework\TestCase;

/*
 * Test ResponsiveImages class
 */

final class ResponsiveImageTest extends TestCase
{
    public function testCanBeCreatedFromPng(): void
    {
        $responsiveImagesInstance = new Nerdcel\ResponsiveImages\ResponsiveImages(
            $this->createKirbyApp('responsive-img-empty.json')
        );
        $fileMock = $this->createFileMock();

        $image = $responsiveImagesInstance->makeResponsiveImage('test', $fileMock, 'test', false, '', 'webp');
        preg_match_all('@src="([^"]+)"@', $image, $match);

        $this->assertTrue(isset($match[1][0]));
        $this->assertIsString($image);
        $this->assertStringContainsString('src', $image);
        $this->assertStringContainsString('.webp', $image);
    }

    public function testCanBeCreatedFromPngWithConfig(): void
    {
        $responsiveImagesInstance = new Nerdcel\ResponsiveImages\ResponsiveImages(
            $this->createKirbyApp('responsive-img.json')
        );
        $fileMock = $this->createFileMock();

        $image = $responsiveImagesInstance->makeResponsiveImage('test', $fileMock, 'test', false, '', 'webp');
        preg_match_all('@src="([^"]+)"@', $image, $match);

        $this->assertTrue(isset($match[1][0]));
        $this->assertIsString($image);
        $this->assertStringContainsString('src', $image);
        $this->assertStringContainsString('srcset', $image);
        $this->assertStringContainsString('.webp', $image);
    }

    private function createKirbyApp(string $configFile): App
    {
        return new App([
            'roots' => [
                'index' => __DIR__.'/kirby',
                'media' => __DIR__.'/kirby/media',
                'content' => __DIR__.'/kirby/content',
            ],
            'options' => [
                'nerdcel.responsive-images' => [
                    'configPath' => __DIR__.'/config',
                    'configFile' => $configFile,
                    'quality' => 85,
                    'defaultWidth' => 1024,
                    'allowedRoles' => ['admin'],
                ],
            ],
        ]);
    }

    private function createFileMock(): File
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

