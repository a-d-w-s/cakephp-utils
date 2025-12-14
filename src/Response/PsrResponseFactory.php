<?php
declare(strict_types=1);

namespace ADWS\Utils\Response;

use ADWS\Utils\Exception\ResponseException;
use Cake\Http\Response;
use Laminas\Diactoros\Stream;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Glide\Responses\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class PsrResponseFactory implements ResponseFactoryInterface
{
    /**
     * Create response.
     *
     * @param \League\Flysystem\FilesystemOperator $cache Cache file system.
     * @param string $path Cached file path.
     * @return \Psr\Http\Message\ResponseInterface Response object.
     * @throws \League\Flysystem\FilesystemException
     */
    public function create(FilesystemOperator $cache, string $path): ResponseInterface
    {
        try {
            $resource = $cache->readStream($path);
        } catch (FilesystemException $e) {
            throw new ResponseException(null, null, $e);
        }

        $stream = new Stream($resource);

        $contentType = $cache->mimeType($path);
        $contentLength = $cache->fileSize($path);

        return (new Response())->withBody($stream)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Content-Length', (string)$contentLength);
    }
}
