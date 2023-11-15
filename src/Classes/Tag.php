<?php

namespace Nerdcel\ResponsiveImages;

use Kirby\Cms\File;
use Nerdcel\ResponsiveImages\Cropper;

class Tag
{
    private File $resource;
    private array $config;
    private array $breakpoints;
    private array $source;
    private string $img;
    private float $retinaDensity = 1.5;
    private ?string $classes;
    private ?string $alt;

    public function __construct(File $file, array $config, array $breakpoints, string $classes = null, $alt = null)
    {
        $this->source = [];
        $this->img = '';
        $this->resource = $file;
        $this->config = $config;
        $this->breakpoints = $breakpoints;
        $this->classes = $classes;
        $this->alt = $alt;
    }

    /**
     * Return picture tag with responsive tags
     *
     * @return string
     */
    public function writeTag(): string
    {
        return '<picture>'.$this->toSortSource($this->source).$this->img.'</picture>';
    }

    /**
     * Sort breakpoint source and return string values
     *
     * @param  array  $source
     *
     * @return string
     */
    private function toSortSource(array $source): string
    {
        krsort($source);

        return join('', array_map(function (array $value) {
            return ($value['retina'] ?? '').$value['standard'];
        }, $source));
    }

    /**
     * Add image tag
     *
     * @param  array  $config
     * @param  boolean  $lazy
     * @param  string|null  $imageType
     *
     * @return void
     */
    function addImg(array $config, bool $lazy, string $imageType = null): void
    {
        $lazyOption = $lazy ? 'lazy' : 'eager';
        $imgSet = $this->adjust($config, false, $imageType);
        $this->img = '<img src="'.$imgSet['image']->url().'" width="'.$imgSet['image']->width().'" height="'.$imgSet['image']->height().'" class="'.$this->classes.'" alt="'.($this->alt ?: $imgSet['image']->alt()).'" title="'.($this->alt ?: $imgSet['image']->alt()).'" loading="'.$lazyOption.'"/>';
    }

    /**
     * Add source tag
     *
     * @param  array  $config
     * @param  boolean  $default
     *
     * @return void
     */
    public function addSource(array $config, string $imageType = null): void
    {
        $imgSet = $this->adjust($config, $config['retina'], $imageType);
        $mediaqueries = array_column($this->breakpoints, 'name');
        $index = array_search($config['breakpoint'], $mediaqueries, true);
        $mediaquery = $this->breakpoints[$index]['mediaquery'];
        $mediaqueryWidth = $this->breakpoints[$index]['width'];

        $this->source[$mediaqueryWidth] = [];

        if ($config['retina'] && $imgSet['imageRetina']) {
            $this->source[$mediaqueryWidth]['retina'] = '<source srcset="'.$imgSet['imageRetina']->url().'"
                   width="'.$imgSet['imageRetina']->width().'"
                   height="'.$imgSet['imageRetina']->height().'"
                   media="('.$mediaquery.': '.$mediaqueryWidth.'px) and (-webkit-min-device-pixel-ratio: '.$this->retinaDensity.'),
                       ('.$mediaquery.': '.$mediaqueryWidth.'px) and (min-device-pixel-ratio: '.$this->retinaDensity.')"/>';
        }

        $this->source[$mediaqueryWidth]['standard'] = '<source srcset="'.$imgSet['image']->url().'"
            width="'.$imgSet['image']->width().'"
            height="'.$imgSet['image']->height().'"
            media="('.$mediaquery.': '.$mediaqueryWidth.'px)"/>';
    }

    /**
     * @param  array  $config
     * @param  bool  $retina
     * @param  string|null  $imageType
     *
     * @return array
     */
    public function adjust(array $config, bool $retina, string $imageType = null): array
    {
        $width = (int) (isset($config['width']) && ! empty($config['width']) ? $config['width'] : $this->config['defaultWidth']);
        $height = (int) (isset($config['height']) && ! empty($config['height']) ? $config['height'] : $this->resource->dimensions()->height() / $this->resource->dimensions()->width() * $width);
        $widthRetina = (int) ((isset($config['width']) && ! empty($config['width']) ? $config['width'] : $this->config['defaultWidth']) * $this->retinaDensity);
        $heightRetina = (int) (isset($config['height']) && ! empty($config['height']) ? $config['height'] * $this->retinaDensity : ($this->resource->dimensions()->height() / $this->resource->dimensions()->width()) * $widthRetina);

        $cropWidth = $config['cropwidth'] && $config['width'] ?? false;
        $cropHeight = $config['cropheight'] && $config['height'] ?? false;

        $return = [
            'width' => '',
            'height' => '',
            'widthRetina' => '',
            'heightRetina' => '',
        ];

        if ($cropWidth && ! $cropHeight) {
            $originalHeight = $this->resource->dimensions()->height();
            $image = Cropper::crop($this->resource, [
                    'width' => $width,
                    'height' => $originalHeight,
                    'crop' => true,
                    'format' => $imageType ?? null,
                ]
            );
            $return['width'] = $width;
            $return['height'] = $originalHeight;

            if ($retina) {
                $originalRetinaHeight = $this->resource->dimensions()->height() * $this->retinaDensity;
                $imageRetina = Cropper::crop($this->resource, [
                    'width' => $widthRetina,
                    'height' => $originalRetinaHeight,
                    'crop' => true,
                    'format' => $imageType ?? null,
                ]);
                $return['widthRetina'] = $widthRetina;
                $return['heightRetina'] = $originalRetinaHeight;
            }
        }

        if (! $cropWidth && $cropHeight) {
            $originalWidth = $this->resource->dimensions()->width();
            $image = Cropper::crop($this->resource, [
                'width' => $originalWidth,
                'height' => $height,
                'crop' => true,
                'format' => $imageType ?? null,
            ]);
            $return['width'] = $originalWidth;
            $return['height'] = $height;

            if ($retina) {
                $originalRetinaWidth = $this->resource->dimensions()->width() * $this->retinaDensity;
                $imageRetina = Cropper::crop($this->resource, [
                    'width' => $originalRetinaWidth,
                    'height' => $heightRetina,
                    'crop' => true,
                    'format' => $imageType ?? null,
                ]);
                $return['widthRetina'] = $originalRetinaWidth;
                $return['heightRetina'] = $heightRetina;
            }
        }

        if ($cropWidth && $cropHeight) {
            $image = Cropper::crop($this->resource, [
                'width' => $width,
                'height' => $height,
                'crop' => true,
                'format' => $imageType ?? null,
            ]);

            if ($retina) {
                $imageRetina = Cropper::crop($this->resource, [
                    'width' => $widthRetina,
                    'height' => $heightRetina,
                    'crop' => true,
                    'format' => $imageType ?? null,
                ]);
                $return['widthRetina'] = $widthRetina;
                $return['heightRetina'] = $heightRetina;
            }
        }

        if (! $cropWidth && ! $cropHeight) {
            $image = Cropper::crop($this->resource, [
                'width' => $width,
                'height' => $height,
                'crop' => false,
                'format' => $imageType ?? null,
                'quality' => $this->config['quality'] ?? '80',
            ]);
            $return['width'] = $width;
            $return['height'] = $height;

            if ($retina) {
                $imageRetina = Cropper::crop($this->resource, [
                    'width' => $widthRetina,
                    'height' => $heightRetina,
                    'crop' => false,
                    'format' => $imageType ?? null,
                    'quality' => $this->config['quality'] ?? '80',
                ]);
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
