<?php
declare(strict_types=1);

namespace ADWS\Utils\Test\TestCase\Utility;

use ADWS\Utils\Utility\File;
use ADWS\Utils\Utility\Folder;
use ADWS\Utils\Utility\Path;
use Cake\TestSuite\TestCase;

class FolderTest extends TestCase
{
    /**
     * @var string
     */
    protected string $tempDir;

    /**
     * @var string
     */
    protected string $tempSubDir;

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

        $this->tempDir = 'folder_test';
        $this->tempSubDir = 'folder_test/sub';
    }

    protected function tearDown(): void
    {
        if ($this->Folder->exist($this->tempDir)) {
            $this->Folder->delete($this->tempDir);
        }

        parent::tearDown();
    }

    public function testCreateAndExist(): void
    {
        $this->assertFalse($this->Folder->exist($this->tempDir));
        $this->assertTrue($this->Folder->create($this->tempDir));
        $this->assertTrue($this->Folder->exist($this->tempDir));
        $this->assertFalse($this->Folder->create($this->tempDir));
    }

    public function testDelete(): void
    {
        $this->Folder->create($this->tempSubDir);
        file_put_contents($this->Path->convert($this->tempDir . '/file1.txt'), 'test');
        file_put_contents($this->Path->convert($this->tempDir . '/sub/file2.txt'), 'test');
        $this->assertTrue($this->Folder->exist($this->tempDir));
        $this->assertTrue($this->Folder->delete($this->tempDir));
        $this->assertFalse($this->Folder->exist($this->tempDir));
    }

    public function testFolder(): void
    {
        $this->assertSame('000123', $this->Folder->folder(123));
        $this->assertSame('001234', $this->Folder->folder(1234));
    }
}
