<?php
declare(strict_types=1);

namespace ADWS\Utils\Service;

use ADWS\Utils\Utility\Folder;
use ADWS\Utils\Utility\Path;
use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;

class ImageService
{
    use InstanceConfigTrait;

    /**
     * @var \ADWS\Utils\Utility\Folder
     */
    protected Folder $Folder;

    /**
     * @var \ADWS\Utils\Utility\Path
     */
    protected Path $Path;

    /**
     * Default config.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'format' => 'webp',
    ];

    /**
     * Constructor.
     *
     * @param array<string,mixed> $config Array of config.
     */
    public function __construct(array $config = [])
    {
        $this->Folder = new Folder();
        $this->Path = new Path();

        $config = array_merge($this->_defaultConfig, Configure::read('ADWS.Utils.Image'), $config);

        $this->setConfig($config);
    }

    /**
     * Vrátí data o obrázcích
     *
     * @param int $id ID entity
     * @param string $type Typ obrázků (např. "teasers")
     * @param array{
     *     gallery?: bool
     * } $options Volitelné parametry
     * @return array{
     *     path: string,
     *     main: array{file: string, time: int|null},
     *     gallery: list<array{file: string, time: int|null}>
     * }
     */
    public function getImages(
        int $id,
        string $type,
        array $options = [],
    ): array {
        $folder = $this->Folder->folder($id);

        $format = $this->getConfig('format');

        $path = "img/{$type}/{$folder}";

        /** MAIN */
        $mainFile = "{$folder}-main-original.{$format}";

        $mtime = file_exists("{$path}/{$mainFile}") ? filemtime("{$path}/{$mainFile}") : null;
        $main = [
            'file' => "{$mainFile}",
            'time' => $mtime !== false ? $mtime : null,
        ];

        /** GALLERY */
        $gallery = [];
        if (!empty($options['gallery'])) {
            $galleryPattern = $this->Path->convert("{$path}/{$folder}-gallery-*-original.{$format}");
            foreach (glob($galleryPattern) ?: [] as $file) {
                $mtime = file_exists("{$file}") ? filemtime("{$file}") : null;
                $gallery[] = [
                    'file' => basename($file),
                    'time' => $mtime !== false ? $mtime : null,
                ];
            }
        }

        return [
            'path' => "{$type}/{$folder}",
            'main' => $main,
            'gallery' => $gallery,
        ];
    }
}
