<?php
declare(strict_types=1);

namespace ADWS\Utils\Test\TestCase\Service;

use ADWS\Utils\Service\ImageUploadService;
use ADWS\Utils\Utility\Folder;
use ADWS\Utils\Utility\Path;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;
use RuntimeException;

class ImageUploadServiceTest extends TestCase
{
    protected ImageUploadService $Service;

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
    protected string $fixtureImage;

    /**
     * @var string Cílový adresář pro uploady během testu
     */
    protected string $tmpUploadDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Service = new ImageUploadService();
        $this->Folder = new Folder();
        $this->Path = new Path();

        $this->fixtureImage = $this->Path->convert('upload/sample.jpg');
        $this->tmpUploadDir = $this->Path->convert('img');

        $this->assertFileExists($this->fixtureImage);
    }

    protected function tearDown(): void
    {
        $this->Folder->delete('img');

        parent::tearDown();
    }

    public function testUploadCreatesMainImage(): void
    {
        $id = 123;
        $type = 'articles';

        $targetDir = $this->tmpUploadDir . DS . $type;
        $folder = $this->Folder->folder($id);
        $targetFolder = $targetDir . DS . $folder;

        $uploadedFile = new UploadedFile(
            $this->fixtureImage,
            filesize($this->fixtureImage),
            UPLOAD_ERR_OK,
            'sample.jpg',
            'image/jpeg',
        );

        $filename = $this->Service->upload($uploadedFile, $id, $type);
        $targetPathWithFile = $targetFolder . DS . $filename;

        $this->assertSame($folder . '-main-original.jpg', $filename);
        $this->assertFileExists($targetPathWithFile);
        $this->assertGreaterThan(0, filesize($targetPathWithFile));
    }

    public function testUploadFailsOnInvalidMimeType(): void
    {
        $this->expectException(RuntimeException::class);

        // vytvoříme fake PDF
        $fakePdf = TMP . 'fake.pdf';
        file_put_contents($fakePdf, '%PDF-1.4 fake pdf content');

        $uploadedFile = new UploadedFile(
            $fakePdf,
            filesize($fakePdf),
            UPLOAD_ERR_OK,
            'fake.pdf',
            'application/pdf',
        );

        $this->Service->upload($uploadedFile, 1, 'article');
    }

    public function testUploadReturnsNullOnNoFile(): void
    {
        $uploadedFile = new UploadedFile(
            $this->fixtureImage,
            filesize($this->fixtureImage),
            UPLOAD_ERR_NO_FILE,
            'sample.jpg',
            'image/jpeg',
        );

        $result = $this->Service->upload($uploadedFile, 1, 'article');

        $this->assertNull($result);
    }

    public function testUploadMultipleWithIndexedNames(): void
    {
        $id = 123;
        $type = 'articles';

        $folder = $this->Folder->folder($id);
        $targetFolder = $this->tmpUploadDir . DS . $type . DS . $folder;

        // Připravíme 2 gallery soubory
        $galleryFiles = [];
        for ($i = 1; $i <= 2; $i++) {
            $galleryFiles[] = new UploadedFile(
                $this->fixtureImage,
                filesize($this->fixtureImage),
                UPLOAD_ERR_OK,
                "sample{$i}.jpg",
                'image/jpeg',
            );
        }

        // Provedeme upload
        $filenames = $this->Service->uploadMultiple($galleryFiles, $id, $type);

        // Ověření počtu
        $this->assertCount(2, $filenames);

        // Ověření fyzické existence + pattern
        foreach ($filenames as $filename) {
            $this->assertMatchesRegularExpression(
                sprintf(
                    '/^%s-gallery-\d{3}-original\.(jpg|webp)$/',
                    preg_quote($folder, '/'),
                ),
                $filename,
            );

            $this->assertFileExists($targetFolder . DS . $filename);
            $this->assertGreaterThan(0, filesize($targetFolder . DS . $filename));
        }

        // Ověříme, že indexy jsou unikátní a vzestupné
        $indexes = array_map(
            static function (string $filename): int {
                preg_match('/-gallery-(\d{3})-original\./', $filename, $m);

                return (int)$m[1];
            },
            $filenames,
        );

        sort($indexes);

        $this->assertSame(
            range(min($indexes), min($indexes) + count($indexes) - 1),
            $indexes,
        );
    }
}
