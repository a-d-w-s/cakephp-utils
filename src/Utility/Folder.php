<?php
declare(strict_types=1);

namespace ADWS\Utils\Utility;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Folder Utility
 */
class Folder
{
    /**
     * @var \ADWS\Utils\Utility\Path
     */
    protected Path $Path;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->Path = new Path();
    }

    /**
     * Generates a folder name from an ID, padded to 6 digits.
     *
     * @param int $id
     * @return string
     */
    public function folder(int $id): string
    {
        return str_pad((string)$id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Checks if a folder exists.
     *
     * @param string $path
     * @return bool
     */
    public function exist(string $path): bool
    {
        $path = $this->Path->convert($path);

        return is_dir($path);
    }

    /**
     * Checks whether a folder contains at least one file.
     *
     * @param string $path
     * @return bool
     */
    public function hasFiles(string $path): bool
    {
        $path = $this->Path->convert($path);

        if (!is_dir($path)) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $item) {
            if ($item instanceof SplFileInfo && $item->isFile()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Creates a folder if it doesn't exist.
     *
     * @param string $path
     * @param int $mode
     * @return bool
     */
    public function create(string $path, int $mode = 0775): bool
    {
        $path = $this->Path->convert($path);

        if (!is_dir($path)) {
            return mkdir($path, $mode, true);
        }

        return false;
    }

    /**
     * Deletes a folder and all its contents.
     *
     * @param string $path
     * @return bool True pokud byl adresář smazán nebo neexistoval, false pokud se něco nepodařilo
     */
    public function delete(string $path): bool
    {
        $path = $this->Path->convert($path);

        if (!is_dir($path)) {
            return true;
        }

        $success = true;

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($it as $file) {
            if ($file->isDir()) {
                if (!rmdir($file->getPathname())) {
                    $success = false;
                }
            } else {
                if (!unlink($file->getPathname())) {
                    $success = false;
                }
            }
        }

        if (!rmdir($path)) {
            $success = false;
        }

        return $success;
    }
}
