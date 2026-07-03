<?php

use Nerdcel\ResponsiveImages\ResponsiveImages;
use Kirby\Cms\Field;

return [
    'props' => [
        /** @this Field */
        'focalpoints' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            return $this->focalpoints();
        },

        /** @this Field */
        'label' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            return $this->label() ?? I18n::translate('nerdcel.responsive-images.field.label.set-focal-point');
        },

        /** @this Field */
        'help' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            return $this->help() ?? I18n::translate('nerdcel.responsive-images.field.help.set-focal-point');
        },

        /** @this Field */
        'fieldModel' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            return $this->model()->toArray();
        },

        'breakpoints' => function () {
            $config = (new ResponsiveImages(kirby()))->loadConfig();
            return $config['breakpoints'] ?? [];
        },

        /** @this Field */
        'fileType' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            return $this->model()->type();
        },

        /** @this Field */
        'value' => function ($value = []) {
            if (is_array($value)) {
                return $value;
            }
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            return $this->model()->focalpoints()->toBreakpointFocal();
        },
    ],
];
