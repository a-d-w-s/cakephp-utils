<?php
declare(strict_types=1);

namespace ADWS\Utils\Test\TestCase\View\Helper;

use ADWS\Utils\View\Helper\GlideHelper;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use Cake\View\View;

class GlideHelperTest extends TestCase
{
    protected ServerRequest $request;
    protected View $view;
    protected GlideHelper $helper;

    public function setUp(): void
    {
        $this->request = new ServerRequest();
        $this->request = $this->request->withAttribute('webroot', '/');
        $this->view = new View($this->request);
        $this->helper = new GlideHelper($this->view, ['baseUrl' => '/images/']);

        Security::setSalt('salt');
    }

    public function testUrl(): void
    {
        $result = $this->helper->url('sample.jpg', ['w' => 100]);
        $this->assertEquals('/images/sample.jpg?w=100', $result);

        $this->helper->getView()->setRequest(
            $this->helper->getView()->getRequest()->withAttribute('webroot', '/subfolder/'),
        );
        $result = $this->helper->url('sample.jpg', ['w' => 100]);
        $this->assertEquals('/subfolder/images/sample.jpg?w=100', $result);

        $helper = new GlideHelper($this->view, [
            'baseUrl' => '/images/',
            'secureUrls' => true,
        ]);
        $result = $helper->url('sample.jpg', ['w' => 100]);
        $this->assertStringContainsString('&s=', $result);
    }

    public function testImage(): void
    {
        $result = $this->helper->image('sample.jpg', ['w' => 100], ['width' => 100]);
        $this->assertHtml([
            'img' => [
                'src' => '/images/sample.jpg?w=100',
                'width' => 100,
                'alt' => '',
            ],
        ], $result);

        Router::setRequest(
            $this->helper->getView()->getRequest()
                ->withAttribute('webroot', '/subfolder/'),
        );
        $result = $this->helper->image('sample.jpg', ['w' => 100], ['width' => 100]);
        $this->assertHtml([
            'img' => [
                'src' => '/subfolder/images/sample.jpg?w=100',
                'width' => 100,
                'alt' => '',
            ],
        ], $result);
    }
}
