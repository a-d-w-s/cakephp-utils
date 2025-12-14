<?php
declare(strict_types=1);

namespace ADWS\Utils\Test\TestCase;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

class UtilsPluginTest extends TestCase
{
    public function testPluginBootstrapLoadsConfig(): void
    {
        $config = Configure::read('ADWS.Utils.Glide');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('server', $config);
        $this->assertArrayHasKey('security', $config);
    }
}
