<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Processors;

use AndyDefer\LaravelImages\Contracts\Storage\ImageStorageInterface;
use AndyDefer\LaravelImages\Processors\ImagickImageProcessor;
use AndyDefer\LaravelImages\Storage\LocalImageStorage;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use AndyDefer\LaravelImages\ValueObjects\ImagePathVO;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use AndyDefer\PhpServices\Services\FileSystemService;
use RuntimeException;

final class ImagickImageProcessorTest extends IntegrationTestCase
{
    private ImagickImageProcessor $processor;

    private ImageStorageInterface $storage;

    private FileSystemInterface $fileSystem;

    private string $testDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick extension is not installed.');
        }

        $this->fileSystem = new FileSystemService;
        $this->storage = new LocalImageStorage($this->fileSystem, 'public');
        $this->processor = new ImagickImageProcessor($this->storage, $this->fileSystem);

        $this->testDirectory = storage_path('app/public/images/test');
        $this->fileSystem->ensureDirectoryExists($this->testDirectory);

        $this->cleanTestDirectory();
    }

    protected function tearDown(): void
    {
        $this->cleanTestDirectory();
        parent::tearDown();
    }

    private function cleanTestDirectory(): void
    {
        if ($this->fileSystem->exists($this->testDirectory)) {
            $this->fileSystem->deleteDirectory($this->testDirectory);
        }
    }

    // ============================================================
    // TESTS: resize()
    // ============================================================

    public function test_resize_image_with_quality(): void
    {
        $sourcePath = $this->createTestImage('source.jpg', 800, 600);
        $imagePath = new ImagePathVO('images/test/source.jpg');

        $result = $this->processor->resize($imagePath, 400, 300, 80);

        $fullResizedPath = $this->storage->getFullPath($result->getFullPath());

        $this->assertTrue($this->fileSystem->exists($fullResizedPath));
        $this->assertStringContainsString('400x300', $result->getFullPath());

        $dimensions = getimagesize($fullResizedPath);
        $this->assertEquals(400, $dimensions[0]);
        $this->assertEquals(300, $dimensions[1]);
    }

    public function test_resize_image_without_quality(): void
    {
        $sourcePath = $this->createTestImage('source_no_quality.jpg', 800, 600);
        $imagePath = new ImagePathVO('images/test/source_no_quality.jpg');

        $result = $this->processor->resize($imagePath, 400);

        $fullResizedPath = $this->storage->getFullPath($result->getFullPath());

        $this->assertTrue($this->fileSystem->exists($fullResizedPath));

        $dimensions = getimagesize($fullResizedPath);
        $this->assertEquals(400, $dimensions[0]);
        $this->assertEquals(300, $dimensions[1]);
    }

    public function test_resize_image_with_height_only(): void
    {
        $sourcePath = $this->createTestImage('source_height.jpg', 1200, 800);
        $imagePath = new ImagePathVO('images/test/source_height.jpg');

        $result = $this->processor->resize($imagePath, 600, 400);

        $fullResizedPath = $this->storage->getFullPath($result->getFullPath());

        $this->assertTrue($this->fileSystem->exists($fullResizedPath));

        $dimensions = getimagesize($fullResizedPath);
        $this->assertEquals(600, $dimensions[0]);
        $this->assertEquals(400, $dimensions[1]);
    }

    public function test_resize_image_with_scale_only_width(): void
    {
        $sourcePath = $this->createTestImage('source_scale_width.jpg', 1000, 500);
        $imagePath = new ImagePathVO('images/test/source_scale_width.jpg');

        $result = $this->processor->resize($imagePath, 300);

        $fullResizedPath = $this->storage->getFullPath($result->getFullPath());

        $dimensions = getimagesize($fullResizedPath);
        $this->assertEquals(300, $dimensions[0]);
        $this->assertEquals(150, $dimensions[1]);
    }

    public function test_resize_jpeg_with_quality(): void
    {
        $sourcePath = $this->createTestImage('source_jpeg.jpg', 800, 600);
        $imagePath = new ImagePathVO('images/test/source_jpeg.jpg');

        $result = $this->processor->resize($imagePath, 300, 200, 90);

        $fullResizedPath = $this->storage->getFullPath($result->getFullPath());

        $this->assertTrue($this->fileSystem->exists($fullResizedPath));
        $this->assertStringEndsWith('.jpg', $result->getFullPath());
        $this->assertEquals('jpg', $result->getExtension());
    }

    public function test_resize_png_with_quality(): void
    {
        $sourcePath = $this->createTestImage('source.png', 800, 600);
        $imagePath = new ImagePathVO('images/test/source.png');

        $result = $this->processor->resize($imagePath, 300, 200, 80);

        $fullResizedPath = $this->storage->getFullPath($result->getFullPath());

        $this->assertTrue($this->fileSystem->exists($fullResizedPath));
        $this->assertStringEndsWith('.png', $result->getFullPath());
        $this->assertEquals('png', $result->getExtension());
    }

    public function test_resize_webp_with_quality(): void
    {
        $sourcePath = $this->createTestImage('source.webp', 800, 600);
        $imagePath = new ImagePathVO('images/test/source.webp');

        $result = $this->processor->resize($imagePath, 300, 200, 80);

        $fullResizedPath = $this->storage->getFullPath($result->getFullPath());

        $this->assertTrue($this->fileSystem->exists($fullResizedPath));
        $this->assertStringEndsWith('.webp', $result->getFullPath());
        $this->assertEquals('webp', $result->getExtension());
    }

    public function test_resize_gif_with_quality(): void
    {
        $sourcePath = $this->createTestImage('source.gif', 400, 400);
        $imagePath = new ImagePathVO('images/test/source.gif');

        $result = $this->processor->resize($imagePath, 200, 200, 80);

        $fullResizedPath = $this->storage->getFullPath($result->getFullPath());

        $this->assertTrue($this->fileSystem->exists($fullResizedPath));
        $this->assertStringEndsWith('.gif', $result->getFullPath());
        $this->assertEquals('gif', $result->getExtension());
    }

    public function test_resize_avif_with_quality(): void
    {
        $sourcePath = $this->createTestImage('source.avif', 800, 600);
        $imagePath = new ImagePathVO('images/test/source.avif');

        $result = $this->processor->resize($imagePath, 300, 200, 80);

        $fullResizedPath = $this->storage->getFullPath($result->getFullPath());

        $this->assertTrue($this->fileSystem->exists($fullResizedPath));
        $this->assertStringEndsWith('.avif', $result->getFullPath());
        $this->assertEquals('avif', $result->getExtension());
    }

    public function test_resize_keeps_aspect_ratio_when_height_not_provided(): void
    {
        $sourcePath = $this->createTestImage('source_ratio.jpg', 1200, 800);
        $imagePath = new ImagePathVO('images/test/source_ratio.jpg');

        $result = $this->processor->resize($imagePath, 600);

        $fullResizedPath = $this->storage->getFullPath($result->getFullPath());

        $dimensions = getimagesize($fullResizedPath);
        $this->assertEquals(600, $dimensions[0]);
        $this->assertEquals(400, $dimensions[1]);
    }

    public function test_resize_throws_exception_when_image_not_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Image not found: images/test/nonexistent.jpg');

        $imagePath = new ImagePathVO('images/test/nonexistent.jpg');
        $this->processor->resize($imagePath, 100, 100);
    }

    // ============================================================
    // TESTS: read()
    // ============================================================

    public function test_read_image(): void
    {
        $sourcePath = $this->createTestImage('source_read.jpg', 800, 600);
        $imagePath = new ImagePathVO('images/test/source_read.jpg');

        $fullPath = $this->storage->getFullPath($imagePath->getFullPath());

        $image = $this->processor->read($fullPath);

        $this->assertNotNull($image);
        $this->assertEquals(800, $image->width());
        $this->assertEquals(600, $image->height());
    }

    public function test_read_image_with_different_formats(): void
    {
        $formats = ['jpg', 'png', 'webp', 'gif'];

        foreach ($formats as $format) {
            $sourcePath = $this->createTestImage("source_read.{$format}", 400, 300);
            $imagePath = new ImagePathVO("images/test/source_read.{$format}");
            $fullPath = $this->storage->getFullPath($imagePath->getFullPath());

            $image = $this->processor->read($fullPath);

            $this->assertNotNull($image);
            $this->assertEquals(400, $image->width());
            $this->assertEquals(300, $image->height());
        }
    }

    public function test_read_throws_exception_when_file_not_found(): void
    {
        $this->expectException(\Exception::class);
        $this->processor->read('/path/to/nonexistent/image.jpg');
    }

    // ============================================================
    // TESTS: save()
    // ============================================================

    public function test_save_image(): void
    {
        $sourcePath = $this->createTestImage('source_save.jpg', 800, 600);
        $fullPath = $this->storage->getFullPath('images/test/source_save.jpg');

        $image = $this->processor->read($fullPath);

        $savePath = $this->storage->getFullPath('images/test/saved.jpg');
        $this->fileSystem->ensureDirectoryExists(dirname($savePath));

        $this->processor->save($image, $savePath);

        $this->assertTrue($this->fileSystem->exists($savePath));

        $dimensions = getimagesize($savePath);
        $this->assertEquals(800, $dimensions[0]);
        $this->assertEquals(600, $dimensions[1]);
    }

    public function test_save_image_with_different_formats(): void
    {
        $formats = ['jpg', 'png', 'webp', 'gif'];

        foreach ($formats as $format) {
            $sourcePath = $this->createTestImage("source_save.{$format}", 400, 300);
            $fullPath = $this->storage->getFullPath("images/test/source_save.{$format}");

            $image = $this->processor->read($fullPath);

            $savePath = $this->storage->getFullPath("images/test/saved.{$format}");
            $this->fileSystem->ensureDirectoryExists(dirname($savePath));

            $this->processor->save($image, $savePath);

            $this->assertTrue($this->fileSystem->exists($savePath));
        }
    }

    // ============================================================
    // TESTS: getDriverName()
    // ============================================================

    public function test_get_driver_name(): void
    {
        $driverName = $this->processor->getDriverName();
        $this->assertEquals('imagick', $driverName);
    }

    // ============================================================
    // METHODES UTILITAIRES
    // ============================================================

    private function createTestImage(string $filename, int $width, int $height): string
    {
        $fullPath = $this->storage->getFullPath('images/test/'.$filename);

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $image = new \Imagick;
        $image->newImage($width, $height, new \ImagickPixel('white'));
        $image->setImageFormat($extension);

        $draw = new \ImagickDraw;
        $draw->setStrokeColor(new \ImagickPixel('red'));
        $draw->setStrokeWidth(2);
        $draw->rectangle(10, 10, $width - 10, $height - 10);

        $draw->setStrokeColor(new \ImagickPixel('blue'));
        $draw->line(0, 0, $width, $height);

        $image->drawImage($draw);

        if (in_array($extension, ['jpg', 'jpeg', 'webp', 'avif'])) {
            $image->setImageCompressionQuality(90);
        }

        $this->fileSystem->ensureDirectoryExists(dirname($fullPath));
        $image->writeImage($fullPath);
        $image->clear();
        $image->destroy();

        return $fullPath;
    }
}
