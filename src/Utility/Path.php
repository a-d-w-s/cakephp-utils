<?php
declare(strict_types=1);

namespace ADWS\Utils\Utility;

class Path
{
    /**
     * Convert method.
     *
     * @param string $path
     * @return string
     */
    public function convert(string $path): string
    {
        return WWW_ROOT . str_replace('/', DS, ltrim($path, '/'));
    }
}
