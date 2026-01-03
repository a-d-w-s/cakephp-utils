<?php
declare(strict_types=1);

namespace ADWS\Utils\Test\TestCase\Service;

use ADWS\Utils\Service\FileUploadService;
use ADWS\Utils\Utility\Folder;
use ADWS\Utils\Utility\Path;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

class FileUploadServiceTest extends TestCase
{
    protected FileUploadService $Service;

    /**
     * @var \ADWS\Utils\Utility\Folder
     */
    protected Folder $Folder;

    /**
     * @var \ADWS\Utils\Utility\Path
     */
    protected Path $Path;

    /**
     * @var string Testovací obrázek
     */
    protected string $fixtureFile;

    /**
     * @var string Cílový adresář pro uploady během testu
     */
    protected string $tmpUploadDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Service = new FileUploadService();
        $this->Folder = new Folder();
        $this->Path = new Path();

        $this->fixtureFile = $this->Path->convert('upload/sample.pdf');
        $this->tmpUploadDir = $this->Path->convert('img');

        $this->assertFileExists($this->fixtureFile);
    }

    protected function tearDown(): void
    {
        $this->Folder->delete('img');

        parent::tearDown();
    }

    public function testUploadSingle(): void
    {
        $id = 1;
        $type = 'articles';

        $folder = $this->Folder->folder($id);

        $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
        copy($this->fixtureFile, $tmpFile);

        $uploadedFile = new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'document.pdf',
            'application/pdf',
        );

        $filename = $this->Service->upload($uploadedFile, $id, $type);
        $filePath = $this->tmpUploadDir . "/{$type}/{$folder}/files/{$filename}";

        $this->assertSame('document.pdf', $filename);
        $this->assertFileExists($filePath);
        $this->assertGreaterThan(0, filesize($filePath));
    }

    public function testUploadMultipleFiles(): void
    {
        $id = 2;
        $type = 'articles';

        $uploadedFiles = [];
        for ($i = 1; $i <= 2; $i++) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
            copy($this->fixtureFile, $tmpFile);

            $uploadedFiles[] = new UploadedFile(
                $tmpFile,
                filesize($tmpFile),
                UPLOAD_ERR_OK,
                "document{$i}.pdf",
                'application/pdf',
            );
        }

        $filenames = $this->Service->uploadMultiple($uploadedFiles, $id, $type);

        $this->assertCount(2, $filenames);

        $folder = $this->Folder->folder($id);
        foreach ($filenames as $i => $filename) {
            $expected = 'document' . ($i + 1) . '.pdf';
            $this->assertSame($expected, $filename);

            $filePath = $this->tmpUploadDir . "/{$type}/{$folder}/files/{$filename}";
            $this->assertFileExists($filePath);
            $this->assertGreaterThan(0, filesize($filePath));
        }
    }
}
