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
    'cropDriver' => null
];
