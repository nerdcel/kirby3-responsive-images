<?php

use Kirby\Data\Data;

/**
 * Normalizes any raw decoded aihint payload into the predictable
 * shape used throughout this field, its computed value and storage.
 */
$normalize = function (array $decoded): array {
    return [
        'enabled' => (bool) ($decoded['enabled'] ?? false),
        'text' => $decoded['text'] ?? null,
        'position' => $decoded['position'] ?? null,
        'color' => $decoded['color'] ?? null,
        'size' => isset($decoded['size']) ? \Nerdcel\ResponsiveImages\ResponsiveImages::normalizeSize($decoded['size']) : null,
        'opacity' => isset($decoded['opacity']) ? (int) $decoded['opacity'] : null,
        'margin' => isset($decoded['margin']) ? \Nerdcel\ResponsiveImages\ResponsiveImages::normalizeMargin($decoded['margin']) : null,
        'padding' => isset($decoded['padding']) ? \Nerdcel\ResponsiveImages\ResponsiveImages::normalizePadding($decoded['padding']) : null,
    ];
};

return [
    'props' => [
        /** @this Kirby\Cms\Field */
        'label' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            return $this->label() ?? I18n::translate('nerdcel.responsive-images.aihint.label');
        },

        /** @this Kirby\Cms\Field */
        'help' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            return $this->help() ?? I18n::translate('nerdcel.responsive-images.aihint.help');
        },

        /**
         * Default hint text, configurable per blueprint, overridable by the editor
         */
        'text' => function (?string $text = null) {
            return $text ?? I18n::translate('nerdcel.responsive-images.aihint.default-text');
        },

        /**
         * Default position of the hint, configurable per blueprint
         */
        'position' => function (string $position = 'bottom-right') {
            return $position;
        },

        /**
         * Default font color of the hint, configurable per blueprint
         */
        'color' => function (string $color = '#ffffff') {
            return $color;
        },

        /**
         * Default text size of the hint, as a percentage of the rendered
         * image height (e.g. 5 = 5% of the image height), configurable
         * per blueprint.
         *
         * Accepts the legacy `small`/`medium`/`large` string enum (used
         * before this became a numeric percentage) for backwards
         * compatibility with existing blueprints.
         */
        'size' => function ($size = 5) {
            return \Nerdcel\ResponsiveImages\ResponsiveImages::normalizeSize($size);
        },

        /**
         * Default opacity of the hint text (0-100), configurable per blueprint
         */
        'opacity' => function (int $opacity = 100) {
            return max(0, min(100, $opacity));
        },

        /**
         * Default distance between the hint's background box and the
         * edge of the image (in em, relative to the hint's own font
         * size), configurable per blueprint
         */
        'margin' => function ($margin = 0.6) {
            return \Nerdcel\ResponsiveImages\ResponsiveImages::normalizeMargin($margin);
        },

        /**
         * Default padding between the hint text and the edge of its own
         * backdrop box (in em), configurable per blueprint
         */
        'padding' => function ($padding = 0.5) {
            return \Nerdcel\ResponsiveImages\ResponsiveImages::normalizePadding($padding);
        },

        /** @this Kirby\Cms\Field */
        'fileType' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            return $this->model()->type();
        },

        /**
         * Real image URL and dimensions, so the panel can render a live
         * preview of the hint overlay directly on the actual image.
         *
         * @this Kirby\Cms\Field
         */
        'imageUrl' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            $file = $this->model();

            return $file instanceof \Kirby\Cms\File ? $file->url() : null;
        },

        /** @this Kirby\Cms\Field */
        'imageWidth' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            $file = $this->model();

            return $file instanceof \Kirby\Cms\File ? $file->dimensions()->width() : null;
        },

        /** @this Kirby\Cms\Field */
        'imageHeight' => function () {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            $file = $this->model();

            return $file instanceof \Kirby\Cms\File ? $file->dimensions()->height() : null;
        },
    ],

    /**
     * Decode the raw stored value (native YAML, like Kirby's own structure/
     * object fields) into a predictable structure for the panel. Runs after
     * props are applied, reading the still-raw stored value from
     * `$this->value` and replacing it with the decoded array.
     * IMPORTANT: because of this, `$this->value` is a structured array from
     * here on – the "save" option below turns it back into storable data.
     */
    'computed' => [
        /** @this Kirby\Cms\Field */
        'value' => function () use ($normalize) {
            // @phpstan-ignore-next-line Kirby bindet $this zur Laufzeit auf das Field-Objekt.
            $decoded = Data::decode($this->value ?: [], 'yaml', false);

            return $normalize($decoded);
        },
    ],

    'methods' => [
        /**
         * Fallback value used when the field has never been touched
         */
        'emptyValue' => function () use ($normalize) {
            return $normalize([]);
        },
    ],

    /**
     * Re-encode the (already decoded) structured value back into a plain
     * array. Kirby's content writer automatically stores arrays as native
     * YAML in the content file (exactly like structure/object fields do),
     * so no manual JSON/YAML encoding is needed here.
     */
    'save' => function ($value) use ($normalize) {
        if (is_string($value)) {
            $value = Data::decode($value ?: [], 'yaml', false);
        }

        if (! is_array($value)) {
            $value = [];
        }

        return $normalize($value);
    },
];
