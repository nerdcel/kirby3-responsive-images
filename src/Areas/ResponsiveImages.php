<?php

use Kirby\Toolkit\I18n;
use Nerdcel\ResponsiveImages\ResponsiveImages;

return function () {
    try {
        $accessPermission = (new ResponsiveImages(kirby()))->hasPermission('access');
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
                            'component' => 'nerdcel-restricted',

                            'title' => I18n::translate('nerdcel.responsive-images.panel.restricted.title'),
                        ];
                    }

                    $config = (new ResponsiveImages(kirby()))->loadConfig();

                    return [
                        'component' => 'nerdcel-responsive-images',

                        'title' => I18n::translate('nerdcel.responsive-images.panel.title'),

                        'props' => [

                            'value' => $config,

                            'fields' => [
                                'breakpoints' => [
                                    'label' => 'Breakpoints',
                                    'type' => 'structure',
                                    'columns' => [
                                        'name' => [
                                            'label' => 'Name',
                                            'type' => 'slug',
                                            'width' => '1/2',
                                        ],
                                        'width' => [
                                            'label' => 'Width',
                                            'type' => 'number',
                                            'width' => '1/2',
                                        ],
                                    ],
                                    'fields' => [
                                        'name' => [
                                            'label' => 'Name',
                                            'type' => 'text',
                                        ],
                                        'width' => [
                                            'label' => 'Width',
                                            'type' => 'number',
                                        ],
                                        'mediaquery' => [
                                            'label' => 'Media query',
                                            'type' => 'select',
                                            'options' => [
                                                [
                                                    'value' => 'min-width',
                                                    'text' => 'min-width',
                                                ],
                                                [
                                                    'value' => 'max-width',
                                                    'text' => 'max-width',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'settings' => [
                                    'label' => 'Settings',
                                    'type' => 'structure',
                                    'columns' => [
                                        'name' => [
                                            'label' => 'Name',
                                            'type' => 'text',
                                            'width' => '1/2',
                                        ],
                                        'breakpointoptions' => [
                                            'label' => 'Breakpoint options',
                                            'type' => 'structure',
                                            'width' => '1/2',
                                        ],
                                    ],
                                    'fields' => [
                                        'name' => [
                                            'label' => 'Name',
                                            'type' => 'slug',
                                            'required' => true,
                                        ],
                                        'breakpointoptions' => [
                                            'label' => 'Breakpoint options',
                                            'type' => 'structure',
                                            'columns' => [
                                                'breakpoint' => [
                                                    'label' => 'Breakpoint',
                                                    'type' => 'select',
                                                    'width' => '1/6',
                                                ],
                                                'width' => [
                                                    'label' => 'Width',
                                                    'type' => 'number',
                                                    'width' => '1/6',
                                                ],
                                                'cropwidth' => [
                                                    'label' => 'Crop width',
                                                    'type' => 'toggle',
                                                    'width' => '1/6',
                                                ],
                                                'height' => [
                                                    'label' => 'Height',
                                                    'type' => 'number',
                                                    'width' => '1/6',
                                                ],
                                                'cropheight' => [
                                                    'label' => 'Crop height',
                                                    'type' => 'toggle',
                                                    'width' => '1/6',
                                                ],
                                                'retina' => [
                                                    'label' => 'Retina',
                                                    'type' => 'toggle',
                                                    'width' => '1/6',
                                                ],
                                            ],
                                            'fields' => [
                                                'breakpoint' => [
                                                    'label' => 'Breakpoint',
                                                    'type' => 'select',
                                                    'options' => array_map(function ($breakpoint) {
                                                        return [
                                                            'value' => $breakpoint['name'],
                                                            'text' => $breakpoint['name'],
                                                        ];
                                                    }, $config['breakpoints']),
                                                ],
                                                'width' => [
                                                    'label' => 'Width',
                                                    'type' => 'number',
                                                ],
                                                'cropwidth' => [
                                                    'label' => 'Crop width',
                                                    'type' => 'toggle',
                                                    'default' => false,
                                                ],
                                                'height' => [
                                                    'label' => 'Height',
                                                    'type' => 'number',
                                                ],
                                                'cropheight' => [
                                                    'label' => 'Crop height',
                                                    'type' => 'toggle',
                                                    'default' => false,
                                                ],
                                                'retina' => [
                                                    'label' => 'Retina',
                                                    'type' => 'toggle',
                                                    'default' => true,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],

                            'endpoints' => [
                                'field' => 'responsive-images',
                                'section' => 'responsive-images',
                                'model' => 'responsive-images',
                            ],
                        ],
                    ];
                },
            ],
        ],
    ];
};
