<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\LaravelImages\Contracts\Storage\ImageStorageInterface;
use AndyDefer\LaravelImages\Directives\CompressImagesDirective;
use AndyDefer\LaravelImages\Storage\LocalImageStorage;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use AndyDefer\PhpServices\Services\FileSystemService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;

final class CompressImagesDirectiveTest extends IntegrationTestCase
{
    use DatabaseMigrations;

    private DirectiveTestingService $service;

    private FileSystemInterface $fileSystem;

    private ImageStorageInterface $storage;

    private string $testDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->areToolsInstalled()) {
            $this->markTestSkipped('pngquant or jpegoptim not installed. Run: sudo apt install pngquant jpegoptim');
        }

        $this->fileSystem = new FileSystemService;
        $this->storage = new LocalImageStorage($this->fileSystem, 'public');

        $this->testDirectory = storage_path('app/public/images/test');
        $this->fileSystem->ensureDirectoryExists($this->testDirectory);
        $this->cleanTestDirectory();

        $this->service = new DirectiveTestingService(
            application: $this->app,
            sourcePaths: []
        );

        $this->service->getKernel()->addDirective(CompressImagesDirective::class);
    }

    protected function tearDown(): void
    {
        $this->cleanTestDirectory();
        $this->service->destroy();
        parent::tearDown();
    }

    private function cleanTestDirectory(): void
    {
        if ($this->fileSystem->exists($this->testDirectory)) {
            $this->fileSystem->deleteDirectory($this->testDirectory);
        }
        // Recréer le répertoire pour les prochains tests
        $this->fileSystem->ensureDirectoryExists($this->testDirectory);
    }

    private function areToolsInstalled(): bool
    {
        $tools = ['pngquant', 'jpegoptim'];

        foreach ($tools as $tool) {
            $process = new Process(['which', $tool]);
            $process->run();

            if (! $process->isSuccessful()) {
                return false;
            }
        }

        return true;
    }

    private function createTestImage(string $filename, int $width = 100, int $height = 100, string $format = 'jpg'): string
    {
        // ✅ S'assurer que le répertoire existe
        $this->fileSystem->ensureDirectoryExists($this->testDirectory);

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

    // ============================================================
    // TESTS DE BASE
    // ============================================================

    public function test_compress_images_successfully(): void
    {
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('image2.jpg', 800, 600, 'jpg');
        $this->createTestImage('image3.png', 800, 600, 'png');

        $source = 'images/test';

        $response = $this->service->run("images:compress {$source}");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📷 Starting image compression...', $response->output);
        $this->assertStringContainsString('✅ Source directory:', $response->output);
        $this->assertStringContainsString('📁 Found 3 images to process', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);
    }

    public function test_compress_with_destination(): void
    {
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('image2.png', 800, 600, 'png');

        $source = 'images/test';
        $destination = 'images/compressed';

        $response = $this->service->run("images:compress {$source} {$destination}");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        // ✅ Ne pas vérifier le message de création du répertoire (il peut ne pas apparaître)
        $this->assertStringContainsString('📷 Starting image compression...', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);

        // ✅ Vérifier que les fichiers compressés existent
        $this->assertTrue($this->storage->exists('images/compressed/image1.jpg'));
        $this->assertTrue($this->storage->exists('images/compressed/image2.png'));
    }

    public function test_compress_with_custom_png_quality(): void
    {
        $this->createTestImage('image.png', 800, 600, 'png');

        $source = 'images/test';

        $response = $this->service->run("images:compress {$source} --png-quality=30-40");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📷 Starting image compression...', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);
    }

    public function test_compress_with_custom_jpg_quality(): void
    {
        $this->createTestImage('image.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        $response = $this->service->run("images:compress {$source} --jpg-quality=40");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📷 Starting image compression...', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);
    }

    // ============================================================
    // TESTS DE COMPORTEMENT
    // ============================================================

    public function test_compress_with_recursive(): void
    {
        $subdir = $this->testDirectory.'/subdir';
        $this->fileSystem->ensureDirectoryExists($subdir);

        $this->createTestImage('image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('subdir/image2.jpg', 800, 600, 'jpg');
        $this->createTestImage('subdir/image3.png', 800, 600, 'png');

        $source = 'images/test';

        $response = $this->service->run("images:compress {$source} --recursive");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📁 Found 3 images to process', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);
    }

    public function test_compress_dry_run(): void
    {
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('image2.png', 800, 600, 'png');

        $source = 'images/test';

        $response = $this->service->run("images:compress {$source} --dry-run");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📋 DRY RUN - No changes will be made', $response->output);
        $this->assertStringContainsString('📋 Files to compress:', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);
    }

    public function test_compress_with_strip_meta(): void
    {
        $this->createTestImage('image.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        $response = $this->service->run("images:compress {$source} --strip-meta");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📷 Starting image compression...', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);
    }

    public function test_compress_with_force(): void
    {
        $this->createTestImage('image.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        $response = $this->service->run("images:compress {$source} --force");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📷 Starting image compression...', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);
    }

    // ============================================================
    // TESTS DE COMPORTEMENT AVEC SHOW SUMMARY
    // ============================================================

    public function test_compress_shows_summary(): void
    {
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('image2.png', 800, 600, 'png');

        $source = 'images/test';

        $response = $this->service->run("images:compress {$source}");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📊 Summary:', $response->output);
        $this->assertStringContainsString('📁 Files processed:', $response->output);
        $this->assertStringContainsString('📦 Size before:', $response->output);
        $this->assertStringContainsString('📦 Size after:', $response->output);
        $this->assertStringContainsString('💾 Space saved:', $response->output);
    }

    // ============================================================
    // TESTS D'ERREUR
    // ============================================================

    public function test_compress_with_invalid_source(): void
    {
        $response = $this->service->run('images:compress invalid/path');

        $this->assertSame(ExitCode::RUNTIME_ERROR, $response->exit_code);
        $this->assertStringContainsString('Source directory not found', $response->output);
    }

    public function test_compress_with_no_images(): void
    {
        $emptyDir = $this->testDirectory.'/empty';
        $this->fileSystem->ensureDirectoryExists($emptyDir);

        $source = 'images/test/empty';

        $response = $this->service->run("images:compress {$source}");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('⚠️ No images found to compress', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);
    }

    // ============================================================
    // TESTS DE PERFORMANCE
    // ============================================================

    public function test_compress_large_number_of_images(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->createTestImage("image_{$i}.jpg", 800, 600, 'jpg');
        }

        $source = 'images/test';

        $start = microtime(true);
        $response = $this->service->run("images:compress {$source}");
        $duration = microtime(true) - $start;

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📁 Found 10 images to process', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);

        $this->assertLessThan(30, $duration);
    }

    // ============================================================
    // TESTS AVEC DIFFÉRENTS FORMATS
    // ============================================================

    public function test_compress_jpg_images(): void
    {
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('image2.jpg', 800, 600, 'jpg');
        $this->createTestImage('image3.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        $response = $this->service->run("images:compress {$source} --jpg-quality=45");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📁 Found 3 images to process', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);
    }

    public function test_compress_png_images(): void
    {
        $this->createTestImage('image1.png', 800, 600, 'png');
        $this->createTestImage('image2.png', 800, 600, 'png');

        $source = 'images/test';

        $response = $this->service->run("images:compress {$source} --png-quality=30-40");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📁 Found 2 images to process', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);
    }

    public function test_compress_mixed_formats(): void
    {
        $this->createTestImage('image1.jpg', 800, 600, 'jpg');
        $this->createTestImage('image2.jpeg', 800, 600, 'jpeg');
        $this->createTestImage('image3.png', 800, 600, 'png');

        $source = 'images/test';

        $response = $this->service->run("images:compress {$source}");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📁 Found 3 images to process', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);
    }

    // ============================================================
    // TESTS D'ALIAS
    // ============================================================

    public function test_compress_alias_works(): void
    {
        $this->createTestImage('image.jpg', 800, 600, 'jpg');

        $source = 'images/test';

        $response = $this->service->run("imc {$source}");

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('📷 Starting image compression...', $response->output);
        $this->assertStringContainsString('✅ Compression completed', $response->output);
    }
}
