<?php

namespace Nerdcel\ResponsiveImages;

use Exception;
use JsonException;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\User;
use Kirby\Exception\PermissionException;
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
                $this->writeConfig(json_encode(self::DEFAULT_CONFIG));
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
        if ($this->logger) {
            $this->logger->error($message);
        }
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
     */
    private function generateCacheKey(
        File $file,
        array $setting,
        ?string $classes,
        string $slug,
        bool $lazy,
        ?string $alt
    ): string {
        $cacheComponents = [
            $file->mediaHash()(),
            json_encode($setting['breakpointoptions'] ?? []),
            json_encode($this->settings['breakpoints'] ?? []),
            $classes ?? '',
            $slug,
            $lazy ? 'lazy' : 'eager',
            $alt ?? '',
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
        ?string $imageType = null
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
            $imageType
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
     */
    private function createDefaultResponsiveImage(
        File $file,
        ?string $classes,
        bool $lazy,
        ?string $alt,
        ?string $imageType
    ): string {
        $options = $this->getOptions();

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
     */
    private function createCustomResponsiveImage(
        File $file,
        array $setting,
        ?string $classes,
        bool $lazy,
        ?string $alt,
        ?string $imageType
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
            $alt
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
            $alt
        );

        foreach ($breakpointOptions as $option) {
            $responsiveTag->addSource($option, $imageType);
        }

        $responsiveTag->addImg(
            array_pop($breakpointOptions),
            $lazy,
            $imageType
        );

        $generatedImage = $responsiveTag->writeTag();

        // Cache the result
        $cache->set($cacheKey, $generatedImage);

        return $generatedImage;
    }
}
