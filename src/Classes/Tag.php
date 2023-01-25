<?php

namespace Nerdcel\ResponsiveImages;

use Kirby\Cms\File;

class Tag
{
    private File $resource;
    private array $config;
    private array $breakpoints;
    private array $source;
    private string $img;
    private float $retinaDensity = 1.5;
    private ?string $classes;

    public function __construct(File $file, array $config, array $breakpoints, string $classes = null)
    {
        $this->source = [];
        $this->img = '';
        $this->resource = $file;
        $this->config = $config;
        $this->breakpoints = $breakpoints;
        $this->classes = $classes;
    }

    /**
     * Return picture tag with responsive tags
     *
     * @return string
     */
    public function writeTag(): string
    {
        return '<picture>' . $this->toSortSource($this->source) . $this->img . '</picture>';
    }

    /**
     * Sort breakpoint source and return string values
     *
     * @param array $source
     *
     * @return string
     */
    private function toSortSource(array $source): string
    {
        krsort($source);
        return join('', array_map(function(array $value) {
            return ($value['retina'] ?? '').$value['standard'];
        }, $source));
    }

    /**
     * Add image tag
     *
     * @param array $config
     * @return void
     */
    function addImg(array $config): void
    {
        $imgSet = $this->adjust($config, false);
        $this->img = '<img src="' . $imgSet['image']->url() . '" width="' . $imgSet['image']->width() . '" height="' . $imgSet['image']->height() . '" class="' . $this->classes . '" alt="' . $imgSet['image']->alt() . '" title="' . $imgSet['image']->alt() . '" />';
    }

    /**
     * Add source tag
     *
     * @param array $config
     * @param boolean $default
     * @return void
     */
    public function addSource(array $config): void
    {
        $imgSet = $this->adjust($config, $config['retina']);
        $mediaqueries = array_column($this->breakpoints, 'name');
        $index = array_search($config['breakpoint'], $mediaqueries, true);
        $mediaquery = $this->breakpoints[$index]['mediaquery'];
        $mediaqueryWidth = $this->breakpoints[$index]['width'];

        $this->source[$mediaqueryWidth] = [];

        if ($config['retina'] && $imgSet['imageRetina']) {
            $this->source[$mediaqueryWidth]['retina'] = '<source srcset="' . $imgSet['imageRetina']->url() . '"
                   width="' . $imgSet['imageRetina']->width() . '"
                   height="' . $imgSet['imageRetina']->height() . '"
                   media="(' . $mediaquery . ': ' . $mediaqueryWidth . 'px) and (-webkit-min-device-pixel-ratio: ' . $this->retinaDensity . '),
                       (' . $mediaquery . ': ' . $mediaqueryWidth . 'px) and (min-device-pixel-ratio: ' . $this->retinaDensity . ')"/>';
        }

        $this->source[$mediaqueryWidth]['standard'] = '<source srcset="' . $imgSet['image']->url() . '"
            width="' . $imgSet['image']->width() . '"
            height="' . $imgSet['image']->height() . '"
            media="(' . $mediaquery . ': ' . $mediaqueryWidth . 'px)"/>';
    }

    public function adjust(array $config, bool $retina): array
    {
        $width = (int) (isset($config['width']) && ! empty($config['width']) ? $config['width'] : $this->config['defaultWidth']);
        $height = (int) (isset($config['height']) && ! empty($config['height']) ? $config['height'] : $this->resource->dimensions()->height() / $this->resource->dimensions()->width() * $width);
        $widthRetina = (int) ((isset($config['width']) && ! empty($config['width']) ? $config['width'] : $this->config['defaultWidth']) * $this->retinaDensity);
        $heightRetina = (int) (isset($config['height']) && !empty($config['height']) ? $config['height'] * $this->retinaDensity : ($this->resource->dimensions()->height() / $this->resource->dimensions()->width()) * $widthRetina);

        $return = [
            'width' => '',
            'height' => '',
            'widthRetina' => '',
            'heightRetina' => '',
        ];

        if ($config['cropwidth'] && !$config['cropheight']) {
            $image = $this->resource->crop($width, $this->resource->dimensions()->height(), ['crop' => true]);
            // $image = $image->resize($width, $height, $this->config['quality']);
            $return['width'] = $width;
            $return['height'] = $this->resource->dimensions()->height();

            if ($retina) {
                $imageRetina = $this->resource->crop($widthRetina, $this->resource->dimensions()->height() * $this->retinaDensity, ['crop' => true]);
                // $imageRetina = $imageRetina->resize($widthRetina, $heightRetina, $this->config['quality']);
                $return['widthRetina'] = $widthRetina;
                $return['heightRetina'] = $this->resource->dimensions()->height() * $this->retinaDensity;
            }
        }

        if (!$config['cropwidth'] && $config['cropheight']) {
            $image = $this->resource->crop($this->resource->dimensions()->width(), $height, ['crop' => true]);
            $return['width'] = $this->resource->dimensions()->width();
            $return['height'] = $height;

            if ($retina) {
                $imageRetina = $this->resource->crop($this->resource->dimensions()->width() * $this->retinaDensity, $heightRetina, ['crop' => true]);
                $return['widthRetina'] = $this->resource->dimensions()->width() * $this->retinaDensity;
                $return['heightRetina'] = $heightRetina;
            }
        }

        if ($config['cropwidth'] && $config['cropheight']) {
            $image = $this->resource->crop($width, $height, [
                'crop' => true
            ]);

            if ($retina) {
                $imageRetina = $this->resource->crop($widthRetina, $heightRetina, ['crop' => true]);
                $return['widthRetina'] = $widthRetina;
                $return['heightRetina'] = $heightRetina;
            }
        }

        if (!$config['cropwidth'] && !$config['cropheight']) {
            $image = $this->resource->resize($width, $height, $this->config['quality']);
            $return['width'] = $width;
            $return['height'] = $height;

            if ($retina) {
                $imageRetina = $this->resource->resize($widthRetina, $heightRetina, $this->config['quality']);
                $return['widthRetina'] = $widthRetina;
                $return['heightRetina'] = $heightRetina;
            }
        }

        return [
            'width' => $return['width'],
            'widthRetina' => $return['widthRetina'],
            'height' => $return['height'],
            'heightRetina' => $return['heightRetina'],
            'image' => $image ?? null,
            'imageRetina' => $imageRetina ?? null,
        ];
    }
}
