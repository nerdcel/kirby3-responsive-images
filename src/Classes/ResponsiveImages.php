<?php

namespace Nerdcel\ResponsiveImages;

use Exception;
use JsonException;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\User;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Config;
use Psr\Log\LoggerInterface;

class ResponsiveImages
{
    /**
     * Default configuration for responsive images
     */
    private const DEFAULT_CONFIG = [
        'breakpoints' => [],
        'settings' => [],
    ];

    /**
     * Configuration settings
     */
    private array $settings = [];

    /**
     * Kirby App instance
     */
    private App $kirby;

    /**
     * Logger instance
     */
    private ?LoggerInterface $logger;

    /**
     * Constructor with dependency injection
     *
     * @param  App  $kirby  Kirby application instance
     * @param  LoggerInterface|null  $logger  Optional logger
     */
    public function __construct(App $kirby, ?LoggerInterface $logger = null)
    {
        $this->kirby = $kirby;
        $this->logger = $logger;
    }

    /**
     * Legacy size enum (used before the "size" setting became a numeric
     * percentage of the image height), kept for backwards compatibility
     * with existing blueprints/content still using `small`/`medium`/`large`.
     */
    private const LEGACY_SIZES = [
        'small' => 2.5,
        'medium' => 5,
        'large' => 8,
    ];

    /**
     * Normalizes a size value (numeric percentage or legacy string enum)
     * into a valid float percentage, falling back to $default when the
     * value is missing or not resolvable.
     */
    public static function normalizeSize(mixed $size, float $default = 5): float
    {
        if (is_string($size) && isset(self::LEGACY_SIZES[$size])) {
            $size = self::LEGACY_SIZES[$size];
        }

        if (! is_numeric($size)) {
            $size = $default;
        }

        return max(1, min(15, (float) $size));
    }

    /**
     * Normalizes a margin value (the distance, in em, between the hint's
     * background box and the edge of the image), falling back to
     * $default when the value is missing or not resolvable.
     */
    public static function normalizeMargin(mixed $margin, float $default = 0.6): float
    {
        if (! is_numeric($margin)) {
            $margin = $default;
        }

        return max(0, min(5, (float) $margin));
    }

    /**
     * Normalizes a padding value (the space, in em, between the hint text
     * and the edge of its own backdrop box), falling back to $default
     * when the value is missing or not resolvable.
     */
    public static function normalizePadding(mixed $padding, float $default = 0.5): float
    {
        if (! is_numeric($padding)) {
            $padding = $default;
        }

        return max(0, min(2, (float) $padding));
    }

    /**
     * Picks a black or white backdrop colour depending on the perceived
     * brightness of the given hex font colour, so the semi-transparent
     * background always provides good contrast against the text (a light
     * font gets a dark backdrop, a dark font gets a light backdrop).
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public static function contrastBackdropRgb(string $color): array
    {
        $hex = ltrim($color, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return [0, 0, 0];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Perceived brightness (ITU-R BT.601 luma)
        $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;

        return $brightness > 140 ? [0, 0, 0] : [255, 255, 255];
    }

    /**
     * Retrieve configuration options with sensible defaults
     */
    private function getOptions(): array
    {
        return Config::get('nerdcel.responsive-images', [
            'configPath' => $this->kirby->root('content'),
            'configFile' => 'responsive-img.json',
            'quality' => 75,
            'defaultWidth' => 1024,
            'allowedRoles' => ['admin'],
            'supportedFormats' => ['webp', 'avif', 'jpg', 'png'],
            'aiHint' => [
                'text' => 'AI generated image',
                'position' => 'bottom-right',
                'color' => '#ffffff',
                'size' => 5,
                'opacity' => 100,
                'margin' => 0.6,
                'padding' => 0.5,
                'font' => null,
            ],
        ]);
    }

