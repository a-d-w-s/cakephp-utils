<?php
declare(strict_types=1);

namespace ADWS\Utils\Utility;

use Exception;
use GdImage;
use InvalidArgumentException;
use RuntimeException;

/**
 * SimpleImage utility class for handling images with GD.
 *
 * Supports auto-orientation based on EXIF, proportional resizing,
 * flipping, rotation, and saving images.
 */
class Image
{
    /**
     * @var \GdImage GD image resource
     */
    protected GdImage $image;

    /**
     * @var string Cesta k souboru obrázku
     */
    protected string $filePath;

    /**
     * @var int Šířka obrázku v pixelech
     */
    protected int $width;

    /**
     * @var int Výška obrázku v pixelech
     */
    protected int $height;

    /**
     * Konstruktor
     *
     * Načte obrázek, získá rozměry a vytvoří GD resource.
     * Pokud soubor neexistuje nebo je typ nepodporovaný, vyhodí výjimku.
     *
     * @param string $filePath
     * @throws \InvalidArgumentException Pokud soubor neexistuje
     * @throws \RuntimeException Pokud typ obrázku není podporován nebo GD selže
     */
    public function __construct(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: $filePath");
        }

        $this->filePath = $filePath;
        $info = getimagesize($filePath);
        if ($info === false) {
            throw new RuntimeException("Cannot read image info: $filePath");
        }
        $this->width = $info[0];
        $this->height = $info[1];

