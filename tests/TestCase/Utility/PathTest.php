<?php
declare(strict_types=1);

namespace ADWS\Utils\Test\TestCase\Utility;

use ADWS\Utils\Utility\Path;
use Cake\TestSuite\TestCase;

class PathTest extends TestCase
{
    /**
     * @var string
     */
    protected string $tempDir;

    /**
     * @var \ADWS\Utils\Utility\Path
     */
    protected Path $Path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Path = new Path();

        $this->tempDir = 'img';
    }

    public function testConvertPathReturnsFullPath(): void
    {
        $image = $this->Path->convert($this->tempDir . '/test.jpg');
        $image_expected = $this->Path->convert($this->tempDir . '/test.jpg');

        $this->assertSame($image_expected, $image);
    }
}
