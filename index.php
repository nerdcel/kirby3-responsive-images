<?php

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('nerdcel/responsive-images', [
    'options' => require __DIR__ . '/src/config.php',

    'translations' => [
        'en' => require __DIR__ . '/i18n/en.php',
        'de' => require __DIR__ . '/i18n/de.php'
    ],

    'areas' => [
        'responsiveimages' => require __DIR__ . '/src/Areas/ResponsiveImages.php',
    ],

    'api' => [
        'routes' => require 'src/routes.php'
    ],

    'permissions' => [
        'access' => true
    ],

    'fields' => [
        'focalpoints' => require __DIR__ . '/src/Fields/FocalPoints.php',
    ],

    'fieldMethods' => require __DIR__ . '/src/fieldMethods.php',
]);