    /**
     * Resolve the AI hint settings for a file, merging the per-file
     * overrides (set via the "aihint" blueprint field) with the plugin's
     * configured defaults. Returns null when the hint is not enabled.
     *
     * Public so it can also be reused by other panel-facing fields (e.g.
     * the "focalpoints" field, to render an accurate live preview).
     */
    public function getAiHintData(File $file): ?array
    {
        try {
            $aiHint = $file->aihint()->toAiHint();
        } catch (\Throwable $e) {
            return null;
        }

        if (! is_array($aiHint) || empty($aiHint['enabled'])) {
            return null;
        }

        $defaults = $this->getOptions()['aiHint'] ?? [];

        return [
            'text' => $aiHint['text'] ?? $defaults['text'] ?? 'AI generated image',
            'position' => $aiHint['position'] ?? $defaults['position'] ?? 'bottom-right',
            'color' => $aiHint['color'] ?? $defaults['color'] ?? '#ffffff',
            'size' => self::normalizeSize($aiHint['size'] ?? $defaults['size'] ?? 5),
            'opacity' => $aiHint['opacity'] ?? $defaults['opacity'] ?? 100,
            'margin' => self::normalizeMargin($aiHint['margin'] ?? $defaults['margin'] ?? 0.6),
            'padding' => self::normalizePadding($aiHint['padding'] ?? $defaults['padding'] ?? 0.5),
        ];
    }

    /**
     * Returns the URL of a "stamped" copy of the given thumbnail with the
     * AI hint text burned directly into the pixel data (plus basic IPTC
     * metadata for JPEGs). This guarantees the hint survives even when the
     * image is downloaded or reused outside of the page, satisfying AI
     * content labelling obligations. Falls back to the original URL when
     * stamping is not possible (e.g. unreadable file, unsupported format).
     *
     * The stamped copy is cached next to the original thumbnail and keyed
     * by a hash of the aiHint settings, so it's regenerated whenever the
     * hint text/position/color/size changes or the source thumb changes.
     */
    private function stampedUrl(mixed $image, ?array $aiHint): string
    {
        if (! $image) {
            return '';
        }

        // Kirby generates thumbnails lazily (usually on first HTTP request
        // to the media route). Force creation now so there is a file on
        // disk we can actually stamp.
        if (method_exists($image, 'exists') && method_exists($image, 'save') && ! $image->exists()) {
            $image->save();
        }

        $root = $image->root();

        if (! $aiHint || ! $root || ! file_exists($root)) {
            return (string) $image->url();
        }

        $suffix = '-ai-'.substr(md5(ImageStamper::VERSION.json_encode($aiHint)), 0, 8);
        $destRoot = preg_replace('/(\.[^.\/]+)$/', $suffix.'$1', $root);

        if (! file_exists($destRoot) || filemtime($root) > filemtime($destRoot)) {
            if (! ImageStamper::stamp($root, $destRoot, $aiHint)) {
                return (string) $image->url();
            }
        }

        return preg_replace('/(\.[^.\/]+)$/', $suffix.'$1', (string) $image->url());
    }

    /**
     * Generate full path to configuration file
     */
    private function getConfigFilePath(): string
    {
        $options = $this->getOptions();

        return $options['configPath'].'/'.$options['configFile'];
    }

    public function writeConfig(string $config)
    {
        F::write($this->getConfigFilePath(), $config);
    }

    /**
     * Load and validate configuration
     *
     * @throws JsonException
     */
    public function loadConfig(): array
    {
        try {
            $configPath = $this->getConfigFilePath();

            // Ensure config file exists
            if (! F::exists($configPath)) {
                $this->writeConfig(json_encode(self::DEFAULT_CONFIG, JSON_THROW_ON_ERROR));
            }

            // Read and parse configuration
            $configContent = F::read($configPath);
            $config = json_decode($configContent, true, 512, JSON_THROW_ON_ERROR);

            // Validate config structure
            if (! $this->validateConfigStructure($config)) {
                $this->logError('Invalid configuration structure');

                return self::DEFAULT_CONFIG;
            }

            return $config;
        } catch (JsonException $e) {
            $this->logError('Configuration parsing error: '.$e->getMessage());

            return self::DEFAULT_CONFIG;
        }
    }

    /**
     * Validate configuration structure
     */
    private function validateConfigStructure(array $config): bool
    {
        return isset($config['breakpoints']) &&
               isset($config['settings']) &&
               is_array($config['breakpoints']) &&
               is_array($config['settings']);
    }

    /**
     * Log error messages
     */
    private function logError(string $message): void
    {
        $this->logger?->error($message);
    }

