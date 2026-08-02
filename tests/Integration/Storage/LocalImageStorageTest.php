<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Storage;

use AndyDefer\LaravelImages\Storage\LocalImageStorage;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use AndyDefer\PhpServices\Services\FileSystemService;
use Illuminate\Http\UploadedFile;

final class LocalImageStorageTest extends IntegrationTestCase
{
    private LocalImageStorage $storage;

    private FileSystemInterface $fileSystem;

    private string $testDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSystem = new FileSystemService;
        $this->storage = new LocalImageStorage($this->fileSystem, 'public');

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

    private function createTestFile(string $filename = 'test.txt', string $content = 'test content'): string
    {
        $fullPath = $this->testDirectory.'/'.$filename;
        $this->fileSystem->put($fullPath, $content);

        return $fullPath;
    }

    // ============================================================
    // TESTS: store()
    // ============================================================

    public function test_store_file(): void
    {
        $file = UploadedFile::fake()->create('test.jpg', 100);

        $path = 'images/test';
        $filename = 'uploaded.jpg';

        $result = $this->storage->store($file, $path, $filename);

        $this->assertEquals('images/test/uploaded.jpg', $result);
        $this->assertTrue($this->storage->exists($result));

        $fullPath = $this->storage->getFullPath($result);
        $this->assertTrue($this->fileSystem->exists($fullPath));
    }

    public function test_store_file_creates_directory_if_not_exists(): void
    {
        $file = UploadedFile::fake()->create('test.jpg', 100);

        // Utiliser un chemin unique avec timestamp pour éviter les conflits
        $uniqueId = uniqid();
        $path = 'images/new/deep/path/'.$uniqueId;
        $filename = 'uploaded.jpg';

        $fullPath = $this->storage->getFullPath($path);
        $this->assertFalse($this->fileSystem->exists($fullPath));

        $result = $this->storage->store($file, $path, $filename);

        $expectedPath = 'images/new/deep/path/'.$uniqueId.'/uploaded.jpg';
        $this->assertEquals($expectedPath, $result);
        $this->assertTrue($this->storage->exists($result));

        $fullPath = $this->storage->getFullPath($result);
        $this->assertTrue($this->fileSystem->exists($fullPath));

        // Nettoyer
        $this->storage->delete($result);
    }

    public function test_store_file_keeps_original_filename(): void
    {
        $file = UploadedFile::fake()->create('custom_name.jpg', 100);

        $path = 'images/test';
        $filename = 'custom_name.jpg';

        $result = $this->storage->store($file, $path, $filename);

        $this->assertStringEndsWith('custom_name.jpg', $result);

        $fullPath = $this->storage->getFullPath($result);
        $this->assertTrue($this->fileSystem->exists($fullPath));

        // Nettoyer
        $this->storage->delete($result);
    }

    // ============================================================
    // TESTS: delete()
    // ============================================================

    public function test_delete_file(): void
    {
        $filePath = $this->createTestFile('delete_test.txt');
        $relativePath = 'images/test/delete_test.txt';

        $this->assertTrue($this->fileSystem->exists($filePath));

        $result = $this->storage->delete($relativePath);

        $this->assertTrue($result);
        $this->assertFalse($this->fileSystem->exists($filePath));
    }

    public function test_delete_file_returns_true_if_file_does_not_exist(): void
    {
        $result = $this->storage->delete('images/test/nonexistent.txt');

        $this->assertTrue($result);
    }

    // ============================================================
    // TESTS: deleteMultiple()
    // ============================================================

    public function test_delete_multiple_files(): void
    {
        $file1 = $this->createTestFile('file1.txt');
        $file2 = $this->createTestFile('file2.txt');
        $file3 = $this->createTestFile('file3.txt');

        $relative1 = 'images/test/file1.txt';
        $relative2 = 'images/test/file2.txt';
        $relative3 = 'images/test/file3.txt';

        $this->assertTrue($this->fileSystem->exists($file1));
        $this->assertTrue($this->fileSystem->exists($file2));
        $this->assertTrue($this->fileSystem->exists($file3));

        $result = $this->storage->deleteMultiple([$relative1, $relative2, $relative3]);

        $this->assertTrue($result);
        $this->assertFalse($this->fileSystem->exists($file1));
        $this->assertFalse($this->fileSystem->exists($file2));
        $this->assertFalse($this->fileSystem->exists($file3));
    }

