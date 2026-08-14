<?php

use Kirby\Data\Data;

return [
    'toBreakpointFocal' => function ($field) {
        return Data::decode($field->value ?: [], 'yml', false);
    },

    /**
     * Decode the aihint field value into a predictable structure
     */
    'toAiHint' => function ($field) {
        $decoded = Data::decode($field->value ?: [], 'yaml', false);

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
    },
];
