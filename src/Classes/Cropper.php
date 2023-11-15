<?php

namespace Nerdcel\ResponsiveImages;

use Kirby\Cms\File;
use Kirby\Toolkit\Config;

class Cropper
{
    public static function crop(File $file, array $options = [])
    {
        if ($driver = Config::get('nerdcel.responsive-images.cropDriver')) {
            return $driver($file, [
                'width' => $options['width'] ?? null,
                'height' => $options['height'] ?? null,
                'quality' => $options['quality'] ?? null,
                'upscale' => $options['upscale'] ?? null,
                'format' => $options['format'] ?? null,
            ]);
        }

        return $file->thumb($options);
    }
}