    public function test_delete_multiple_returns_true_if_some_files_fail(): void
    {
        $file1 = $this->createTestFile('file1.txt');
        $relative1 = 'images/test/file1.txt';
        $relative2 = 'images/test/nonexistent.txt';

        $result = $this->storage->deleteMultiple([$relative1, $relative2]);

        $this->assertTrue($result);
        $this->assertFalse($this->fileSystem->exists($this->storage->getFullPath($relative1)));
    }

    public function test_delete_multiple_with_empty_array(): void
    {
        $result = $this->storage->deleteMultiple([]);

        $this->assertTrue($result);
    }

    // ============================================================
    // TESTS: exists()
    // ============================================================

    public function test_exists_returns_true_for_existing_file(): void
    {
        $this->createTestFile('exists_test.txt');
        $relativePath = 'images/test/exists_test.txt';

        $this->assertTrue($this->storage->exists($relativePath));
    }

    public function test_exists_returns_false_for_non_existing_file(): void
    {
        $this->assertFalse($this->storage->exists('images/test/nonexistent.txt'));
    }

    public function test_exists_returns_false_for_deleted_file(): void
    {
        $relativePath = 'images/test/delete_test.txt';
        $this->createTestFile('delete_test.txt');

        $this->assertTrue($this->storage->exists($relativePath));

        $this->storage->delete($relativePath);

        $this->assertFalse($this->storage->exists($relativePath));
    }

    // ============================================================
    // TESTS: files()
    // ============================================================

    public function test_files_returns_list_of_files_in_directory(): void
    {
        $this->createTestFile('file1.txt');
        $this->createTestFile('file2.txt');
        $this->createTestFile('file3.jpg');

        $files = $this->storage->files('images/test');

        $this->assertCount(3, $files);

        foreach ($files as $file) {
            $this->assertStringStartsWith($this->testDirectory.'/', $file);
        }

        // Nettoyer
        foreach ($files as $file) {
            $this->fileSystem->delete($file);
        }
    }

    public function test_files_returns_empty_array_for_empty_directory(): void
    {
        $files = $this->storage->files('images/test');
        $this->assertCount(0, $files);
    }

    public function test_files_returns_empty_array_for_non_existing_directory(): void
    {
        $files = $this->storage->files('images/nonexistent');
        $this->assertCount(0, $files);
    }

    public function test_files_returns_all_files_including_subdirectories(): void
    {
        $this->createTestFile('file1.txt');

        $subdir = $this->testDirectory.'/subdir';
        $this->fileSystem->ensureDirectoryExists($subdir);
        $this->fileSystem->put($subdir.'/file2.txt', 'content');

        $files = $this->storage->files('images/test');

        // files() avec glob($path.'/*') retourne tous les fichiers
        // et dossiers du dossier parent
        $this->assertCount(2, $files);

        // Nettoyer
        $this->fileSystem->deleteDirectory($subdir);
        $this->fileSystem->delete($this->testDirectory.'/file1.txt');
    }

    // ============================================================
    // TESTS: getFullPath()
    // ============================================================

    public function test_get_full_path(): void
    {
        $path = 'images/test/file.jpg';
        $fullPath = $this->storage->getFullPath($path);

        $expected = storage_path('app/public/images/test/file.jpg');
        $this->assertEquals($expected, $fullPath);
    }

    public function test_get_full_path_removes_leading_slash(): void
    {
        $path = '/images/test/file.jpg';
        $fullPath = $this->storage->getFullPath($path);

        $expected = storage_path('app/public/images/test/file.jpg');
        $this->assertEquals($expected, $fullPath);
    }

