<?php
declare(strict_types=1);

namespace ADWS\Utils;

use ADWS\Utils\Middleware\GlideMiddleware;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\Http\MiddlewareQueue;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use League\Glide\Responses\PsrResponseFactory;
use League\Glide\ServerFactory;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Plugin for Utils
 */
class UtilsPlugin extends BasePlugin
{
    /**
     * Do bootstrapping or not
     *
     * @var bool
     */
    protected bool $bootstrapEnabled = true;

    /**
     * Console middleware
     *
     * @var bool
     */
    protected bool $consoleEnabled = false;

    /**
     * Load routes or not
     *
     * @var bool
     */
    protected bool $routesEnabled = true;

    /**
     * ADWS the Glide middleware to the middleware queue and sets a fallback
     * image in case Glide cannot generate the requested image.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue with Glide middleware
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $config = Configure::read('ADWS.Utils.Glide');

        EventManager::instance()->on(
            GlideMiddleware::RESPONSE_FAILURE_EVENT,
            function ($event, ServerRequestInterface $request) use ($config) {
                $query = $request->getQueryParams();
                unset($query['s']);

                if (isset($query['p'])) {
                    $query['fit'] = $query['p'];
                    unset($query['p']);
                }

                $fallbackPath = $config['server']['no_image'];

                $serverConfig = $config['server'];
                $serverConfig['secureUrls'] = false;
                $serverConfig['response'] = new PsrResponseFactory(
                    new Response(),
                    fn($stream = 'php://temp', $mode = 'r+') => new Stream($stream, $mode),
                );

                $server = ServerFactory::create($serverConfig);

                return $server->getImageResponse($fallbackPath, $query);
            },
        );

        return $middlewareQueue;
    }
}
