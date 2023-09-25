<?php

use Kirby\Toolkit\I18n;
use Nerdcel\ResponsiveImages\ResponsiveImages;

return function () {
    try {
        $accessPermission = ResponsiveImages::getInstance()->hasPermission('access');
    } catch (LogicException $e) {
        // area was loaded by Kirby without a logged-in user
        $accessPermission = false;
    }

    return [
        'label' => I18n::translate('nerdcel.responsive-images.panel.label'),
        'icon' => 'image',
        'menu' => $accessPermission,
        'link' => 'responsiveimages',
        'views' => [
            [
                'pattern' => 'responsiveimages',
                'action' => function () {

                    if (! (kirby()->user() && array_intersect(kirby()->user()->roles()->pluck('name'),
                            kirby()->option('nerdcel.responsive-images.allowedRoles', [])))) {
                        return [
                            'component' => 'k-nerdcel-restricted',

                            'title' => I18n::translate('nerdcel.responsive-images.panel.restricted.title'),
                        ];
                    }

                    $responsiveImagesConfig = ResponsiveImages::getInstance()->getConfig();

                    try {
                        $json = json_decode($responsiveImagesConfig, false, 512, JSON_THROW_ON_ERROR);
                    } catch (JsonException $e) {
                        $json = [];
                    }

                    if (! isset($json->breakpoints, $json->settings)) {
                        $responsiveImagesConfig = ResponsiveImages::getInstance()->default;
                    }

                    try {
                        $config = json_decode($responsiveImagesConfig, false, 512, JSON_THROW_ON_ERROR);
                    } catch (JsonException $e) {
                        $config = [];
                    }

                    return [
                        'component' => 'k-nerdcel-responsive-images',

                        'title' => I18n::translate('nerdcel.responsive-images.panel.title'),

                        'props' => [
                            'config' => $config,
                        ],
                    ];
                },
            ],
        ],
    ];
};
