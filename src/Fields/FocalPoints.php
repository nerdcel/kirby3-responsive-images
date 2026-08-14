<?php

use Kirby\Data\Data;
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

        /**
         * Resolved AI hint settings (blueprint defaults merged with the
         * editor's overrides), so the panel preview can show the real
         * hint overlay exactly as it will appear on the frontend.
         *
         * @this Field
         */
        'aiHint' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            $file = $this->model();

            if (! $file instanceof \Kirby\Cms\File) {
                return null;
            }

            return (new ResponsiveImages(kirby()))->getAiHintData($file);
        },

        /**
         * Real image dimensions, so the preview can use the same
         * aspect ratio as the actual delivered image.
         *
         * @this Field
         */
        'imageWidth' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            $file = $this->model();

            return $file instanceof \Kirby\Cms\File ? $file->dimensions()->width() : null;
        },

        /** @this Field */
        'imageHeight' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            $file = $this->model();

            return $file instanceof \Kirby\Cms\File ? $file->dimensions()->height() : null;
        },
    ],

    /**
     * Decode the raw stored value into a breakpoint => focal point map for
     * the panel. Runs after props are applied, reading the still-raw stored
     * value from `$this->value` and replacing it with the decoded array.
     * IMPORTANT: because of this, `$this->value` is a structured array from
     * here on – the "save" option below turns it back into a storable string.
     */
    'computed' => [
        /** @this Field */
        'value' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            return Data::decode($this->value ?: [], 'yaml', false);
        },
    ],

    'methods' => [
        /**
         * Fallback value used when the field has never been touched
         */
        'emptyValue' => function () {
            return [];
        },
    ],

    /**
     * Re-encode the (already decoded) breakpoint map back into a plain
     * array. Kirby's content writer automatically stores arrays as native
     * YAML in the content file (exactly like structure/object fields do),
     * so no manual YAML encoding is needed here.
     */
    'save' => function ($value) {
        if (is_string($value)) {
            $value = Data::decode($value ?: [], 'yaml', false);
        }

        if (! is_array($value)) {
            $value = [];
        }

        return $value;
    },
];
