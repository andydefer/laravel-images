<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Contracts\Storage;

use Illuminate\Http\UploadedFile;

interface ImageStorageInterface
{
    /**
     * Store an uploaded file.
     *
     * @param  UploadedFile  $file  The uploaded file
     * @param  string  $path  The storage path
     * @param  string  $filename  The filename
     * @return string The stored file path
     */
    public function store(UploadedFile $file, string $path, string $filename): string;

    /**
     * Delete a file.
     *
     * @param  string  $path  The file path
     * @return bool True if deleted
     */
    public function delete(string $path): bool;

    /**
     * Delete multiple files.
     *
     * @param  array<string>  $paths  The file paths
     * @return bool True if all deleted
     */
    public function deleteMultiple(array $paths): bool;

    /**
     * Check if a file exists.
     *
     * @param  string  $path  The file path
     * @return bool True if exists
     */
    public function exists(string $path): bool;

    /**
     * Get all files in a directory.
     *
     * @param  string  $directory  The directory path
     * @return array<string> List of file paths
     */
    public function files(string $directory): array;

    /**
     * Get the full filesystem path for a relative path.
     *
     * @param  string  $path  The relative path
     * @return string The full filesystem path
     */
    public function getFullPath(string $path): string;

    /**
     * Get the base path.
     *
     * @return string The base path
     */
    public function getBasePath(): string;

    /**
     * Set the base path.
     *
     * @param  string  $basePath  The base path
     */
    public function setBasePath(string $basePath): self;
}
