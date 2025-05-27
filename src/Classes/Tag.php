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
    private array $imgObj;
    private float $retinaDensity = 1.5;
    private ?string $classes;
    private ?string $alt;
    private string $responseType;
    private float $factor = 1;

    public function __construct(
        File $file,
        array $config,
        array $breakpoints,
        string $classes = null,
        $alt = null,
        $responseType = 'html',
        $factor = 1
    ) {
        $this->source = [];
        $this->img = '';
        $this->resource = $file;
        $this->config = $config;
        $this->breakpoints = $breakpoints;
        $this->classes = $classes;
        $this->alt = $alt;
        $this->responseType = $responseType;
        $this->factor = (float) $factor;
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

    public function checkTag(): bool
    {
        return ! empty($this->source) || ! empty($this->img);
    }

    /**
     * Return the picture tag as an array
     *
     * @return array
     */
    public function writeTagObject(): array
    {
        return [
            'source' => $this->source,
            'img' => $this->imgObj,
        ];
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
    public function addImg(array $config, bool $lazy, string $imageType = null): void
    {
        try {
            $lazyOption = $lazy ? 'lazy' : 'eager';
            $imgSet = $this->adjust($config, false, $imageType);

            if ($this->responseType === 'json') {
                $this->imgObj = [
                    'src' => $imgSet['image']->url(),
                    'width' => $imgSet['image']->width(),
                    'height' => $imgSet['image']->height(),
                    'class' => $this->classes,
                    'alt' => $this->alt ?: $imgSet['image']->alt()->value(),
                    'title' => $this->alt ?: $imgSet['image']->alt()->value(),
                    'loading' => $lazyOption,
                ];
            } else {
                $this->img = '<img src="'.$imgSet['image']->url().'" width="'.$imgSet['image']->width().'" height="'.$imgSet['image']->height().'" class="'.$this->classes.'" alt="'.($this->alt ?: $imgSet['image']->alt()->value()).'" title="'.($this->alt ?: $imgSet['image']->alt()->value()).'" loading="'.$lazyOption.'"/>';
            }
        } catch (\Exception $e) {
            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /**
     * Add source tag
     *
     * @param  array  $config
     * @param  string|null  $imageType
     *
     * @return void
     * @throws \JsonException
     */
    public function addSource(array $config, string $imageType = null): void
    {
        try {
            $imgSet = $this->adjust($config, $config['retina'], $imageType);
            $mediaqueries = array_column($this->breakpoints, 'name');
            $index = array_search($config['breakpoint'], $mediaqueries, true);
            $mediaquery = $this->breakpoints[$index]['mediaquery'];
            $mediaqueryWidth = $this->breakpoints[$index]['width'];

            $this->source[$mediaqueryWidth] = [];

            if ($config['retina'] && $imgSet['imageRetina']) {
                if ($this->responseType === 'json') {
                    $this->source[$mediaqueryWidth]['retina'] = [
                        'src' => $imgSet['imageRetina']->url(),
                        'width' => $imgSet['imageRetina']->width(),
                        'height' => $imgSet['imageRetina']->height(),
                        'media' => '('.$mediaquery.': '.$mediaqueryWidth.'px) and (-webkit-min-device-pixel-ratio: '.$this->retinaDensity.'),
                           ('.$mediaquery.': '.$mediaqueryWidth.'px) and (min-device-pixel-ratio: '.$this->retinaDensity.')',
                    ];
                } else {
                    $this->source[$mediaqueryWidth]['retina'] = '<source srcset="'.$imgSet['imageRetina']->url().'"
                           width="'.$imgSet['imageRetina']->width().'"
                           height="'.$imgSet['imageRetina']->height().'"
                           media="('.$mediaquery.': '.$mediaqueryWidth.'px) and (-webkit-min-device-pixel-ratio: '.$this->retinaDensity.'),
                               ('.$mediaquery.': '.$mediaqueryWidth.'px) and (min-device-pixel-ratio: '.$this->retinaDensity.')"/>';
                }
            }

            if ($this->responseType === 'json') {
                $this->source[$mediaqueryWidth]['standard'] = [
                    'src' => $imgSet['image']->url(),
                    'width' => $imgSet['image']->width(),
                    'height' => $imgSet['image']->height(),
                    'media' => '('.$mediaquery.': '.$mediaqueryWidth.'px)',
                ];
            } else {
                $this->source[$mediaqueryWidth]['standard'] = '<source srcset="'.$imgSet['image']->url().'"
                    width="'.$imgSet['image']->width().'"
                    height="'.$imgSet['image']->height().'"
                    media="('.$mediaquery.': '.$mediaqueryWidth.'px)"/>';
            }
        } catch (\Exception $e) {
            throw new \Exception('Error: '.$e->getMessage());
        }
    }

    /**
     * Get focus point from resource
     * Use focus or focalpoints breakpoints
     *
     * @return bool|string
     */
    private function getFocus($config)
    {
        $focalPoints = $this->resource->focalpoints()->toBreakpointFocal();

        if ($config && isset($config['breakpoint'])) {
            $focus = $focalPoints[$config['breakpoint']] ?? true;
        } else {
            $focus = $this->resource->focus()->isNotEmpty() ? $this->resource->focus()->value() : true;
        }

        return $focus ?? true;
    }

    /**
     * @param  array  $config
     * @param  bool  $retina
     * @param  string|null  $imageType
     *
     * @return array
     * @throws \Exception
     */
    public function adjust(array $config, bool $retina, string $imageType = null): array
    {
        // If config width is greater or equal 640, use the factor from the constructor and multiply it with the width, also for height to keep the aspect ratio
        if (isset($config['width']) && (int) $config['width'] >= 640) {
            $config['width'] = (int) ((int) $config['width'] * $this->factor);
            $config['height'] = (int) ((int) $config['height'] * $this->factor);
        }
        $width = (int) (isset($config['width']) && ! empty($config['width']) ? $config['width'] : $this->config['defaultWidth']);
        $height = (int) (isset($config['height']) && ! empty($config['height']) ? $config['height'] : $this->resource->dimensions()->height() / $this->resource->dimensions()->width() * $width);
        $widthRetina = (int) ((isset($config['width']) && ! empty($config['width']) ? $config['width'] : $this->config['defaultWidth']) * $this->retinaDensity);
        $heightRetina = (int) (isset($config['height']) && ! empty($config['height']) ? $config['height'] * $this->retinaDensity : ($this->resource->dimensions()->height() / $this->resource->dimensions()->width()) * $widthRetina);

        $cropWidth = $config['cropwidth'] && $config['width'] ?? false;
        $cropHeight = $config['cropheight'] && $config['height'] ?? false;

        $originalHeight = $this->resource->dimensions()->height();
        $originalWidth = $this->resource->dimensions()->width();

        $return = [
            'width' => '',
            'height' => '',
            'widthRetina' => '',
            'heightRetina' => '',
        ];

        if ($cropWidth && ! $cropHeight) {
            try {
                $image = $this->resource->thumb([
                        'width' => $width,
                        'height' => $originalHeight,
                        'crop' => $this->getFocus($config),
                        'format' => $imageType ?? null,

                    ]
                );
            } catch (\Exception $e) {
                throw new \Exception('Error: '.$e->getMessage());
            }
            $return['width'] = $width;
            $return['height'] = $originalHeight;

            if ($retina) {
                $originalRetinaHeight = (int) ($this->resource->dimensions()->height() * $this->retinaDensity);
                try {
                    $imageRetina = $this->resource->thumb([
                        'width' => $widthRetina,
                        'height' => $originalRetinaHeight,
                        'crop' => $this->getFocus($config),
                        'format' => $imageType ?? null,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Error: '.$e->getMessage());
                }
                $return['widthRetina'] = $widthRetina;
                $return['heightRetina'] = $originalRetinaHeight;
            }
        }

        if (! $cropWidth && $cropHeight) {
            try {
                $image = $this->resource->thumb([
                    'width' => $originalWidth,
                    'height' => $height,
                    'crop' => $this->getFocus($config),
                    'format' => $imageType ?? null,
                ]);
            } catch (\Exception $e) {
                throw new \Exception('Error: '.$e->getMessage());
            }
            $return['width'] = $originalWidth;
            $return['height'] = $height;

            if ($retina) {
                $originalRetinaWidth = (int) ($this->resource->dimensions()->width() * $this->retinaDensity);
                try {
                    $imageRetina = $this->resource->thumb([
                        'width' => $originalRetinaWidth,
                        'height' => $heightRetina,
                        'crop' => $this->getFocus($config),
                        'format' => $imageType ?? null,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Error: '.$e->getMessage());
                }
                $return['widthRetina'] = $originalRetinaWidth;
                $return['heightRetina'] = $heightRetina;
            }
        }

        if ($cropWidth && $cropHeight) {
            try {
                $image = $this->resource->thumb([
                    'width' => $width,
                    'height' => $height,
                    'crop' => $this->getFocus($config),
                    'format' => $imageType ?? null,
                ]);
            } catch (\Exception $e) {
                throw new \Exception('Error: '.$e->getMessage());
            }

            if ($retina) {
                try {
                    $imageRetina = $this->resource->thumb([
                        'width' => $widthRetina,
                        'height' => $heightRetina,
                        'crop' => $this->getFocus($config),
                        'format' => $imageType ?? null,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Error: '.$e->getMessage());
                }
                $return['widthRetina'] = $widthRetina;
                $return['heightRetina'] = $heightRetina;
            }
        }

        if (! $cropWidth && ! $cropHeight) {
            try {
                $image = $this->resource->thumb([
                    'width' => $width,
                    'height' => $height,
                    'crop' => false,
                    'format' => $imageType ?? null,
                    'quality' => $this->config['quality'] ?? '80',
                ]);
            } catch (\Exception $e) {
                throw new \Exception('Error: '.$e->getMessage());
            }
            $return['width'] = $width;
            $return['height'] = $height;

            if ($retina) {
                try {
                    $imageRetina = $this->resource->thumb([
                        'width' => $widthRetina,
                        'height' => $heightRetina,
                        'crop' => false,
                        'format' => $imageType ?? null,
                        'quality' => $this->config['quality'] ?? '80',
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Error: '.$e->getMessage());
                }
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
