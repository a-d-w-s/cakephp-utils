<?php

return [
    'ADWS' => [
        'Utils' => [
            'Glide' => [
                'route' => '/images',

                'server' => [
                    'source' => null,
                    'watermarks' => null,
                    'cache' => null,
                    'driver' => 'imagick',
                    'base_url' => '/images/',
                    'no_image' => null,
                    'mask_image' => null,
                ],

                'security' => [
                    'secureUrls' => false,
                    'signKey' => null,
                ],

                'cacheTime' => '+1 days',
                'headers' => [],
                'allowedParams' => ['p'],
            ],
            'Image' => [
                'base_url' => 'img',
                'format' => 'webp',
                'formats' => [
                    'webp' => ['mimeType' => 'image/webp', 'quality' => 90],
                    'jpg' => ['mimeType' => 'image/jpeg', 'quality' => 85],
                ],
                'size' => [
                    'maxWidth' => 2500,
                    'maxHeight' => 2500,
                ],
            ],
            'File' => [
                'formats' => [
                    'pdf' => ['mimeType' => 'application/pdf'],
                ],
            ],
        ],
    ],
];
