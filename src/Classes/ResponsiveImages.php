<?php

namespace Nerdcel\ResponsiveImages;

use Exception;
use JsonException;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Exception\PermissionException;
use Kirby\Toolkit\Config;
use Kirby\Filesystem\F;
use Kirby\Exception\LogicException;

class ResponsiveImages
{
    public array $settings = [];
    public array $config = [];
    public string $configFilePath = '';
    public string $default = '';

    /**
     * Kirby App instance
     *
     * @var \Kirby\Cms\App
     */
    protected $kirby;

    protected static $instance;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(?array $config = null, ?App $kirby = null): self
    {
        if (
            self::$instance !== null &&
            ($kirby === null || self::$instance->kirby() === $kirby)
        ) {
            return self::$instance;
        }

        return self::$instance = new self($config, $kirby);
    }

    public function __construct(?array $config = null, ?App $kirby = null)
    {
        $this->kirby = $kirby ?? App::instance();

        if ($config) {
            $this->config = $config;
        } else {
            $this->config = Config::get('nerdcel.responsive-images', [
                'configPath' => kirby()->root('content'),
                'configFile' => 'responsive-img.json',
                'quality' => 75,
                'defaultWidth' => 1024,
                'allowedRoles' => [
                    'admin',
                ],
            ]);
        }

        try {
            $this->default = json_encode(['breakpoints' => [], 'settings' => []], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->default = '{"breakpoints": [], "settings": []}';
        }

        $this->configFilePath = $this->config['configPath'].'/'.$this->config['configFile'];
    }


    /**
     * Ensures that the current user has the specified permission
     *
     * @param  string  $permission
     *
     * @return void
     *
     * @throws LogicException If no user is logged in
     * @throws PermissionException If the user does not have the required permission
     */
    public function checkPermission(string $permission): void
    {
        if ($this->hasPermission($permission) !== true) {
            throw new PermissionException([
                'key' => 'responsive-images.permission',
                'data' => compact('permission'),
            ]);
        }
    }

    /**
     * @throws LogicException
     */
    public function hasPermission(string $permission): bool
    {
        $user = $this->kirby->user();

        if ($user === null) {
            return false;
        }

        $permissions = $user->role()->permissions();

        return $permissions->for('nerdcel.responsive-images', $permission);
    }

    /**
     * Get config
     *
     * @return string JSON
     * @throws Exception
     */
    public function getConfig(): string
    {
        if (file_exists($this->configFilePath)) {
            return F::read($this->configFilePath);
        }

        F::write($this->configFilePath, $this->default);

        return $this->default;
    }

    /**
     * Write config string to file
     *
     * @param  string  $config
     *
     * @return void
     * @throws Exception
     */
    public function writeConfig(string $config): void
    {
        F::write($this->configFilePath, $config);
    }

    /**
     * Make responsive image
     *
     * @param  string  $slug
     * @param  File  $file
     * @param  string|null  $classes
     * @param  bool  $lazy
     * @param  string|null  $alt
     * @param  string|null  $imageType
     *
     * @return string
     * @throws JsonException
     */
    public function makeResponsiveImage(
        string $slug,
        File $file,
        string $classes = null,
        bool $lazy = false,
        string $alt = null,
        string $imageType = null
    ): string {
        if (! $this->settings) {
            $this->settings = json_decode($this->getConfig(), true, 512, JSON_THROW_ON_ERROR);
        }

        $settings = array_column($this->settings['settings'], 'name');

        if (count($settings)) {
            $index = array_search($slug, $settings, true);

            if ($index !== false) {
                $setting = $this->settings['settings'][$index];
                $sorted = array_column($setting['breakpointoptions'], 'width');

                array_multisort($sorted, SORT_DESC, $setting['breakpointoptions']);

                $cache = kirby()->cache('nerdcel.responsive-images');

                try {
                    $cacheKey = md5($file->hash().base64_encode(json_encode($setting['breakpointoptions'],
                            JSON_THROW_ON_ERROR)).base64_encode(json_encode($this->settings['breakpoints'],
                                JSON_THROW_ON_ERROR).$classes).$slug.$classes.$lazy.$alt);
                } catch (JsonException) {
                    $cacheKey = null;
                }

                $imgCache = $cacheKey ? $cache->get($cacheKey) : null;

                if ($imgCache === null) {
                    $RI = new Tag($file, $this->config, $this->settings['breakpoints'], $classes, $alt);

                    foreach ($setting['breakpointoptions'] as $value) {
                        $RI->addSource($value, $imageType);
                    }

                    $RI->addImg(array_pop($setting['breakpointoptions']), $lazy, $imageType);
                    $imgCache = $RI->writeTag();
                    $cache->set($cacheKey, $imgCache);
                }

                return $imgCache;
            }
        }

        return '<img src="'.Cropper::crop($file, [
                'width' => $this->config['defaultWidth'], 'crop' => false, 'format' => $imageType ?? null,
            ])->url().'" class="'.$classes.'" '.($lazy ? "loading=\"lazy\"" : null).'/>';
    }
}
