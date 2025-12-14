<?php
declare(strict_types=1);

namespace ADWS\Utils\Test\TestCase\Service;

use ADWS\Utils\Service\ImageService;
use ADWS\Utils\Utility\Folder;
use Cake\TestSuite\TestCase;

class ImageServiceTest extends TestCase
{
    protected ImageService $ImageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ImageService = new ImageService([
            'format' => 'jpg', // přepis defaultního webp
        ]);
    }

    public function testGetImagesReturnsArray(): void
    {
        $id = 123;
        $type = 'teasers';
        $config = [
            'baseUrl' => '/img',
            'format' => 'jpg',
        ];

        $result = $this->ImageService->getImages($id, $type, $config);

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('main', $result);
        $this->assertArrayHasKey('gallery', $result);

        $this->assertArrayHasKey('file', $result['main']);
        $this->assertArrayHasKey('time', $result['main']);

        foreach ($result['gallery'] as $item) {
            $this->assertArrayHasKey('file', $item);
            $this->assertArrayHasKey('time', $item);
        }
    }

    public function testGetImagesMainFallback(): void
    {
        $id = 999; // ID, kde neexistují soubory
        $type = 'teasers';
        $config = [
            'baseUrl' => '/img',
            'format' => 'jpg',
        ];

        $result = $this->ImageService->getImages($id, $type, $config);

        $folder = (new Folder())->folder($id);

        $this->assertSame("{$folder}-main-original.jpg", $result['main']['file']);
        $this->assertNull($result['main']['time']);

        $this->assertSame([], $result['gallery']);
    }

    public function testGetImagesWithGallery(): void
    {
        $id = 123;
        $type = 'teasers';
        $config = [
            'baseUrl' => '/img',
            'format' => 'jpg',
        ];
        $options = ['gallery' => true];

        $result = $this->ImageService->getImages($id, $type, $config, $options);

        $this->assertIsArray($result['gallery']);

        foreach ($result['gallery'] as $item) {
            $this->assertArrayHasKey('file', $item);
            $this->assertArrayHasKey('time', $item);
            $this->assertIsString($item['file']);
            $this->assertTrue(is_int($item['time']) || $item['time'] === null);
        }
    }
}