    /**
     * Check user permissions
     */
    public function hasPermission(string $permission): bool
    {
        $user = $this->kirby->user();

        if (! $user instanceof User) {
            return false;
        }

        try {
            $permissions = $user->role()->permissions();

            return $permissions->for('nerdcel.responsive-images', $permission);
        } catch (Exception $e) {
            $this->logError("Permission check failed: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Generate a predictable cache key
     * @throws JsonException
     */
    private function generateCacheKey(
        File $file,
        array $setting,
        ?string $classes,
        string $slug,
        bool $lazy,
        ?string $alt,
        ?string $responseType = null,
        int|float $factor = 1,
        ?array $aiHint = null
    ): string {
        $cacheComponents = [
            $file->mediaHash(),
            json_encode($setting['breakpointoptions'] ?? [], JSON_THROW_ON_ERROR),
            json_encode($this->settings['breakpoints'] ?? [], JSON_THROW_ON_ERROR),
            $classes ?? '',
            $slug,
            $lazy ? 'lazy' : 'eager',
            $alt ?? '',
            $responseType ?? 'html',
            $factor,
            json_encode($aiHint, JSON_THROW_ON_ERROR),
        ];

        return md5(implode('|', $cacheComponents));
    }

    private function getBreakpointName(string $name): string
    {
        $breakpoints = $this->settings['breakpoints'] ?? [];

        foreach ($breakpoints as $bp) {
            if ($bp['name'] === $name) {
                return '**' . ($bp['name'] ?? $name) . '**' . ' ('.$bp['width'].'px)';
            }
        }
        return $name;
    }

    public function getSlugConfig(?string $slug = null): ?string
    {
        // Ensure settings are loaded
        if (empty($this->settings)) {
            $this->settings = $this->loadConfig();
        }

        // Find specific image settings
        $imageSetting = $this->findImageSettings($slug);

        if (! $imageSetting) {
            return null;
        }

        try {
            $output = "### Responsive Image Settings for Slug: `{$slug}`".PHP_EOL.PHP_EOL;
            $output .= '| Viewport | Width | Height | Retina |'.PHP_EOL;
            $output .= '|---------|---------|----------|------------|'.PHP_EOL;
            $output .= implode('', array_map(function ($option) {
                $breakpoint = $this->getBreakpointName($option['breakpoint'] ?? '-');
                $width = $option['width'] ?? '-';
                $height = $option['height'] ?? '-';
                $retina = isset($option['retina']) ? ($option['retina'] ? 'Yes' : 'No') : '-';

                return "| {$breakpoint} | {$width} | {$height} | {$retina} |".PHP_EOL;
            }, $imageSetting['breakpointoptions'] ?? []));

            return $output;
        } catch (Exception $e) {
            $this->logError("Error generating slug config: {$e->getMessage()}");

            return $e->getMessage();
        }
    }

    /**
     * Create responsive image with advanced options
     *
     * @throws JsonException
     */
    public function makeResponsiveImage(
        string $slug,
        File $file,
        ?string $classes = null,
        bool $lazy = false,
        ?string $alt = null,
        ?string $imageType = null,
        int|float $factor = 1
    ): string {
        // Ensure settings are loaded
        if (empty($this->settings)) {
            $this->settings = $this->loadConfig();
        }

        // Find specific image settings
        $imageSetting = $this->findImageSettings($slug);

        if (! $imageSetting) {
            return $this->createDefaultResponsiveImage(
                $file,
                $classes,
                $lazy,
                $alt,
                $imageType
            );
        }

        return $this->createCustomResponsiveImage(
            $file,
            $imageSetting,
            $classes,
            $lazy,
            $alt,
            $imageType,
            'html',
            $factor
        );
    }

    /**
     * Create responsive image with advanced options as an object
     *
     * @throws JsonException
     */
    public function makeResponsiveImageObject(
        string $slug,
        File $file,
        ?string $classes = null,
        bool $lazy = false,
        ?string $alt = null,
        ?string $imageType = null,
        int|float $factor = 1
    ): string {
        // Ensure settings are loaded
        if (empty($this->settings)) {
            $this->settings = $this->loadConfig();
        }

        // Find specific image settings
        $imageSetting = $this->findImageSettings($slug);

        if (! $imageSetting) {
            return $this->createDefaultResponsiveImage(
                $file,
                $classes,
                $lazy,
                $alt,
                $imageType,
                'json'
            );
        }

        return $this->createCustomResponsiveImage(
            $file,
            $imageSetting,
            $classes,
            $lazy,
            $alt,
            $imageType,
            'json',
            $factor
        );
    }

    /**
     * Find specific image settings by slug
     */
    private function findImageSettings(string $slug): ?array
    {
        $settings = $this->settings['settings'] ?? [];
        $slugIndex = array_search($slug, array_column($settings, 'name'));

        return $slugIndex !== false ? $settings[$slugIndex] : null;
    }

    /**
     * Create default responsive image
     * @throws JsonException
     */
    private function createDefaultResponsiveImage(
        File $file,
        ?string $classes,
        bool $lazy,
        ?string $alt,
        ?string $imageType,
        ?string $responseType = 'html'
    ): string {
        $options = $this->getOptions();
        $aiHint = $this->getAiHintData($file);

        $image = Cropper::crop($file, [
            'width' => $options['defaultWidth'],
            'crop' => false,
            'format' => $imageType,
        ]);
        $src = $this->stampedUrl($image, $aiHint);

        if ($responseType === 'json') {
            return json_encode([
                'src' => $src,
                'class' => $classes ?? '',
                'lazy' => $lazy,
                'alt' => $alt,
                'aiHint' => $aiHint,
            ], JSON_THROW_ON_ERROR);
        }

        $markup = sprintf(
            '<img src="%s" class="%s" %s %s/>',
            $src,
            $classes ?? '',
            $lazy ? 'loading="lazy"' : '',
            $alt ? "alt=\"{$alt}\"" : ''
        );

        return $markup;
    }

    /**
     * Create custom responsive image with multiple sources
     * @throws JsonException
     */
    private function createCustomResponsiveImage(
        File $file,
        array $setting,
        ?string $classes,
        bool $lazy,
        ?string $alt,
        ?string $imageType,
        ?string $responseType = 'html',
        int|float $factor = 1
    ): string {
        $options = $this->getOptions();
        $cache = $this->kirby->cache('nerdcel.responsive-images');
        $aiHint = $this->getAiHintData($file);

        // Sort breakpoints
        $breakpointOptions = $setting['breakpointoptions'] ?? [];
        usort($breakpointOptions, fn($a, $b) => $b['width'] <=> $a['width']);

        // Generate cache key
        $cacheKey = $this->generateCacheKey(
            $file,
            $setting,
            $classes,
            $setting['name'],
            $lazy,
            $alt,
            $responseType,
            $factor,
            $aiHint
        );

        // Check cache
        $cachedImage = $cache->get($cacheKey);
        if ($cachedImage) {
            return $cachedImage;
        }

        // Generate responsive image
        $responsiveTag = new Tag(
            $file,
            $options,
            $this->settings['breakpoints'],
            $classes,
            $alt,
            $responseType,
            $factor,
            $aiHint
        );

        foreach ($breakpointOptions as $option) {
            try {
                $responsiveTag->addSource($option, $imageType);
            } catch (\Exception $exception) {
                $this->logError('Error generating responsive image: '.$exception->getMessage());
            }
        }

        try {
            $responsiveTag->addImg(
                array_pop($breakpointOptions),
                $lazy,
                $imageType
            );
        } catch (\Exception $exception) {
            $this->logError('Error generating responsive image: '.$exception->getMessage());
        }

        if ($responsiveTag->checkTag()) {
            if ($responseType === 'html') {
                $generatedImage = $responsiveTag->writeTag();
            } else {
                $tagObject = $responsiveTag->writeTagObject();
                $tagObject['aiHint'] = $aiHint;
                $generatedImage = json_encode($tagObject, JSON_THROW_ON_ERROR);
            }

            // Cache the result
            $cache->set($cacheKey, $generatedImage);

            return $generatedImage;
        }

        // Fallback to default image if generation fails
        if ($responseType === 'json') {
            return json_encode([
                'img' => [
                    'src' => $file->url(),
                    'class' => $classes ?? '',
                    'lazy' => $lazy,
                    'alt' => $alt,
                ],
                'source' => [],
                'aiHint' => $aiHint,
            ], JSON_THROW_ON_ERROR);
        }

        $markup = sprintf(
            '<div class="%s"><img src="%s" %s %s/></div>',
            $classes ?? '',
            $file->url(),
            $lazy ? 'loading="lazy"' : '',
            $alt ? "alt=\"{$alt}\"" : ''
        );

        return $markup;
    }
}
