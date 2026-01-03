<?php
declare(strict_types=1);

namespace ADWS\Utils\Service;

use ADWS\Utils\Utility\Folder;
use ADWS\Utils\Utility\Path;
use Cake\Utility\Text;
use Laminas\Diactoros\UploadedFile;
use RuntimeException;

/**
 * FileUploadService
 */
class FileUploadService
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
        'application/pdf' => 'pdf',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->Folder = new Folder();
        $this->Path = new Path();
    }

    /**
     * Upload single file
     *
     * @param \Laminas\Diactoros\UploadedFile $file Uploadnutý soubor
     * @param int $id ID entity
     * @param string $type Typ souboru (např. "article", "product")
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

        $folder = $this->Folder->folder($id);
        $targetDir = "img/{$type}/{$folder}/files";
        $this->Folder->create($targetDir);

        $this->assertValidUpload($file);
        $this->assertValidMimeType($file);

        $baseName = Text::slug(
            pathinfo((string)$file->getClientFilename(), PATHINFO_FILENAME),
        );

        if ($baseName === '') {
            throw new RuntimeException('Invalid filename');
        }

        $ext = $this->detectExtension($file);

        $filename = strtolower($baseName . '.' . $ext);
        $targetPath = $this->Path->convert("{$targetDir}/{$filename}");

        $file->moveTo($targetPath);

        return $filename;
    }

    /**
     * Upload multiple files
     *
     * @param array<\Laminas\Diactoros\UploadedFile> $files
     * @param int $id
     * @param string $type
     * @return array<string> Array of filenames
     */
    public function uploadMultiple(array $files, int $id, string $type): array
    {
        $folder = $this->Folder->folder($id);
        $targetDir = "img/{$type}/{$folder}/files";
        $this->Folder->create($targetDir);

        $filenames = [];
        foreach ($files as $file) {
            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $this->assertValidUpload($file);
            $this->assertValidMimeType($file);

            $baseName = Text::slug(
                pathinfo((string)$file->getClientFilename(), PATHINFO_FILENAME),
            );

            if ($baseName === '') {
                throw new RuntimeException('Invalid filename');
            }

            $ext = $this->detectExtension($file);

            $filename = strtolower($baseName . '.' . $ext);
            $targetPath = $this->Path->convert("{$targetDir}/{$filename}");

            $file->moveTo($targetPath);

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
            throw new RuntimeException("Unsupported file type: {$mime}");
        }

        return self::MIME_MAP[$mime];
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
            throw new RuntimeException("Upload failed with error code: {$file->getError()}");
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
            throw new RuntimeException("Unsupported image MIME type: {$mime}");
        }
    }
}
