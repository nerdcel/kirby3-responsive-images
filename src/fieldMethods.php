<?php

use Kirby\Data\Data;

return [
    'toBreakpointFocal' => function ($field) {
        return Data::decode($field->value, 'yml');
    },
];
