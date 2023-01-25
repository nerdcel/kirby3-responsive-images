<?php

use Kirby\Cms\File;
use Nerdcel\ResponsiveImages\ResponsiveImages;

if (!function_exists('responsiveImage')) {
    /**
     * Creates a responsive image baed on slug from a kirby file
     *
     * @param string $responsiveImageSlug
     * @param File $file
     * @param  string|null  $classes
     *
     * @return void
     */
    function responsiveImage(string $responsiveImageSlug, File $file, string $classes = null): void
    {
        try {
            echo ResponsiveImages::getInstance()->makeResponsiveImage($responsiveImageSlug, $file, $classes);
        } catch (JsonException|\Kirby\Exception\InvalidArgumentException $e) {
            echo '';
        }
    }
}