        $this->image = $this->createImageFromFile($filePath, $info[2]);
    }

    /**
     * Načte GD resource z konkrétního typu souboru
     *
     * @param string $filePath
     * @param int $type Typ obrázku (IMAGETYPE_*)
     * @return \GdImage
     * @throws \RuntimeException Pokud GD selže při načítání
     */
    protected function createImageFromFile(string $filePath, int $type): GdImage
    {
        set_error_handler(function ($errno, $errstr): void {
            throw new RuntimeException("GD error: $errstr", $errno);
        });

        try {
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $img = imagecreatefromjpeg($filePath);
                    break;
                case IMAGETYPE_PNG:
                    $img = imagecreatefrompng($filePath);
                    break;
                case IMAGETYPE_GIF:
                    $img = imagecreatefromgif($filePath);
                    break;
                case IMAGETYPE_WEBP:
                    $img = imagecreatefromwebp($filePath);
                    break;
                default:
                    throw new RuntimeException('Unsupported image type');
            }

            if (!$img) {
                throw new RuntimeException("Failed to create GD image from file: $filePath");
            }

            return $img;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Vrátí šířku obrázku
     *
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Vrátí výšku obrázku
     *
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Vrátí poměr stran (width / height)
     *
     * @return float
     */
    public function getAspectRatio(): float
    {
        return $this->width / $this->height;
    }

    /**
     * Určí orientaci obrázku ('landscape' / 'portrait')
     *
     * @return string
     */
    public function getOrientation(): string
    {
        return $this->width >= $this->height ? 'landscape' : 'portrait';
    }

    /**
     * Načte EXIF metadata z JPEG obrázku
     *
     * @return array<string, mixed>|null
     */
    public function getExif(): ?array
    {
        if (!function_exists('exif_read_data') || exif_imagetype($this->filePath) !== IMAGETYPE_JPEG) {
            return null;
        }

        try {
            $data = exif_read_data($this->filePath);

            return $data !== false ? $data : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Automaticky otočí obrázek podle EXIF Orientation tagu
     *
     * @return static
     */
    public function autoOrient(): static
    {
        $exif = $this->getExif();
        if (!$exif || !isset($exif['Orientation'])) {
            return $this;
        }

        switch ($exif['Orientation']) {
            case 2:
                $this->flip('x');
                break;
            case 3:
                $this->rotate(180);
                break;
            case 4:
                $this->flip('y');
                break;
            case 5:
                $this->flip('y')->rotate(90);
                break;
            case 6:
                $this->rotate(90);
                break;
            case 7:
                $this->flip('x')->rotate(90);
                break;
            case 8:
                $this->rotate(-90);
                break;
        }

        return $this;
    }

    /**
     * Flip obrázku horizontálně nebo vertikálně
     *
     * Používá `set_error_handler` aby GD chyby skonvertoval na RuntimeException.
     *
     * @param string $mode 'x' horizontálně, 'y' vertikálně
     * @return static
     */
    public function flip(string $mode): static
    {
        set_error_handler(fn($errno, $errstr) => throw new RuntimeException("GD error: $errstr", $errno));
        try {
            $width = max(1, $this->width);
            $height = max(1, $this->height);

            $tmp = imagecreatetruecolor($width, $height);
            if (!$tmp) {
                throw new RuntimeException('Failed to create temporary image for flipping');
            }

            if ($mode === 'x') {
                for ($y = 0; $y < $this->height; $y++) {
                    imagecopy($tmp, $this->image, 0, $y, $this->width - 1, $y, $this->width, 1);
                }
            } elseif ($mode === 'y') {
                for ($x = 0; $x < $this->width; $x++) {
                    imagecopy($tmp, $this->image, $x, 0, $x, $this->height - 1, 1, $this->height);
                }
            }

            $this->image = $tmp;
        } finally {
            restore_error_handler();
        }

        return $this;
    }

    /**
     * Otočení obrázku
     *
     * @param int $degrees Stupně, kladné = po směru hodinových ručiček
     * @return static
     */
    public function rotate(int $degrees): static
    {
        set_error_handler(fn($errno, $errstr) => throw new RuntimeException("GD error: $errstr", $errno));
        try {
            $rotated = imagerotate($this->image, $degrees, 0);
            if (!$rotated) {
                throw new RuntimeException('Failed to rotate image');
            }

            $this->image = $rotated;
            $this->width = imagesx($this->image);
            $this->height = imagesy($this->image);
        } finally {
            restore_error_handler();
        }

        return $this;
    }

    /**
     * Změna velikosti obrázku na pevné rozměry
     *
     * @param int $width
     * @param int $height
     * @return static
     */
    public function resize(int $width, int $height): static
    {
        set_error_handler(fn($errno, $errstr) => throw new RuntimeException("GD error: $errstr", $errno));
        try {
            $width = max(1, $width);
            $height = max(1, $height);

            $newImage = imagecreatetruecolor($width, $height);
            if (!$newImage) {
                throw new RuntimeException('Failed to create image for resize');
            }

            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);

            if (
                !imagecopyresampled(
                    $newImage,
                    $this->image,
                    0,
                    0,
                    0,
                    0,
                    $width,
                    $height,
                    $this->width,
                    $this->height,
                )
            ) {
                throw new RuntimeException('Failed to resample image');
            }

            $this->image = $newImage;
            $this->width = $width;
            $this->height = $height;
        } finally {
            restore_error_handler();
        }

        return $this;
    }

    /**
     * Proporcionální změna velikosti tak, aby se vešel do maxWidth/maxHeight
     *
     * @param int $maxWidth
     * @param int $maxHeight
     * @return static
     */
    public function bestFit(int $maxWidth, int $maxHeight): static
    {
        if ($this->width <= $maxWidth && $this->height <= $maxHeight) {
            return $this;
        }

        $ratio = $this->getAspectRatio();
        $width = $maxWidth;
        $height = (int)round($maxWidth / $ratio);
        if ($height > $maxHeight) {
            $height = $maxHeight;
            $width = (int)round($maxHeight * $ratio);
        }

        return $this->resize($width, $height);
    }

    /**
     * Uloží obrázek do souboru
     *
     * Podporuje jpg, jpeg, png, gif, webp.
     * Používá `set_error_handler` aby GD chyby skonvertoval na RuntimeException.
     *
     * @param string $path
     * @param int $quality
     * @throws \RuntimeException Pokud formát není podporován nebo GD selže
     */
    public function save(string $path, int $quality = 90): void
    {
        set_error_handler(fn($errno, $errstr) => throw new RuntimeException("GD error: $errstr", $errno));
        try {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $result = match ($ext) {
                'jpg', 'jpeg' => imagejpeg($this->image, $path, $quality),
                'png' => imagepng($this->image, $path, $quality),
                'gif' => imagegif($this->image, $path),
                'webp' => imagewebp($this->image, $path, $quality),
                default => throw new RuntimeException("Unsupported save format: $ext"),
            };
            if (!$result) {
                throw new RuntimeException("Failed to save image to $path");
            }
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Destruktor: uvolní GD resource
     */
    public function __destruct()
    {
        imagedestroy($this->image);
    }
}