    public function test_get_full_path_with_trailing_slash(): void
    {
        $path = 'images/test/';
        $fullPath = $this->storage->getFullPath($path);

        $expected = storage_path('app/public/images/test/');
        $this->assertEquals($expected, $fullPath);
    }

    // ============================================================
    // TESTS: getBasePath()
    // ============================================================

    public function test_get_base_path(): void
    {
        $basePath = $this->storage->getBasePath();
        $this->assertEquals('public', $basePath);
    }

    // ============================================================
    // TESTS: setBasePath()
    // ============================================================

    public function test_set_base_path(): void
    {
        $newStorage = $this->storage->setBasePath('custom');

        $this->assertSame($this->storage, $newStorage);
        $this->assertEquals('custom', $this->storage->getBasePath());

        // Remettre à 'public' pour les autres tests
        $this->storage->setBasePath('public');
    }

    public function test_set_base_path_removes_trailing_slash(): void
    {
        $this->storage->setBasePath('custom/');
        $this->assertEquals('custom', $this->storage->getBasePath());

        // Remettre à 'public' pour les autres tests
        $this->storage->setBasePath('public');
    }

    public function test_set_base_path_affects_get_full_path(): void
    {
        $this->storage->setBasePath('custom');

        $path = 'images/test/file.jpg';
        $fullPath = $this->storage->getFullPath($path);

        $expected = storage_path('app/custom/images/test/file.jpg');
        $this->assertEquals($expected, $fullPath);

        // Remettre à 'public' pour les autres tests
        $this->storage->setBasePath('public');
    }

    // ============================================================
    // TESTS: workflow complet
    // ============================================================

    public function test_complete_storage_workflow(): void
    {
        // 1. Store
        $file = UploadedFile::fake()->create('workflow.jpg', 100);
        $path = 'images/workflow';
        $filename = 'workflow.jpg';

        $result = $this->storage->store($file, $path, $filename);
        $this->assertEquals('images/workflow/workflow.jpg', $result);

        // 2. Exists
        $this->assertTrue($this->storage->exists($result));

        // 3. Files
        $files = $this->storage->files('images/workflow');
        $this->assertCount(1, $files);

        // 4. Delete
        $this->assertTrue($this->storage->delete($result));

        // 5. Exists after delete
        $this->assertFalse($this->storage->exists($result));

        // 6. Files after delete
        $files = $this->storage->files('images/workflow');
        $this->assertCount(0, $files);
    }

    public function test_multiple_operations_with_different_base_paths(): void
    {
        // S'assurer que le répertoire existe pour 'private'
        $privateDir = storage_path('app/private/images/test');
        $this->fileSystem->ensureDirectoryExists($privateDir);

        // Storage avec base path 'public'
        $publicStorage = new LocalImageStorage($this->fileSystem, 'public');

        // Storage avec base path 'private'
        $privateStorage = new LocalImageStorage($this->fileSystem, 'private');

        // Créer un fichier dans public
        $file = UploadedFile::fake()->create('test.jpg', 100);
        $publicPath = $publicStorage->store($file, 'images/test', 'public.jpg');

        // Créer un fichier dans private
        $file2 = UploadedFile::fake()->create('test2.jpg', 100);
        $privatePath = $privateStorage->store($file2, 'images/test', 'private.jpg');

        // Vérifier que les chemins sont différents
        $this->assertNotEquals($publicPath, $privatePath);

        // Vérifier que chaque storage voit son fichier
        $this->assertTrue($publicStorage->exists('images/test/public.jpg'));
        $this->assertTrue($privateStorage->exists('images/test/private.jpg'));

        // Vérifier que les chemins sont différents
        $publicFullPath = $publicStorage->getFullPath('images/test/public.jpg');
        $privateFullPath = $privateStorage->getFullPath('images/test/private.jpg');

        $this->assertNotEquals($publicFullPath, $privateFullPath);
        $this->assertStringContainsString('public', $publicFullPath);
        $this->assertStringContainsString('private', $privateFullPath);

        // Nettoyer
        $publicStorage->delete('images/test/public.jpg');
        $privateStorage->delete('images/test/private.jpg');
    }
}
