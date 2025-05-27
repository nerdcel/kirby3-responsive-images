<?php

namespace Nerdcel\ResponsiveImages;

use Exception;
use JsonException;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\User;
use Kirby\Filesystem\F;
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
        ]);
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
        int|float $factor = 1
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
        ];

        return md5(implode('|', $cacheComponents));
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

        if ($responseType === 'json') {
            return json_encode([
                'src' => Cropper::crop($file, [
                    'width' => $options['defaultWidth'],
                    'crop' => false,
                    'format' => $imageType,
                ])->url(),
                'class' => $classes ?? '',
                'lazy' => $lazy,
                'alt' => $alt,
            ], JSON_THROW_ON_ERROR);
        }

        return sprintf(
            '<img src="%s" class="%s" %s %s/>',
            Cropper::crop($file, [
                'width' => $options['defaultWidth'],
                'crop' => false,
                'format' => $imageType,
            ])->url(),
            $classes ?? '',
            $lazy ? 'loading="lazy"' : '',
            $alt ? "alt=\"{$alt}\"" : ''
        );
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
            $factor
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
            $factor
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
                $generatedImage = json_encode($responsiveTag->writeTagObject(), JSON_THROW_ON_ERROR);
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
            ], JSON_THROW_ON_ERROR);
        }

        return sprintf(
            '<div class="%s"><img src="%s" %s %s/></div>',
            $classes ?? '',
            $file->url(),
            $lazy ? 'loading="lazy"' : '',
            $alt ? "alt=\"{$alt}\"" : ''
        );
    }
}
