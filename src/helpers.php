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
    function responsiveImage(string $responsiveImageSlug, File $file, string $classes = null, $lazy = false, $alt = null): string
    {
        try {
            return ResponsiveImages::getInstance()->makeResponsiveImage($responsiveImageSlug, $file, $classes, $lazy, $alt);
        } catch (JsonException|\Kirby\Exception\InvalidArgumentException $e) {
            return '';
        }
    }
}
