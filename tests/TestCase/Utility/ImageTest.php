<?php
declare(strict_types=1);

namespace ADWS\Utils\Test\TestCase\Utility;

use ADWS\Utils\Utility\Image;
use ADWS\Utils\Utility\Path;
use Cake\TestSuite\TestCase;

class ImageTest extends TestCase
{
    /**
     * @var string
     */
    protected string $testImageJpg;

    /**
     * @var string
     */
    protected string $testImagePng;

    /**
     * @var string
     */
    protected string $outputImage;

    /**
     * @var \ADWS\Utils\Utility\Path
     */
    protected Path $Path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Path = new Path();

        $this->testImageJpg = $this->Path->convert('test.jpg');
        $this->testImagePng = $this->Path->convert('test.png');
        $this->outputImage = $this->Path->convert('output.jpg');

        // Vytvoříme malý testovací JPEG, pokud neexistuje
        if (!file_exists($this->testImageJpg)) {
            $img = imagecreatetruecolor(200, 100);
            $white = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $white);
            imagejpeg($img, $this->testImageJpg);
            imagedestroy($img);
        }

        // Vytvoříme malý testovací PNG, pokud neexistuje
        if (!file_exists($this->testImagePng)) {
            $img = imagecreatetruecolor(100, 200);
            imagesavealpha($img, true);
            $trans = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefill($img, 0, 0, $trans);
            imagepng($img, $this->testImagePng);
            imagedestroy($img);
        }
    }

    protected function tearDown(): void
    {
        foreach ([$this->testImageJpg, $this->testImagePng, $this->outputImage] as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    public function testLoadAndDimensions(): void
    {
        $image = new Image($this->testImageJpg);
        $this->assertEquals(200, $image->getWidth());
        $this->assertEquals(100, $image->getHeight());

        $imagePng = new Image($this->testImagePng);
        $this->assertEquals(100, $imagePng->getWidth());
        $this->assertEquals(200, $imagePng->getHeight());
    }

    public function testBestFitResize(): void
    {
        $image = new Image($this->testImageJpg);
        $image->bestFit(100, 50);

        $this->assertLessThanOrEqual(100, $image->getWidth());
        $this->assertLessThanOrEqual(50, $image->getHeight());
    }

    public function testSave(): void
    {
        $image = new Image($this->testImageJpg);
        $image->bestFit(50, 50);
        $image->save($this->outputImage);

        $this->assertFileExists($this->outputImage);
        $info = getimagesize($this->outputImage);
        $this->assertNotFalse($info);
        $this->assertLessThanOrEqual(50, $info[0]);
        $this->assertLessThanOrEqual(50, $info[1]);
    }
}
