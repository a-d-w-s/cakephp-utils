<?php
declare(strict_types=1);

namespace ADWS\Utils\Utility;

class File
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
     * Check if a file exists.
     */
    public function exist(string $path): bool
    {
        return is_file($this->Path->convert($path));
    }

    /**
     * Delete a file if it exists.
     *
     * @return bool True if the file was deleted, false if it didn't exist or deletion failed
     */
    public function delete(string $path): bool
    {
        $file = $this->Path->convert($path);

        if (!is_file($file)) {
            return false;
        }

        return unlink($file);
    }

    /**
     * Delete files in a folder with a specific prefix.
     *
     * @return bool True if directory existed, false if it didn't exist or deletion failed
     */
    public function deleteWithPrefix(string $path, string $prefix): bool
    {
        $dir = $this->Path->convert($path);

        if (!is_dir($dir)) {
            return false;
        }

        $success = true;

        foreach (scandir($dir) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (str_starts_with($file, $prefix)) {
                $filepath = $dir . DS . $file;
                if (is_file($filepath) && !unlink($filepath)) {
                    $success = false;
                }
            }
        }

        return $success;
    }
}
