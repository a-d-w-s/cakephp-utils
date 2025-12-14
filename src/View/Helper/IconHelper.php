<?php
declare(strict_types=1);

namespace ADWS\Utils\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;

/**
 * IconHelper
 *
 * Pomocník pro generování SVG ikon ze sprite souboru.
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class IconHelper extends Helper
{
    /**
     * Default config.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'baseUrl' => 'img-sprite',
    ];

    /**
     * Initialization hook method.
     *
     * @param array<string, mixed> $config Helper configuration
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setConfig([
            'baseUrl' => Configure::read('ADWS.Utils.Icon.base_url') ?? $this->_defaultConfig['baseUrl'],
        ]);
    }

    /**
     * Vygeneruje SVG ikonu z externího sprite souboru.
     *
     * @param string      $icon   Název ikony (např. 'application')
     * @param int         $width  Šířka SVG (default: 40)
     * @param int         $height Výška SVG (default: 40)
     * @param string|null $class  Volitelná CSS třída
     * @return string HTML kód SVG ikony
     */
    public function get(string $icon, int $width = 40, int $height = 40, ?string $class = null): string
    {
        $version = (string)(Configure::read('App.version') ?? time());

        $baseUrl = rtrim($this->getConfig('baseUrl'), '/');
        $classAttr = $class ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';

        return sprintf(
            '<svg width="%d" height="%d"%s><use xlink:href="/%s/sprite-icons-web.svg?v=%s#%s"></use></svg>',
            $width,
            $height,
            $classAttr,
            $baseUrl,
            $version,
            htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'),
        );
    }
}
