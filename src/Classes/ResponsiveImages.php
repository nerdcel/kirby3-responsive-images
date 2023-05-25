<?php

namespace Nerdcel\ResponsiveImages;

use Exception;
use JsonException;
use Kirby\Cms\File;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Toolkit\Config;
use Kirby\Filesystem\F;

class ResponsiveImages
{
    public array $settings = [];
    public array $config = [];
    public string $configFilePath = '';
    public string $default = '';

    protected static $instance;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): ResponsiveImages
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function __construct()
    {
        $this->config = Config::get('nerdcel.responsive-images', [
            'configPath' => kirby()->root('content'),
            'configFile' => 'responsive-img.json',
            'quality' => 75,
            'defaultWidth' => 1024,
            'allowedRoles' => [
                'admin',
            ],
        ]);

        try {
            $this->default = json_encode(['breakpoints' => [], 'settings' => []], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->default = '{"breakpoints": [], "settings": []}';
        }

        $this->configFilePath = $this->config['configPath'].'/'.$this->config['configFile'];
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
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws Exception
     */
    public function makeResponsiveImage(string $slug, File $file, string $classes = null, $lazy = false, $alt = null): string
    {
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
                        $RI->addSource($value);
                    }

                    $RI->addImg(array_pop($setting['breakpointoptions']), $lazy);
                    $imgCache = $RI->writeTag();
                    $cache->set($cacheKey, $imgCache);
                }

                return $imgCache;
            }
        }

        return '<img src="'.$file->resize($this->config['defaultWidth'])->url().'" class="'.$classes.'" ' . ($lazy ? "loading=\"lazy\"" : null) . '/>';
    }
}
