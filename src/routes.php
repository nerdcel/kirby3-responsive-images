<?php

use Kirby\Exception\InvalidArgumentException;
use Kirby\Http\Response;
use Kirby\Toolkit\V;
use Nerdcel\ResponsiveImages\ResponsiveImages;

$kirby = kirby();

return [
    [
        'pattern' => 'responsive-images',
        'action' => function () {
            try {
                $fields = kirby()->request()->body()->toArray();

                $json = json_encode($fields, JSON_THROW_ON_ERROR);

                (new ResponsiveImages(kirby()))->writeConfig($json);

                return Response::json($json, 200);
            } catch (\Exception $e) {
                return Response::json($e->getMessage(), 500);
            }
        },
        'method' => 'POST',
    ],
    [
        'pattern' => 'responsive-images/validate',
        'action' => function () {
            $fields = kirby()->request()->body()->toArray();

            if (V::required('name', $fields) === false) {
                throw new InvalidArgumentException('Name must be given');
            }

            return [];
        },
        'method' => 'POST',
    ],
    [
        'pattern' => 'responsive-images+breakpointoptions/validate',
        'action' => function () {
            $fields = kirby()->request()->body()->toArray();

            if (V::required('breakpoint', $fields) === false) {
                throw new InvalidArgumentException('Breakpoint must be given');
            }

            if (! V::accepted($fields['cropwidth']) && ! V::denied($fields['cropwidth'])) {
                throw new InvalidArgumentException('cropwidth must be given');
            }

            if (! V::accepted($fields['cropheight']) && ! V::denied($fields['cropheight'])) {
                throw new InvalidArgumentException('cropwidth must be given');
            }

            if (! V::accepted($fields['retina']) && ! V::denied($fields['retina'])) {
                throw new InvalidArgumentException('cropwidth must be given');
            }

            return [];
        },
        'method' => 'POST',
    ],
    [
        'pattern' => 'responsive-images-breakpoints/validate',
        'action' => function () {
            $fields = kirby()->request()->body()->toArray();

            if (V::required('name', $fields) === false) {
                throw new InvalidArgumentException('Name must be given');
            }

            if (V::required('width', $fields) === false) {
                throw new InvalidArgumentException('Width must be given');
            }

            if (V::required('mediaquery', $fields) === false) {
                throw new InvalidArgumentException('Mediaquery must be given');
            }

            return [];
        },
        'method' => 'POST',
    ],
];
