<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Storage;

use AndyDefer\LaravelImages\Contracts\Storage\ImageStorageInterface;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use Illuminate\Http\UploadedFile;

/**
 * Local filesystem implementation for image storage.
 *
 * This storage adapter uses the local filesystem to store, retrieve, and delete
 * image files. It provides a clean abstraction over PHP's filesystem operations
 * through the FileSystemInterface contract.
 *
 * @example
 * $storage = new LocalImageStorage($fileSystem, 'public');
 * $path = $storage->store($uploadedFile, 'images/avatars', 'user-1.jpg');
 * $storage->delete($path);
 */
final class LocalImageStorage implements ImageStorageInterface
{
    private string $basePath;

    public function __construct(
        private readonly FileSystemInterface $fileSystem,
        string $basePath = 'public',
    ) {
        $this->basePath = $this->normalizePath($basePath);
    }

    /**
     * {@inheritDoc}
     */
    public function store(UploadedFile $file, string $path, string $filename): string
    {
        $fullPath = $this->buildFullPath($path, $filename);
        $directory = $this->extractDirectory($fullPath);

        $this->fileSystem->ensureDirectoryExists($directory);
        $file->move($directory, $filename);

        return $this->buildRelativePath($path, $filename);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $path): bool
    {
        $fullPath = $this->getFullPath($path);

        return $this->fileSystem->delete($fullPath);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple(array $paths): bool
    {
        $allSucceeded = true;

        foreach ($paths as $path) {
            if (! $this->delete($path)) {
                $allSucceeded = false;
            }
        }

        return $allSucceeded;
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $path): bool
    {
        $fullPath = $this->getFullPath($path);

        return $this->fileSystem->exists($fullPath);
    }

    /**
     * {@inheritDoc}
     */
    public function files(string $directory): array
    {
        $fullPath = $this->getFullPath($directory);

        return $this->fileSystem->glob($fullPath.'/*');
    }

    /**
     * {@inheritDoc}
     */
    public function getFullPath(string $path): string
    {
        return storage_path(
            sprintf('app/%s/%s', $this->basePath, ltrim($path, '/'))
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * {@inheritDoc}
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = $this->normalizePath($basePath);

        return $this;
    }

    /**
     * Builds the full filesystem path from path and filename components.
     *
     * @param  string  $path  The storage path
     * @param  string  $filename  The filename
     * @return string The complete filesystem path
     */
    private function buildFullPath(string $path, string $filename): string
    {
        return $this->getFullPath($path.'/'.$filename);
    }

    /**
     * Builds the relative storage path from path and filename components.
     *
     * @param  string  $path  The storage path
     * @param  string  $filename  The filename
     * @return string The relative storage path
     */
    private function buildRelativePath(string $path, string $filename): string
    {
        return $path.'/'.$filename;
    }

    /**
     * Extracts the directory part from a full file path.
     *
     * @param  string  $fullPath  The complete file path
     * @return string The directory path
     */
    private function extractDirectory(string $fullPath): string
    {
        return dirname($fullPath);
    }

    /**
     * Normalizes a base path by removing trailing slashes.
     *
     * @param  string  $path  The path to normalize
     * @return string The normalized path
     */
    private function normalizePath(string $path): string
    {
        return rtrim($path, '/');
    }
}
