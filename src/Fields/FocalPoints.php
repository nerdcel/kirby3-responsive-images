<?php

use Nerdcel\ResponsiveImages\ResponsiveImages;

$config = (new ResponsiveImages(kirby()))->loadConfig();

return [
    'model' => [

    ],

    'props' => [
        'focalpoints' => function () {
            return $this->focalpoints();
        },

        'label' => function () {
            return $this->label() ?? I18n::translate('nerdcel.responsive-images.field.label.set-focal-point');
        },

        'help' => function () {
            return $this->help() ?? I18n::translate('nerdcel.responsive-images.field.help.set-focal-point');
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
            return $this->model()->focalpoints()->toBreakpointFocal();
        },
    ],
];
