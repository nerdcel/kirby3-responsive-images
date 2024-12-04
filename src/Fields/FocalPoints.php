<?php

use Nerdcel\ResponsiveImages\ResponsiveImages;

$config = ResponsiveImages::getInstance()->loadConfig();

return [
    'props' => [
        'focalpoints' => function () {
            return $this->focalpoints();
        },

        'label' => function () {
            return $this->label() ?? I18n::translate('nerdcel.responsive-images.field.set-focal-point');
        },

        'fieldModel' => function () {
            return $this->model()->toArray();
        },

        'breakpoints' => function () use ($config) {
            return $config->breakpoints ?? [];
        },

        'fileType' => function () {
            return $this->model()->type();
        },

        'value' => function ($value = []) use ($config) {
            if (is_array($value)) {
                return $value;
            }
            return Data::decode($value, 'yaml');
        },
    ],
];
