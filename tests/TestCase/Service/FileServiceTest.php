<?php
declare(strict_types=1);

namespace ADWS\Utils\Test\TestCase\Service;

use ADWS\Utils\Service\FileService;
use ADWS\Utils\Utility\File;
use ADWS\Utils\Utility\Folder;
use ADWS\Utils\Utility\Path;
use Cake\TestSuite\TestCase;

class FileServiceTest extends TestCase
{
    protected FileService $Service;

    /**
     * @var \ADWS\Utils\Utility\Folder
     */
    protected Folder $Folder;

    /**
     * @var \ADWS\Utils\Utility\Path
     */
    protected Path $Path;

    /**
     * @var \ADWS\Utils\Utility\File
     */
    protected File $File;

    /**
     * @var string Cílový adresář pro uploady během testu
     */
    protected string $tmpDir1;

    /**
     * @var string Cílový adresář pro uploady během testu
     */
    protected string $tmpDir2;

    /**
     * @var string Cílový adresář pro uploady během testu
     */
    protected string $tmpDir3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Service = new FileService();
        $this->Folder = new Folder();
        $this->Path = new Path();
        $this->File = new File();

        $this->tmpDir1 = 'img/articles/000001/files';
        $this->Folder->create($this->tmpDir1);

        $this->tmpDir2 = 'img/articles/000002';
        $this->Folder->create($this->tmpDir2);

        $this->tmpDir3 = 'img/articles/000003/files';
        $this->Folder->create($this->tmpDir3);

        file_put_contents($this->Path->convert($this->tmpDir1) . '/test.pdf', 'dummy content');
        file_put_contents($this->Path->convert($this->tmpDir2) . '/000001-main-original.jpg', 'dummy content');
        file_put_contents($this->Path->convert($this->tmpDir3) . '/test.pdf', 'dummy content');
    }

    protected function tearDown(): void
    {
        $this->Folder->delete('img');

        parent::tearDown();
    }

    public function testDeleteSingleFile(): void
    {
        $id = 2;
        $type = 'articles';
        $filename = '000001-main-original.jpg';

        $result = $this->Service->delete($type, $id, $filename);
        $this->assertTrue($result);
    }

    public function testDeleteWholeFolder(): void
    {
        $id = 2;
        $type = 'articles';

        $result = $this->Service->delete($type, $id);
        $this->assertTrue($result);

        $folderPath = $this->tmpDir2 . "/{$id}";
        $this->assertDirectoryDoesNotExist($folderPath);
    }

    public function testDeleteNonExistentFile(): void
    {
        $id = 3;
        $type = 'articles';
        $filename = 'nonexistent.pdf';

        $result = $this->Service->delete($type, $id, $filename);
        $this->assertFalse($result);
    }

    public function testDeleteNonExistentFolder(): void
    {
        $id = 4;
        $type = 'articles';

        $result = $this->Service->delete($type, $id);
        $this->assertFalse($result);
    }
}
