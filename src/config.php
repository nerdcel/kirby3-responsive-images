<?php

return [
    'cache' => true,
    'configPath' => kirby()->root('content'),
    'configFile' => 'responsive-img.json',
    'quality' => 75,
    'defaultWidth' => 1024,
    'allowedRoles' => [
        'admin',
    ],
    'cropDriver' => null,
    'aiHint' => [
        'text' => 'AI generated image',
        'position' => 'bottom-right',
        'color' => '#ffffff',
        'size' => 'medium',
        'font' => null, // absolute path to a .ttf font used to stamp the hint into the image; falls back to common system fonts, then to a GD bitmap font
    ],
];
