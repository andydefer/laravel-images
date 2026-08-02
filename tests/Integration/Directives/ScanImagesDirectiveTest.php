<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\LaravelImages\Contracts\Storage\ImageStorageInterface;
use AndyDefer\LaravelImages\Directives\ScanImagesDirective;
use AndyDefer\LaravelImages\Storage\LocalImageStorage;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use AndyDefer\PhpServices\Services\FileSystemService;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * Integration tests for the ScanImagesDirective.
 *
 * Verifies that the directive correctly scans directories for images,
 * applies filters (depth, extensions, exclusions), generates proper output
 * files (JSON or PHP array), and handles edge cases gracefully.
 *
 * @group integration
 * @group directives
 * @group scan-images
 */
final class ScanImagesDirectiveTest extends IntegrationTestCase
{
    use DatabaseMigrations;

    private DirectiveTestingService $service;

    private FileSystemInterface $fileSystem;

    private ImageStorageInterface $storage;

    private string $testDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSystem = new FileSystemService;
        $this->storage = new LocalImageStorage($this->fileSystem, 'public');

        $this->testDirectory = storage_path('app/public/images/test');
        $this->initializeTestDirectory();

        $this->service = new DirectiveTestingService(
            application: $this->app,
            sourcePaths: []
        );

        $this->service->getKernel()->addDirective(ScanImagesDirective::class);
    }

    protected function tearDown(): void
    {
        $this->cleanTestDirectory();
        $this->service->destroy();
        parent::tearDown();
    }

    private function initializeTestDirectory(): void
    {
        $this->fileSystem->ensureDirectoryExists($this->testDirectory);
        $this->cleanTestDirectory();
    }

    private function cleanTestDirectory(): void
    {
        if ($this->fileSystem->exists($this->testDirectory)) {
            $this->fileSystem->deleteDirectory($this->testDirectory);
        }

        $this->fileSystem->ensureDirectoryExists($this->testDirectory);
    }

    /**
     * Creates a test image with specified dimensions and format.
     *
     * @param  string  $filename  The image filename
     * @param  int  $width  Image width in pixels
     * @param  int  $height  Image height in pixels
     * @param  string  $format  Image format (jpg, png, jpeg)
     * @return string The absolute path to the created image
     */
    private function createTestImage(
        string $filename,
        int $width = 100,
        int $height = 100,
        string $format = 'jpg'
    ): string {
        $fullPath = $this->testDirectory.'/'.$filename;

        $image = imagecreatetruecolor($width, $height);

        $white = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocate($image, 255, 0, 0);
        $blue = imagecolorallocate($image, 0, 0, 255);

        imagefill($image, 0, 0, $white);
        imagerectangle($image, 10, 10, $width - 10, $height - 10, $red);
        imageline($image, 0, 0, $width, $height, $blue);

        match ($format) {
            'jpg', 'jpeg' => imagejpeg($image, $fullPath, 95),
            'png' => imagepng($image, $fullPath, 9),
            default => imagejpeg($image, $fullPath, 95),
        };

        imagedestroy($image);

        return $fullPath;
    }

    private function createSubDirectory(string $name): void
    {
        $path = $this->testDirectory.'/'.$name;
        $this->fileSystem->ensureDirectoryExists($path);
    }

    private function getOutputFile(string $extension = 'json'): string
    {
        $files = glob(storage_path('app/public/scan_result_*.'.$extension));

        if (empty($files)) {
            return '';
        }

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $files[0];
    }

    // ============================================================
    // BASIC SCAN TESTS
    // ============================================================

    public function test_scan_images_successfully(): void
    {
        // Arrange
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('image2.jpg', 800, 600, 'jpg');
        $this->createTestImage('image3.png', 800, 600, 'png');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source}");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔍 Scanning images...', $response->output);
        $this->assertStringContainsString('✅ Source directory:', $response->output);
        $this->assertStringContainsString('📊 Found: 3 images', $response->output);
        $this->assertStringContainsString('✅ Scan completed', $response->output);

        $outputFile = $this->getOutputFile('json');
        $this->assertNotEmpty($outputFile);
        $this->assertFileExists($outputFile);

        $content = json_decode(file_get_contents($outputFile), true);
        $this->assertCount(3, $content);
        $this->assertArrayHasKey('path', $content[0]);
        $this->assertArrayHasKey('filename', $content[0]);
        $this->assertArrayHasKey('size', $content[0]);
        $this->assertArrayHasKey('width', $content[0]);
        $this->assertArrayHasKey('height', $content[0]);
    }

    public function test_scan_images_with_array_output(): void
    {
        // Arrange
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source} 0 array");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📊 Found: 1 images', $response->output);
        $this->assertStringContainsString('✅ Scan completed', $response->output);

        $outputFile = $this->getOutputFile('php');
        $this->assertNotEmpty($outputFile);
        $this->assertFileExists($outputFile);

        $content = file_get_contents($outputFile);
        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('return [', $content);

        $data = include $outputFile;
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('path', $data[0]);
        $this->assertArrayHasKey('filename', $data[0]);
    }

    // ============================================================
    // OUTPUT FILE CUSTOM PATH TESTS
    // ============================================================

    public function test_scan_with_custom_output_file_relative(): void
    {
        // Arrange
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');

        $source = 'images/test';
        $customPath = 'custom/scan-result.json';

        // Act
        $response = $this->service->run("images:scan {$source} {$customPath}");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('💾 Output saved to:', $response->output);

        $expectedPath = storage_path('app/public/'.$customPath);
        $this->assertFileExists($expectedPath);

        $content = json_decode(file_get_contents($expectedPath), true);
        $this->assertIsArray($content);
        $this->assertCount(1, $content);
        $this->assertArrayHasKey('filename', $content[0]);
        $this->assertEquals('image1.jpg', $content[0]['filename']);
    }

    public function test_scan_with_custom_output_file_absolute(): void
    {
        // Arrange
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');

        $source = 'images/test';
        $customPath = storage_path('app/public/absolute/result.json');

        // Act
        $response = $this->service->run("images:scan {$source} {$customPath}");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('💾 Output saved to:', $response->output);

        $this->assertFileExists($customPath);

        $content = json_decode(file_get_contents($customPath), true);
        $this->assertIsArray($content);
        $this->assertCount(1, $content);
        $this->assertArrayHasKey('filename', $content[0]);
        $this->assertEquals('image1.jpg', $content[0]['filename']);
    }

    public function test_scan_with_custom_output_file_php_array(): void
    {
        // Arrange
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');

        $source = 'images/test';
        $customPath = 'custom/scan-result.php';

        // Act
        $response = $this->service->run("images:scan {$source} {$customPath} _ array");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('💾 Output saved to:', $response->output);

        $expectedPath = storage_path('app/public/'.$customPath);
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('return [', $content);

        $data = include $expectedPath;
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('filename', $data[0]);
        $this->assertEquals('image1.jpg', $data[0]['filename']);
    }

    public function test_scan_with_custom_output_file_creates_directory(): void
    {
        // Arrange
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');

        $source = 'images/test';
        $customPath = 'deep/nested/custom/scan-result.json';

        // Act
        $response = $this->service->run("images:scan {$source} {$customPath}");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('💾 Output saved to:', $response->output);

        $expectedPath = storage_path('app/public/'.$customPath);
        $this->assertFileExists($expectedPath);

        $content = json_decode(file_get_contents($expectedPath), true);
        $this->assertIsArray($content);
        $this->assertCount(1, $content);
        $this->assertArrayHasKey('filename', $content[0]);
        $this->assertEquals('image1.jpg', $content[0]['filename']);
    }

    public function test_scan_with_custom_output_file_and_depth(): void
    {
        // Arrange
        $this->createTestImage('root.jpg', 800, 600, 'jpg');
        $this->createSubDirectory('sub1');
        $this->createTestImage('sub1/image1.jpg', 800, 600, 'jpg');
        $this->createSubDirectory('sub1/sub2');
        $this->createTestImage('sub1/sub2/image2.jpg', 800, 600, 'jpg');

        $source = 'images/test';
        $customPath = 'custom/scan-depth.json';

        // Act
        $response = $this->service->run("images:scan {$source} {$customPath} 1");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('💾 Output saved to:', $response->output);
        $this->assertStringContainsString('📊 Found: 2 images', $response->output);

        $expectedPath = storage_path('app/public/'.$customPath);
        $this->assertFileExists($expectedPath);

        $content = json_decode(file_get_contents($expectedPath), true);
        $this->assertCount(2, $content);
    }

    public function test_scan_with_custom_output_file_and_extensions(): void
    {
        // Arrange
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('image2.png', 800, 600, 'png');
        $this->createTestImage('image3.webp', 800, 600, 'png');

        $source = 'images/test';
        $customPath = 'custom/scan-extensions.json';

        // Act
        $response = $this->service->run("images:scan {$source} {$customPath} 0 json [png,jpg]");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('💾 Output saved to:', $response->output);
        $this->assertStringContainsString('📊 Found: 2 images', $response->output);

        $expectedPath = storage_path('app/public/'.$customPath);
        $this->assertFileExists($expectedPath);

        $content = json_decode(file_get_contents($expectedPath), true);
        $this->assertCount(2, $content);

        $extensions = array_column($content, 'extension');
        $this->assertContains('jpg', $extensions);
        $this->assertContains('png', $extensions);
        $this->assertNotContains('webp', $extensions);
    }

    public function test_scan_with_custom_output_file_and_hash(): void
    {
        // Arrange
        $this->createTestImage('image.jpg', 800, 600, 'jpg');

        $source = 'images/test';
        $customPath = 'custom/scan-hash.json';

        // Act
        $response = $this->service->run("images:scan {$source} {$customPath} 0 json --hash");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('💾 Output saved to:', $response->output);

        $expectedPath = storage_path('app/public/'.$customPath);
        $this->assertFileExists($expectedPath);

        $content = json_decode(file_get_contents($expectedPath), true);
        $this->assertCount(1, $content);
        $this->assertArrayHasKey('hash', $content[0]);
        $this->assertNotEmpty($content[0]['hash']);
    }

    public function test_scan_with_custom_output_file_and_all_options(): void
    {
        // Arrange
        $this->createTestImage('root.jpg', 800, 600, 'jpg');
        $this->createTestImage('root.png', 800, 600, 'png');

        $this->createSubDirectory('sub1');
        $this->createTestImage('sub1/image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('sub1/image2.png', 800, 600, 'png');

        $this->createSubDirectory('compressed');
        $this->createTestImage('compressed/img.jpg', 800, 600, 'jpg');

        $source = 'images/test';
        $customPath = 'custom/scan-all.json';

        // Act
        $response = $this->service->run(
            "images:scan {$source} {$customPath} 1 json [png,jpg] [compressed] --hash"
        );

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $this->assertStringContainsString('📊 Found: 4 images', $response->output);
        $this->assertStringContainsString('💾 Output saved to:', $response->output);
        $this->assertStringContainsString('✅ Scan completed', $response->output);

        $expectedPath = storage_path('app/public/'.$customPath);
        $this->assertFileExists($expectedPath);

        $content = json_decode(file_get_contents($expectedPath), true);

        $this->assertCount(4, $content);

        foreach ($content as $image) {
            $this->assertArrayHasKey('hash', $image);
        }

        foreach ($content as $image) {
            $this->assertStringNotContainsString('/compressed/', $image['path']);
        }
    }

    // ============================================================
    // DEPTH FILTER TESTS
    // ============================================================

    public function test_scan_with_depth_limit(): void
    {
        // Arrange
        $this->createTestImage('root.jpg', 800, 600, 'jpg');
        $this->createSubDirectory('sub1');
        $this->createTestImage('sub1/image1.jpg', 800, 600, 'jpg');
        $this->createSubDirectory('sub1/sub2');
        $this->createTestImage('sub1/sub2/image2.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source} _ 1");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📊 Found: 2 images', $response->output);
        $this->assertStringContainsString('✅ Scan completed', $response->output);

        $outputFile = $this->getOutputFile('json');
        $content = json_decode(file_get_contents($outputFile), true);
        $this->assertCount(2, $content);
    }

    public function test_scan_without_depth_limit(): void
    {
        // Arrange
        $this->createTestImage('root.jpg', 800, 600, 'jpg');
        $this->createSubDirectory('sub1');
        $this->createTestImage('sub1/image1.jpg', 800, 600, 'jpg');
        $this->createSubDirectory('sub1/sub2');
        $this->createTestImage('sub1/sub2/image2.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source} 0");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📊 Found: 3 images', $response->output);

        $outputFile = $this->getOutputFile('json');
        $content = json_decode(file_get_contents($outputFile), true);
        $this->assertCount(3, $content);
    }

    // ============================================================
    // EXTENSION FILTER TESTS
    // ============================================================

    public function test_scan_with_extensions_filter(): void
    {
        // Arrange
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('image2.png', 800, 600, 'png');
        $this->createTestImage('image3.webp', 800, 600, 'png');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source} _ 0 json [png,jpg]");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📊 Found: 2 images', $response->output);

        $outputFile = $this->getOutputFile('json');
        $content = json_decode(file_get_contents($outputFile), true);

        $extensions = array_column($content, 'extension');
        $this->assertContains('jpg', $extensions);
        $this->assertContains('png', $extensions);
        $this->assertNotContains('webp', $extensions);
    }

    public function test_scan_with_all_extensions(): void
    {
        // Arrange
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('image2.png', 800, 600, 'png');
        $this->createTestImage('image3.gif', 800, 600, 'png');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source}");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📊 Found: 3 images', $response->output);
    }

    // ============================================================
    // EXCLUSION TESTS
    // ============================================================

    public function test_scan_with_exclude_directories(): void
    {
        // Arrange
        $this->createTestImage('root.jpg', 800, 600, 'jpg');
        $this->createSubDirectory('compressed');
        $this->createTestImage('compressed/image1.jpg', 800, 600, 'jpg');
        $this->createSubDirectory('thumbnails');
        $this->createTestImage('thumbnails/image2.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source} _ 0 json [] [compressed,thumbnails]");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📊 Found: 1 images', $response->output);

        $outputFile = $this->getOutputFile('json');
        $content = json_decode(file_get_contents($outputFile), true);

        $this->assertCount(1, $content);
        $this->assertEquals('root.jpg', $content[0]['filename']);
    }

    public function test_scan_with_exclude_compressed_flag(): void
    {
        // Arrange
        $this->createTestImage('root.jpg', 800, 600, 'jpg');
        $this->createSubDirectory('compressed');
        $this->createTestImage('compressed/image1.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source} --exclude-compressed");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📊 Found: 1 images', $response->output);

        $outputFile = $this->getOutputFile('json');
        $content = json_decode(file_get_contents($outputFile), true);

        $this->assertCount(1, $content);
        $this->assertEquals('root.jpg', $content[0]['filename']);
    }

    // ============================================================
    // HASH TESTS
    // ============================================================

    public function test_scan_with_hash_flag(): void
    {
        // Arrange
        $this->createTestImage('image.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source} --hash");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📊 Found: 1 images', $response->output);

        $outputFile = $this->getOutputFile('json');
        $content = json_decode(file_get_contents($outputFile), true);

        $this->assertCount(1, $content);
        $this->assertArrayHasKey('hash', $content[0]);
        $this->assertNotEmpty($content[0]['hash']);
    }

    public function test_scan_without_hash_flag(): void
    {
        // Arrange
        $this->createTestImage('image.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source}");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📊 Found: 1 images', $response->output);

        $outputFile = $this->getOutputFile('json');
        $content = json_decode(file_get_contents($outputFile), true);

        $this->assertCount(1, $content);
        $this->assertArrayNotHasKey('hash', $content[0]);
    }

    // ============================================================
    // OUTPUT FILE TESTS
    // ============================================================

    public function test_scan_generates_json_file_in_storage(): void
    {
        // Arrange
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('image2.png', 800, 600, 'png');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source}");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('💾 Output saved to:', $response->output);

        $outputFile = $this->getOutputFile('json');
        $this->assertNotEmpty($outputFile);
        $this->assertFileExists($outputFile);
        $this->assertStringContainsString('scan_result_', basename($outputFile));
    }

    public function test_scan_generates_php_file_in_storage(): void
    {
        // Arrange
        $this->createTestImage('image.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source} 0 array");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('💾 Output saved to:', $response->output);

        $outputFile = $this->getOutputFile('php');
        $this->assertNotEmpty($outputFile);
        $this->assertFileExists($outputFile);
        $this->assertStringContainsString('scan_result_', basename($outputFile));
        $this->assertStringEndsWith('.php', $outputFile);
    }

    // ============================================================
    // METADATA STRUCTURE TESTS
    // ============================================================

    public function test_scan_extracts_correct_metadata(): void
    {
        // Arrange
        $this->createTestImage('test.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source}");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $outputFile = $this->getOutputFile('json');
        $content = json_decode(file_get_contents($outputFile), true);

        $image = $content[0];

        $this->assertArrayHasKey('path', $image);
        $this->assertArrayHasKey('filename', $image);
        $this->assertArrayHasKey('original_filename', $image);
        $this->assertArrayHasKey('extension', $image);
        $this->assertArrayHasKey('mime_type', $image);
        $this->assertArrayHasKey('size', $image);
        $this->assertArrayHasKey('width', $image);
        $this->assertArrayHasKey('height', $image);

        $this->assertEquals('test.jpg', $image['filename']);
        $this->assertEquals('jpg', $image['extension']);
        $this->assertEquals(800, $image['width']);
        $this->assertEquals(600, $image['height']);
        $this->assertGreaterThan(0, $image['size']);
    }

    // ============================================================
    // ERROR HANDLING TESTS
    // ============================================================

    public function test_scan_with_invalid_source(): void
    {
        // Act
        $response = $this->service->run('images:scan invalid/path');

        // Assert
        $this->assertSame(ExitCode::RUNTIME_ERROR, $response->exit_code);
        $this->assertStringContainsString('Source directory not found', $response->output);
    }

    public function test_scan_with_no_images(): void
    {
        // Arrange
        $emptyDir = $this->testDirectory.'/empty';
        $this->fileSystem->ensureDirectoryExists($emptyDir);

        $source = 'images/test/empty';

        // Act
        $response = $this->service->run("images:scan {$source}");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('⚠️ No images found', $response->output);
        $this->assertStringContainsString('✅ Scan completed', $response->output);
    }

    public function test_scan_with_invalid_output_format(): void
    {
        // Arrange
        $this->createTestImage('image.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        // Act
        $response = $this->service->run("images:scan {$source} 0 invalid");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('💾 Output saved to:', $response->output);

        $outputFile = $this->getOutputFile('json');
        $this->assertNotEmpty($outputFile);
    }

    // ============================================================
    // ALIAS TESTS
    // ============================================================

    public function test_scan_alias_works(): void
    {
        // Arrange
        $this->createTestImage('image.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        // Act
        $response = $this->service->run("ims {$source}");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔍 Scanning images...', $response->output);
        $this->assertStringContainsString('✅ Scan completed', $response->output);
    }

    public function test_scan_alias_with_flags(): void
    {
        // Arrange
        $this->createTestImage('image.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        // Act
        $response = $this->service->run("ims {$source} --hash");

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('🔍 Scanning images...', $response->output);
        $this->assertStringContainsString('✅ Scan completed', $response->output);

        $outputFile = $this->getOutputFile('json');
        $content = json_decode(file_get_contents($outputFile), true);

        $this->assertArrayHasKey('hash', $content[0]);
    }

    // ============================================================
    // PERFORMANCE TESTS
    // ============================================================

    public function test_scan_large_number_of_images(): void
    {
        // Arrange
        for ($i = 0; $i < 20; $i++) {
            $this->createTestImage("image_{$i}.jpg", 800, 600, 'jpg');
        }

        $source = 'images/test';

        // Act
        $start = microtime(true);
        $response = $this->service->run("images:scan {$source}");
        $duration = microtime(true) - $start;

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📊 Found: 20 images', $response->output);
        $this->assertStringContainsString('✅ Scan completed', $response->output);

        $outputFile = $this->getOutputFile('json');
        $content = json_decode(file_get_contents($outputFile), true);
        $this->assertCount(20, $content);

        $this->assertLessThan(5, $duration);
    }

    // ============================================================
    // COMBINED OPTIONS TESTS
    // ============================================================

    public function test_scan_with_all_options_combined(): void
    {
        // Arrange
        $this->createTestImage('root.jpg', 800, 600, 'jpg');
        $this->createTestImage('root.png', 800, 600, 'png');

        $this->createSubDirectory('sub1');
        $this->createTestImage('sub1/image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('sub1/image2.png', 800, 600, 'png');

        $this->createSubDirectory('compressed');
        $this->createTestImage('compressed/img.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        // Act
        $response = $this->service->run(
            "images:scan {$source} _  json [png,jpg] [compressed] --hash"
        );

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $this->assertStringContainsString('📊 Found: 4 images', $response->output);
        $this->assertStringContainsString('💾 Output saved to:', $response->output);
        $this->assertStringContainsString('✅ Scan completed', $response->output);

        $outputFile = $this->getOutputFile('json');
        $content = json_decode(file_get_contents($outputFile), true);

        $this->assertCount(4, $content);

        foreach ($content as $image) {
            $this->assertArrayHasKey('hash', $image);
        }

        foreach ($content as $image) {
            $this->assertStringNotContainsString('/compressed/', $image['path']);
        }
    }
}
