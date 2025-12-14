<?php
declare(strict_types=1);

namespace ADWS\Utils\Service;

use ADWS\Utils\Utility\Folder;
use ADWS\Utils\Utility\Image;
use ADWS\Utils\Utility\Path;
use Cake\Core\Configure;
use Laminas\Diactoros\UploadedFile;
use League\Glide\ServerFactory;
use RuntimeException;
use Throwable;

/**
 * ImageUploadService
 *
 * Service pro upload a základní zpracování obrázků.
 * - vytvoří cílový adresář
 * - zkontroluje upload
 * - upraví obrázek (orientace + resize)
 * - uloží jej na disk
 *
 * Použitelné v Controlleru, CLI i Jobu.
 */
class ImageUploadService
{
    /**
     * Folder utility
     *
     * @var \ADWS\Utils\Utility\Folder
     */
    protected Folder $Folder;

    /**
     * @var \ADWS\Utils\Utility\Path
     */
    protected Path $Path;

    /**
     * @var array<string, non-empty-string>
     */
    private const MIME_MAP = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->Folder = new Folder();
        $this->Path = new Path();
    }

    /**
     * Upload hlavního obrázku
     *
     * Vytvoří strukturu:
     *   /img/{type}/{folder}/{folder}-main.jpg
     *
     * @param \Laminas\Diactoros\UploadedFile $file Uploadnutý soubor
     * @param int $id ID entity
     * @param string $type Typ obrázku (např. "article", "product")
     * @return string|null Název uloženého souboru
     * @throws \RuntimeException Pokud upload nebo zpracování selže
     */
    public function upload(
        UploadedFile $file,
        int $id,
        string $type,
    ): ?string {
        if ($file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $this->assertValidUpload($file);
        $this->assertValidMimeType($file);

        $folder = $this->Folder->folder($id);
        $targetDir = "img/{$type}/{$folder}";

        $this->Folder->create($targetDir);

        $ext = $this->detectExtension($file);
        $filename = $folder . '-main-original.' . $ext;
        $targetPath = "{$targetDir}/{$filename}";

        $this->processImage(
            $file->getStream()->getMetadata('uri'),
            $targetPath,
        );

        return $filename;
    }

    /**
     * Upload multiple images
     *
     * @param array<\Laminas\Diactoros\UploadedFile> $files
     * @param int $id
     * @param string $type
     * @return array<string> Array of filenames
     */
    public function uploadMultiple(array $files, int $id, string $type): array
    {
        $folder = $this->Folder->folder($id);
        $targetDir = "img/{$type}/{$folder}";
        $this->Folder->create($targetDir);

        // zjistíme, kolik souborů už existuje
        $existingFiles = glob("{$targetDir}/{$folder}-gallery-*-original.*");
        $startIndex = 0;
        if (!empty($existingFiles)) {
            // vezmeme nejvyšší číslo
            $numbers = [];
            foreach ($existingFiles as $file) {
                if (preg_match('/-gallery-(\d+)-original\./', basename($file), $matches)) {
                    $numbers[] = (int)$matches[1];
                }
            }
            $startIndex = $numbers ? max($numbers) : 0;
        }

        $filenames = [];
        foreach ($files as $i => $file) {
            if ($file->getError() !== UPLOAD_ERR_OK) {
                continue;
            }

            $this->assertValidUpload($file);
            $this->assertValidMimeType($file);

            $ext = $this->detectExtension($file);
            $index = $startIndex + $i + 1; // pokračování číslování
            $filename = sprintf('%s-%s-%03d-original.%s', $folder, 'gallery', $index, $ext);
            $targetPath = "{$targetDir}/{$filename}";

            $this->processImage($file->getStream()->getMetadata('uri'), $targetPath);

            $filenames[] = $filename;
        }

        return $filenames;
    }

    /**
     * Detect file extension based on uploaded file MIME type.
     *
     * Uses client-reported MIME type and maps it to a safe extension.
     *
     * @param \Laminas\Diactoros\UploadedFile $file
     * @return non-empty-string
     */
    private function detectExtension(UploadedFile $file): string
    {
        $mime = (string)$file->getClientMediaType();

        if (!isset(self::MIME_MAP[$mime])) {
            throw new RuntimeException("Unsupported image type: {$mime}");
        }

        return self::MIME_MAP[$mime];
    }

    /**
     * Zpracuje obrázek pomocí Image utility
     *
     * @param string $source Cesta ke zdrojovému souboru
     * @param string $target Cílová cesta
     * @return void
     * @throws \RuntimeException Pokud Image utility selže
     */
    private function processImage(string $source, string $target): void
    {
        if (!file_exists($source)) {
            throw new RuntimeException("Source file does not exist: $source");
        }

        // Konfigurace z CakePHP Configure, fallback na default
        $config = Configure::read('ADWS.Utils.Image');
        $maxWidth = (int)($config['size']['maxWidth'] ?? 2000);
        $maxHeight = (int)($config['size']['maxHeight'] ?? 2000);
        $quality = (int)($config['quality'] ?? 90);

        try {
            $image = new Image($source);
            $image->autoOrient();

            // Pokud jsou definované formáty (např. webp, jpg), vytvoř více verzí
            $process = false;
            $destinationPath = $this->Path->convert($target);
            if (isset($config['formats']) && is_array($config['formats'])) {
                foreach ($config['formats'] as $ext => $formatOptions) {
                    $formatQuality = (int)($formatOptions['quality'] ?? $quality);
                    $baseName = pathinfo($destinationPath, PATHINFO_FILENAME);
                    $outputPath = dirname($destinationPath) . DS . $baseName . '.' . $ext;
                    $image->bestFit($maxWidth, $maxHeight); // opět fit pro každý formát
                    $image->save($outputPath, $formatQuality);
                    $process = true;
                }
            }

            // Glide cache
            if ($process) {
                $server = ServerFactory::create([
                    'source' => WWW_ROOT . 'img',
                    'cache' => WWW_ROOT . 'cache',
                ]);

                if (isset($config['formats']) && is_array($config['formats'])) {
                    foreach ($config['formats'] as $ext => $formatOptions) {
                        $baseName = pathinfo($target, PATHINFO_FILENAME);
                        $outputPath = dirname($target) . DS . $baseName . '.' . $ext;
                        $relativePath = preg_replace('#^img[/\\\\]#', '', $outputPath) ?? $outputPath;
                        $server->deleteCache($relativePath);
                    }
                }
            }
        } catch (Throwable $e) {
            throw new RuntimeException('Image processing failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Ověří, že upload proběhl korektně
     *
     * @param \Laminas\Diactoros\UploadedFile $file
     * @return void
     * @throws \RuntimeException
     */
    private function assertValidUpload(UploadedFile $file): void
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException(
                'Upload failed with error code: ' . $file->getError(),
            );
        }

        if ($file->getSize() === 0) {
            throw new RuntimeException('Uploaded file is empty');
        }
    }

    /**
     * Ověří MIME typ uploadnutého souboru
     *
     * @param \Laminas\Diactoros\UploadedFile $file
     * @return void
     * @throws \RuntimeException Pokud MIME typ není povolen
     */
    private function assertValidMimeType(UploadedFile $file): void
    {
        $mime = $file->getClientMediaType();

        $allowedMimeTypes = array_keys(self::MIME_MAP);

        if ($mime === null || !in_array($mime, $allowedMimeTypes, true)) {
            throw new RuntimeException(
                sprintf('Unsupported image MIME type: %s', (string)$mime),
            );
        }
    }
}
