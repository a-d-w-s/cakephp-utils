<?php

declare(strict_types=1);

use ADWS\Utils\Middleware\GlideMiddleware;
use Cake\Core\Configure;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes): void {
    $config = Configure::read('ADWS.Utils.Glide') ?? [];

    $routes->plugin(
        'ADWS/Utils',
        ['path' => $config['route'] ?? '/images'],
        function (RouteBuilder $routes) use ($config): void {
            $routes->registerMiddleware('glide', new GlideMiddleware([
                'path' => null,
                'server' => [
                    'source' => $config['server']['source'] ?? WWW_ROOT . 'img',
                    'watermarks' => $config['server']['watermarks'] ?? WWW_ROOT . 'img',
                    'cache' => $config['server']['cache'] ?? WWW_ROOT . 'cache',
                    'driver' => $config['server']['driver'] ?? 'gd',
                    'base_url' => $config['server']['base_url'] ?? '/images/',
                    'response' => null,
                    'presets' => [
                        'crop' => [
                            'fit' => 'crop',
                        ],
                        'contain' => [
                            'fit' => 'contain',
                        ],
                        'cover' => [
                            'fit' => 'cover',
                        ],
                        'mark' => [
                            'mark' => $config['server']['watermark_image'] ?? null,
                            'markalpha' => '70',
                            'markpad' => '2w',
                            'markw' => '20w',
                            'markfit' => 'markfit',
                            'markpos' => 'bottom-right',
                        ],
                    ],
                    'defaults' => [
                        'fm' => 'webp',
                        'q' => 70,
                    ],
                ],
                'security' => [
                    'secureUrls' => $config['security']['secureUrls'] ?? false,
                    'signKey' => $config['security']['signKey'] ?? 'default-key',
                ],
                'cacheTime' => $config['cacheTime'] ?? '+30 days',
                'headers' => $config['headers'] ?? [],
                'allowedParams' => $config['allowedParams'] ?? ['v', 'p', 'w', 'h'],
            ]));

            $routes->applyMiddleware('glide');

            $routes->connect('/*');
        },
    );
};
