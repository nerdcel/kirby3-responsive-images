<?php

use Kirby\Cms\File;
use Nerdcel\ResponsiveImages\ResponsiveImages;

if (!function_exists('responsiveImage')) {
    /**
     * Creates a responsive image based on slug from a kirby file
     *
     * @param  string  $responsiveImageSlug
     * @param  File  $file
     * @param  string|null  $classes
     * @param  bool  $lazy
     * @param  null  $alt
     *
     * @return string
     * @throws Exception
     */
    function responsiveImage(string $responsiveImageSlug, File $file, string $classes = null, $lazy = false, $alt = null, $imageType = null, $responseType = 'html'): string
    {
        try {
            $ext = $file->extension();
            // If file is a gif, use the original file
            if ($ext === 'gif') {
                return $file;
            }

            if ($responseType === 'html') {
                return (new ResponsiveImages(kirby()))->makeResponsiveImage($responsiveImageSlug, $file, $classes, $lazy, $alt, $imageType);
            }
            return (new ResponsiveImages(kirby()))->makeResponsiveImageObject($responsiveImageSlug, $file, $classes, $lazy, $alt, $imageType);
        } catch (JsonException|\Kirby\Exception\InvalidArgumentException $e) {
            return '';
        }
    }
}
