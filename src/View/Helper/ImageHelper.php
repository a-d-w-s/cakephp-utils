<?php
declare(strict_types=1);

namespace ADWS\Utils\View\Helper;

use ADWS\Utils\Service\ImageService;
use Cake\View\Helper;

/**
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \ADWS\Utils\View\Helper\GlideHelper $Glide
 */
class ImageHelper extends Helper
{
    /**
     * Helpers used by this helper.
     *
     * @var array<string>
     */
    protected array $helpers = ['Html', 'Glide'];

    /**
     * @template T of array{
     *     size?: string,
     *     sizeFull?: string,
     *     preset?: string,
     *     watermark?: bool,
     *     gallery?: bool,
     *     alt?: string,
     *     class?: string,
     *     link?: bool,
     *     linkClass?: string,
     *     deleteLink?: array{
     *         url: array,
     *         title?: string,
     *         attributes?: array<string, mixed>
     *     }
     * }
     * @param T $options
     * @param int $id Entity ID
     * @param string $type Image type / folder name (e.g. "teasers")
     * @return string HTML output with images
     */
    public function get(int $id, string $type, array $options = []): string
    {
        $service = new ImageService();

        $data = $service->getImages(
            $id,
            $type,
            $options,
        );

        // Rozměry pro resized images
        [$width, $height] = explode('x', $options['size'] ?? '') + [null, null];
        $w = (int)$width;
        $h = (int)$height;
        $p = $options['preset'] ?? null;

        $images = [
            'main' => $this->Glide->url(
                "/{$data['path']}/{$data['main']['file']}",
                array_filter([
                    'w' => $w,
                    'h' => $h,
                    'p' => $p,
                    'v' => $data['main']['time'] ?? null,
                ]),
            ),
            'gallery' => [],
        ];

        foreach ($data['gallery'] as $file) {
            $images['gallery'][] = $this->Glide->url(
                "/{$data['path']}/{$file['file']}",
                array_filter([
                    'w' => $w,
                    'h' => $h,
                    'p' => $p,
                    'v' => $file['time'] ?? null,
                ]),
            );
        }

        // Rozměry pro full images
        [$width, $height] = explode('x', $options['sizeFull'] ?? '1600x1600') + [null, null];
        $w = (int)$width;
        $h = (int)$height;
        $p = isset($options['watermark']) && $options['watermark'] ? 'mark' : null;

        $imagesFull = [
            'main' => $this->Glide->url(
                "/{$data['path']}/{$data['main']['file']}",
                compact('w', 'h', 'p') + ['v' => $data['main']['time'] ?? null],
            ),
            'gallery' => [],
        ];

        foreach ($data['gallery'] as $file) {
            $v = $file['time'] ?? null;
            $imagesFull['gallery'][] = $this->Glide->url(
                "/{$data['path']}/{$file['file']}",
                compact('w', 'h', 'p', 'v'),
            );
        }

        // Vytvoření HTML
        $html = '';

        $renderImage = function ($imgUrl, $fullUrl, $fileName) use ($options) {
            $imgTag = $this->Html->image($imgUrl, [
                'escape' => false,
                'class' => $options['class'] ?? null,
                'alt' => $options['alt'] ?? null,
            ]);

            if (!empty($options['link'])) {
                $imgTag = $this->Html->link($imgTag, $fullUrl, [
                    'escape' => false,
                    'class' => $options['linkClass'] ?? null,
                ]);
            }

            // Pokud je nastaven deleteLink, přidáme ho vedle obrázku
            if (!empty($options['deleteLink'])) {
                $deleteUrl = $options['deleteLink']['url'];
                $deleteUrl[] = $fileName; // aktuální název souboru
                $imgTag .= $this->Html->link(
                    $options['deleteLink']['title'] ?? 'X',
                    $deleteUrl,
                    $options['deleteLink']['attributes'] ?? [],
                );
            }

            // Obalíme do divu
            return '<div class="relative">' . $imgTag . '</div>';
        };

        if (!empty($images['gallery']) && isset($options['gallery']) && $options['gallery']) {
            foreach ($data['gallery'] as $i => $file) {
                $imgUrl = $images['gallery'][$i];
                $fullUrl = $imagesFull['gallery'][$i] ?? $imagesFull['main'];
                $fileName = $file['file'];
                $html .= $renderImage($imgUrl, $fullUrl, $fileName);
            }
        } elseif (!isset($options['gallery'])) {
            $fileName = $data['main']['file'];
            $html .= $renderImage($images['main'], $imagesFull['main'], $fileName);
        }

        return $html;
    }
}
