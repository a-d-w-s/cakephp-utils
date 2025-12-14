<?php
declare(strict_types=1);

namespace ADWS\Utils\Test\TestCase\Utility;

use ADWS\Utils\Utility\File;
use ADWS\Utils\Utility\Folder;
use ADWS\Utils\Utility\Path;
use Cake\TestSuite\TestCase;

class FileTest extends TestCase
{
    /**
     * @var string
     */
    protected string $tempDir;

    /**
     * @var \ADWS\Utils\Utility\File
     */
    protected File $File;

    /**
     * @var \ADWS\Utils\Utility\Folder
     */
    protected Folder $Folder;

    /**
     * @var \ADWS\Utils\Utility\Path
     */
    protected Path $Path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->File = new File();
        $this->Folder = new Folder();
        $this->Path = new Path();

        $this->tempDir = 'file_test';

        if (!$this->Folder->exist($this->tempDir)) {
            $this->Folder->create($this->tempDir);
        }
    }

    protected function tearDown(): void
    {
        if ($this->Folder->exist($this->tempDir)) {
            $this->Folder->delete($this->tempDir);
        }

        parent::tearDown();
    }

    public function testExistAndDelete(): void
    {
        $filePath = $this->tempDir . '/test.txt';
        file_put_contents($this->Path->convert($filePath), 'test');

        $this->assertTrue($this->File->exist($filePath));
        $this->assertTrue($this->File->delete($filePath));
        $this->assertFalse($this->File->exist($filePath));
        $this->assertFalse($this->File->delete($filePath));
    }

    public function testDeleteWithPrefix(): void
    {
        $file1 = $this->tempDir . '/prefix_file_1.txt';
        $file2 = $this->tempDir . '/prefix_file_2.txt';
        $file3 = $this->tempDir . '/other.txt';

        file_put_contents($this->Path->convert($file1), '1');
        file_put_contents($this->Path->convert($file2), '2');
        file_put_contents($this->Path->convert($file3), '3');

        $this->assertTrue($this->File->deleteWithPrefix($this->tempDir, 'prefix_'));

        $this->assertFalse($this->File->exist($file1));
        $this->assertFalse($this->File->exist($file2));
        $this->assertTrue($this->File->exist($file3));
    }
}
