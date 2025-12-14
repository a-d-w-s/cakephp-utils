<?php
declare(strict_types=1);

namespace ADWS\Utils\View\Helper;

use ADWS\Utils\Service\FileService;
use Cake\View\Helper;

/**
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class FileHelper extends Helper
{
    /**
     * Helpers used by this helper.
     *
     * @var array<string>
     */
    protected array $helpers = ['Html'];

    /**
     * Generates HTML output for images associated with an entity.
     *
     * @template T of array{
     *     linkClass?: string,
     *     class?: string,
     *     deleteLink?: array{
     *         title?: string,
     *         url: array,
     *         attributes?: array
     *     }
     * }
     * @param int $id Entity ID
     * @param string $type Type of entity, e.g. 'goods' or 'articles'
     * @param T $options Optional settings
     * @return string HTML markup with images and optional delete links
     */
    public function get(int $id, string $type, array $options = []): string
    {
        $service = new FileService();

        $data = $service->getFiles(
            $id,
            $type,
        );

        $html = '';
        foreach ($data['main'] as $file) {
            $path = '/img/' . $data['path'] . '/';

            $linkTag = $this->Html->link($file['file'], $path . $file['file'], [
                'escape' => false,
                'class' => $options['linkClass'] ?? null,
            ]);

            // Pokud je nastaven deleteLink, přidáme ho vedle
            if (!empty($options['deleteLink'])) {
                $deleteUrl = $options['deleteLink']['url'];
                $deleteUrl[] = $file['file']; // aktuální název souboru
                $linkTag .= $this->Html->link(
                    $options['deleteLink']['title'] ?? 'X',
                    $deleteUrl,
                    $options['deleteLink']['attributes'] ?? [],
                );
            }

            $html .= $this->Html->tag('div', $linkTag, ['class' => $options['class'] ?? null]);
        }

        return $html;
    }
}
