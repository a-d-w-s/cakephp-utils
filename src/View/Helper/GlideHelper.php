<?php
declare(strict_types=1);

namespace ADWS\Utils\View\Helper;

use Cake\Core\Configure;
use Cake\Utility\Security;
use Cake\View\Helper;
use League\Glide\Urls\UrlBuilder;
use League\Glide\Urls\UrlBuilderFactory;

/**
 * GlideHelper
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class GlideHelper extends Helper
{
    /**
     * Helpers used by this helper.
     *
     * @var array<string>
     */
    protected array $helpers = ['Html'];

    /**
     * Default config for this helper.
     *
     * Valid keys:
     * - `baseUrl`: Base URL. Default '/images/'.
     * - `secureUrls`: Whether to generate secure URLs. Default `false`.
     * - `signKey`: Signing key to use when generating secure URLs. If empty
     *   value of `Security::salt()` will be used. Default `null`.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'baseUrl' => '/images/',
        'secureUrls' => false,
        'signKey' => null,
    ];

    /**
     * URL builder.
     *
     * @var \League\Glide\Urls\UrlBuilder|null
     */
    protected ?UrlBuilder $_urlBuilder = null;

    /**
     * Initialization hook method.
     *
     * @param array<string, mixed> $config Helper configuration
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $config = array_merge($this->_defaultConfig, [
            'baseUrl' => Configure::read('ADWS.Utils.Glide.server.base_url'),
            'secureUrls' => Configure::read('ADWS.Utils.Glide.security.secureUrls'),
            'signKey' => Configure::read('ADWS.Utils.Glide.security.signKey'),
        ], $config);

        $this->setConfig($config);
    }

    /**
     * Creates a formatted IMG element.
     *
     * @param string $path Image path.
     * @param array<string, mixed> $params Image manipulation parameters.
     * @param array<string, mixed> $options Array of HTML attributes for image tag.
     * @return string Complete <img> tag.
     * @see http://glide.thephpleague.com/1.0/api/quick-reference/
     */
    public function image(string $path, array $params = [], array $options = []): string
    {
        return $this->Html->image(
            $this->url($path, $params + ['_base' => false]),
            $options,
        );
    }

    /**
     * URL with query string based on resizing params.
     *
     * @param string $path Image path.
     * @param array<string, mixed> $params Image manipulation parameters.
     *        Special key `_base` (bool) can be used to prepend webroot.
     * @return string Image URL.
     * @see http://glide.thephpleague.com/1.0/api/quick-reference/
     */
    public function url(string $path, array $params = []): string
    {
        $base = true;
        if (isset($params['_base'])) {
            $base = (bool)$params['_base'];
            unset($params['_base']);
        }
        $url = $this->urlBuilder()->getUrl($path, $params);
        if ($base && !str_starts_with($url, 'http')) {
            $url = $this->getView()->getRequest()->getAttribute('webroot') . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * Get URL builder instance.
     *
     * @param \League\Glide\Urls\UrlBuilder|null $urlBuilder URL builder instance to
     *   set or null to get instance.
     * @return \League\Glide\Urls\UrlBuilder URL builder instance.
     */
    public function urlBuilder(?UrlBuilder $urlBuilder = null): UrlBuilder
    {
        if ($urlBuilder !== null) {
            return $this->_urlBuilder = $urlBuilder;
        }

        if (!isset($this->_urlBuilder)) {
            $config = $this->getConfig();

            $this->_urlBuilder = UrlBuilderFactory::create(
                $config['baseUrl'],
                $config['secureUrls'] ? ($config['signKey'] ?: Security::getSalt()) : null,
            );
        }

        return $this->_urlBuilder;
    }
}
