<?php
declare(strict_types=1);

namespace ADWS\Utils\Test\TestCase\View\Helper;

use ADWS\Utils\View\Helper\IconHelper;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * IconHelper Test Case
 */
class IconHelperTest extends TestCase
{
    /**
     * @var \ADWS\Utils\View\Helper\IconHelper
     */
    protected IconHelper $Icon;

    /**
     * Setup method
     */
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('App.version', '1.2.3');
        Configure::write('ADWS.Utils.Icon.base_url', 'img-sprite');

        $view = new View();
        $this->Icon = new IconHelper($view);
        $this->Icon->initialize([]);
    }

    /**
     * Test generování SVG ikony bez třídy
     */
    public function testGetWithoutClass(): void
    {
        $iconHtml = $this->Icon->get('application', 50, 60);

        $expected = '<svg width="50" height="60"><use xlink:href="/img-sprite/sprite-icons-web.svg?v=1.2.3#application"></use></svg>';

        $this->assertSame($expected, $iconHtml);
    }

    /**
     * Test generování SVG ikony s CSS třídou
     */
    public function testGetWithClass(): void
    {
        $iconHtml = $this->Icon->get('settings', 40, 40, 'my-icon');

        $expected = '<svg width="40" height="40" class="my-icon"><use xlink:href="/img-sprite/sprite-icons-web.svg?v=1.2.3#settings"></use></svg>';

        $this->assertSame($expected, $iconHtml);
    }

    /**
     * Test použití výchozí velikosti a verze fallback
     */
    public function testGetDefaultSizeAndVersion(): void
    {
        Configure::delete('App.version');

        $iconHtml = $this->Icon->get('user');

        $this->assertStringContainsString('<svg width="40" height="40"', $iconHtml);
        $this->assertStringContainsString('#user', $iconHtml);
        $this->assertStringContainsString('/img-sprite/sprite-icons-web.svg?v=', $iconHtml);
    }
}
