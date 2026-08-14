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

    public function testAiHintIsNotRenderedWhenDisabled(): void
    {
        $responsiveImagesInstance = new Nerdcel\ResponsiveImages\ResponsiveImages(
            $this->createKirbyApp('responsive-img-empty.json')
        );
        $fileMock = $this->createFileMock();

        $image = $responsiveImagesInstance->makeResponsiveImage('test', $fileMock, 'test', false, '', 'webp');

        $this->assertStringNotContainsString('nerdcel-ai-hint', $image);
    }

    /**
     * The hint overlay markup (span/wrapper) must never be rendered in the
     * frontend output - it is only shown as a live preview inside the
     * panel. On the frontend, the hint is solely conveyed by burning it
     * into the generated image itself (see testAiHintIsBurnedIntoTheGeneratedImage).
     */
    public function testAiHintOverlayIsNotRenderedInFrontendMarkup(): void
    {
        $responsiveImagesInstance = new Nerdcel\ResponsiveImages\ResponsiveImages(
            $this->createKirbyApp('responsive-img-empty.json')
        );
        $fileMock = $this->createFileMock([
            'aihint' => \Kirby\Data\Data::encode([
                'enabled' => true,
                'text' => 'Custom AI text',
                'position' => 'top-left',
                'color' => '#000000',
                'size' => 7,
                'margin' => 1.2,
                'padding' => 0.8,
            ], 'yaml'),
        ]);

        $image = $responsiveImagesInstance->makeResponsiveImage('test', $fileMock, 'test', false, '', 'webp');

        $this->assertStringNotContainsString('nerdcel-ai-hint', $image);
        $this->assertStringNotContainsString('Custom AI text', $image);
    }

    /**
     * Blueprints/content created before "size" became a numeric percentage
     * may still use the legacy small/medium/large string enum. This must
     * keep working instead of throwing "Invalid value for size".
     */
    public function testAiHintAcceptsLegacyStringSize(): void
    {
        $responsiveImagesInstance = new Nerdcel\ResponsiveImages\ResponsiveImages(
            $this->createKirbyApp('responsive-img-empty.json')
        );
        $fileMock = $this->createFileMock([
            'aihint' => \Kirby\Data\Data::encode([
                'enabled' => true,
                'size' => 'large',
            ], 'yaml'),
        ]);

        $image = $responsiveImagesInstance->makeResponsiveImage('test', $fileMock, 'test', false, '', 'webp');

        // The legacy string enum must not throw; the image is still generated
        $this->assertStringContainsString('src', $image);

        $this->assertSame(5.0, Nerdcel\ResponsiveImages\ResponsiveImages::normalizeSize('medium'));
        $this->assertSame(2.5, Nerdcel\ResponsiveImages\ResponsiveImages::normalizeSize('small'));
        $this->assertSame(8.0, Nerdcel\ResponsiveImages\ResponsiveImages::normalizeSize('large'));
    }

    public function testAiHintFallsBackToConfiguredDefaults(): void
    {
        $responsiveImagesInstance = new Nerdcel\ResponsiveImages\ResponsiveImages(
            $this->createKirbyApp('responsive-img-empty.json')
        );
        $fileMock = $this->createFileMock([
            'aihint' => \Kirby\Data\Data::encode(['enabled' => true], 'yaml'),
        ]);

        $image = $responsiveImagesInstance->makeResponsiveImage('test', $fileMock, 'test', false, '', 'webp');

        $this->assertStringNotContainsString('nerdcel-ai-hint', $image);
    }

    public function testAiHintIsBurnedIntoTheGeneratedImage(): void
    {
        $responsiveImagesInstance = new Nerdcel\ResponsiveImages\ResponsiveImages(
            $this->createKirbyApp('responsive-img-empty.json')
        );
        $fileMock = $this->createFileMock([
            'aihint' => \Kirby\Data\Data::encode([
                'enabled' => true,
                'text' => 'Stamped hint',
                'position' => 'bottom-right',
                'color' => '#ff0000',
                'size' => 5,
            ], 'yaml'),
        ]);

        $image = $responsiveImagesInstance->makeResponsiveImage('test', $fileMock, 'test', false, '', 'jpg');

        preg_match('/src="([^"]+)"/', $image, $match);
        $this->assertNotEmpty($match[1] ?? null);
        $this->assertStringContainsString('-ai-', $match[1]);

        $stampedRoot = __DIR__.'/kirby'.parse_url($match[1], PHP_URL_PATH);
        $this->assertFileExists($stampedRoot);

        // IPTC metadata should mark the stamped JPEG as AI generated
        getimagesize($stampedRoot, $info);
        $iptc = iptcparse($info['APP13'] ?? '');
        $this->assertNotEmpty($iptc);
        $this->assertContains('AI-generated', $iptc['2#025'] ?? []);

        // XMP metadata should also carry the hint text and mark the
        // digital source type as AI generated, for tools/formats that
        // don't read the legacy IPTC IIM block
        $stampedContent = file_get_contents($stampedRoot);
        $this->assertStringContainsString('Stamped hint', $stampedContent);
        $this->assertStringContainsString(
            'Iptc4xmpExt:DigitalSourceType>http://cv.iptc.org/newscodes/digitalsourcetype/trainedAlgorithmicMedia',
            $stampedContent
        );
    }

    /**
     * XMP embedding must also work for formats without IPTC IIM support
     * (unlike the legacy block, which is JPEG-only).
     */
    public function testAiHintXmpMetadataIsEmbeddedInPng(): void
    {
        $responsiveImagesInstance = new Nerdcel\ResponsiveImages\ResponsiveImages(
            $this->createKirbyApp('responsive-img-empty.json')
        );
        $fileMock = $this->createFileMock([
            'aihint' => \Kirby\Data\Data::encode([
                'enabled' => true,
                'text' => 'PNG hint text',
                'color' => '#ffffff',
            ], 'yaml'),
        ]);

        $image = $responsiveImagesInstance->makeResponsiveImage('test', $fileMock, 'test', false, '', 'png');

        preg_match('/src="([^"]+)"/', $image, $match);
        $stampedRoot = __DIR__.'/kirby'.parse_url($match[1], PHP_URL_PATH);
        $this->assertFileExists($stampedRoot);

        $stampedContent = file_get_contents($stampedRoot);
        $this->assertStringContainsString('iTXt', $stampedContent);
        $this->assertStringContainsString('PNG hint text', $stampedContent);

        // The stamped file must still be a valid, decodable PNG
        $this->assertNotFalse(@imagecreatefrompng($stampedRoot));
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
            'fieldMethods' => require __DIR__.'/../src/fieldMethods.php',
        ]);
    }

    private function createFileMock(array $content = []): File
    {
        $mockedPage = Page::factory([
            'title' => 'testpage',
            'slug' => 'testpage',
        ]);
        $mockedFile = File::factory([
            'filename' => 'test.png', 'parent' => $mockedPage,
            'content' => $content + ['alt' => ''],
        ]);

        return $mockedFile;
    }
}

