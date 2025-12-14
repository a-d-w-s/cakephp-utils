<?php
declare(strict_types=1);

// Find plugin root
use Cake\Core\Configure;

if (!defined('PLUGIN_ROOT')) {
    define('PLUGIN_ROOT', dirname(__DIR__) . DS);
}

// Path to test app inside plugin
if (!defined('TEST_APP')) {
    define('TEST_APP', PLUGIN_ROOT . 'tests' . DS . 'test_app' . DS);
}

// Webroot test app inside plugin
if (!defined('WWW_ROOT')) {
    define('WWW_ROOT', TEST_APP . 'webroot' . DS);
}

// Optional: DS constant
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

// Load CakePHP test bootstrap if needed
$cakeBootstrap = PLUGIN_ROOT . 'vendor/cakephp/cakephp/tests/bootstrap.php';
if (file_exists($cakeBootstrap)) {
    require $cakeBootstrap;
}

Configure::load('ADWS/Utils.app', 'default');
