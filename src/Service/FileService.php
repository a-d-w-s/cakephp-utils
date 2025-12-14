<?php
declare(strict_types=1);

namespace ADWS\Utils\Service;

use ADWS\Utils\Utility\File;
use ADWS\Utils\Utility\Folder;
use ADWS\Utils\Utility\Path;
use Cake\Core\Configure;
use League\Glide\ServerFactory;

class FileService
{
    /**
     * Folder utility
     *
     * @var \ADWS\Utils\Utility\Folder
     */
    protected Folder $Folder;

    /**
     * File utility
     *
     * @var \ADWS\Utils\Utility\File
     */
    protected File $File;

    /**
     * @var \ADWS\Utils\Utility\Path
     */
    protected Path $Path;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->File = new File();
        $this->Path = new Path();
        $this->Folder = new Folder();
    }

    /**
     * Returns files for a given entity.
     *
     * @param int $id Entity ID
     * @param string $type Type of images (e.g., "teasers")
     * @return array{
     *     path: string,
     *     main: array<int, array{file: string, size: int}>
     * }
     */
    public function getFiles(int $id, string $type): array
    {
        $folder = $this->Folder->folder($id);
        $path = "img/{$type}/{$folder}/files";

        $files = [];

        if ($this->Folder->hasFiles($path)) {
            foreach (glob($this->Path->convert("{$path}/*.*")) ?: [] as $file) {
                $size = filesize($file);
                $files[] = [
                    'file' => basename($file),
                    'size' => $size === false ? 0 : $size, // přetypování false → 0
                ];
            }
        }

        return [
            'path' => "{$type}/{$folder}/files",
            'main' => $files,
        ];
    }

    /**
     * Delete a file or a whole folder for an entity
     *
     * @param string $type e.g. 'goods' or 'articles'
     * @param int $id entity ID
     * @param string|null $filename filename to delete, null = delete whole folder
     * @return bool
     * @throws \RuntimeException
     */
    public function delete(string $type, int $id, ?string $filename = null): bool
    {
        $config = Configure::read('ADWS.Utils.Image');
        $formats = array_keys($config['formats'] ?? ['webp']);

        $folder = $this->Folder->folder($id);
        $baseDir = "img/{$type}/{$folder}";
        $filesDir = "img/{$type}/{$folder}/files";

        $server = ServerFactory::create([
            'source' => WWW_ROOT . 'img',
            'cache' => WWW_ROOT . 'cache',
        ]);

        if ($filename !== null) {
            $deleted = false;

            // 1️⃣ pokus – mazání podle formats (img/)
            foreach ($formats as $ext) {
                $ext = '.' . ltrim((string)$ext, '.');
                $filePath = preg_replace('/\.\w+$/', $ext, "{$baseDir}/{$filename}");

                if ($filePath === null) {
                    continue;
                }

                if ($this->File->exist($filePath)) {
                    $this->File->delete($filePath);
                    $deleted = true;
                }
            }

            // 2️⃣ fallback – originál ve files/ (přípona je vždy)
            if ($deleted === false) {
                $filePath = "{$filesDir}/{$filename}";

                if ($this->File->exist($filePath)) {
                    $this->File->delete($filePath);
                    $deleted = true;
                }
            }

            if ($deleted && !$this->Folder->hasFiles($filesDir)) {
                $this->Folder->delete($filesDir);
            }

            // pokud jde o gallery soubor, přejmenujeme ostatní
            if ($deleted && str_contains($filename, '-gallery-')) {
                $this->renumberGalleryFiles($baseDir);
            }

            if ($deleted) {
                $server->deleteCache("{$type}/{$folder}");

                if (!$this->Folder->hasFiles($baseDir)) {
                    $this->Folder->delete($baseDir);
                }
            }

            return $deleted;
        }

        // mazání celé složky
        if (!$this->Folder->exist($baseDir)) {
            return false;
        }

        $server->deleteCache("{$type}/{$folder}");

        return $this->Folder->delete($baseDir);
    }

    /**
     * Přesune a přejmenuje všechny gallery soubory tak, aby pořadí zůstalo konzistentní
     *
     * @param string $baseDir Adresář s obrázky
     * @return void
     */
    protected function renumberGalleryFiles(string $baseDir): void
    {
        // najdeme všechny gallery soubory
        $allFiles = glob("$baseDir/*-gallery-*") ?: [];

        $groups = [];

        foreach ($allFiles as $path) {
            $name = basename($path);
            // match prefix-gallery-XXX a zbytek (velikost nebo -original)
            if (preg_match('/^(.*-gallery-)(\d{3})(-.*)?(\.\w+)$/', $name, $m)) {
                $key = $m[1] . $m[2]; // skupina podle čísla
                $groups[$key][] = $path;
            }
        }

        // seřadíme podle původního čísla
        ksort($groups, SORT_NATURAL);

        $tmpFiles = [];
        $counter = 1;

        // krok 1: všechny přejmenujeme na dočasné názvy
        foreach ($groups as $files) {
            foreach ($files as $oldPath) {
                $name = basename($oldPath);
                preg_match('/^(.*-gallery-)(\d{3})(-.*)?(\.\w+)$/', $name, $m);
                $prefix = $m[1];
                $sizePart = $m[3] ?? '';
                $ext = $m[4];

                $newName = $prefix . sprintf('%03d', $counter) . $sizePart . $ext;
                $tmpName = $newName . '__tmp';
                $tmpPath = $baseDir . '/' . $tmpName;

                rename($oldPath, $tmpPath);
                $tmpFiles[$tmpPath] = $baseDir . '/' . $newName;
            }
            $counter++;
        }

        // krok 2: dočasné názvy přejmenujeme na finální
        foreach ($tmpFiles as $tmpPath => $finalPath) {
            rename($tmpPath, $finalPath);
        }

        // na závěr smažeme složku, pokud je prázdná
        $files = glob($baseDir . '/*') ?: [];

        if (is_dir($baseDir) && count($files) === 0) {
            rmdir($baseDir);
        }
    }
}
