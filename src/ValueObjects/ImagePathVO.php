<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Utils\StrictAssociative;

/**
 * Value Object for image path manipulation.
 *
 * Encapsulates path logic for images including directory, filename, extension
 * extraction, thumbnail path generation, and file matching.
 *
 * @example
 * $path = new ImagePathVO('images/avatar.jpg');
 * $thumbnail = $path->getThumbnailPath('small');
 * $resized = $path->getResizedPath(300, 300);
 */
final class ImagePathVO extends AbstractValueObject
{
    private readonly string $directory;

    private readonly string $filename;

    private readonly string $basename;

    private readonly string $extension;

    private readonly string $fullPath;

    public function __construct(string $path)
    {
        $this->fullPath = $path;
        $this->directory = dirname($path);
        $this->filename = basename($path);
        $this->extension = pathinfo($this->filename, PATHINFO_EXTENSION);
        $this->basename = pathinfo($this->filename, PATHINFO_FILENAME);
    }

    /**
     * Returns the full relative path.
     */
    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    /**
     * Returns the directory path.
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Returns the filename with extension.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Returns the basename without extension.
     */
    public function getBasename(): string
    {
        return $this->basename;
    }

    /**
     * Returns the file extension.
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * Generates a thumbnail path for a specific size.
     *
     * @param  string  $size  The thumbnail size (e.g., 'small', 'medium', 'large')
     * @return string The thumbnail file path
     */
    public function getThumbnailPath(string $size): string
    {
        return $this->directory.'/'.$this->basename.'_'.$size.'.'.$this->extension;
    }

    /**
     * Generates a resized image path for specific dimensions.
     *
     * @param  int  $width  The target width
     * @param  int  $height  The target height
     * @return string The resized image file path
     */
    public function getResizedPath(int $width, int $height): string
    {
        return $this->directory.'/'.$this->basename."_{$width}x{$height}.".$this->extension;
    }

    /**
     * Checks if a file is a thumbnail of this image.
     *
     * @param  string  $filePath  The file path to check
     * @return bool True if the file is a thumbnail
     */
    public function isThumbnail(string $filePath): bool
    {
        return str_starts_with($filePath, $this->directory.'/'.$this->basename.'_')
            && str_ends_with($filePath, '.'.$this->extension);
    }

    /**
     * Checks if a file is a resized version of this image.
     *
     * @param  string  $filePath  The file path to check
     * @return bool True if the file is a resized version
     */
    public function isResized(string $filePath): bool
    {
        $pattern = '/^'.preg_quote($this->directory.'/'.$this->basename, '/').'_(\d+)x(\d+)\.'.preg_quote($this->extension, '/').'$/';

        return preg_match($pattern, $filePath) === 1;
    }

    /**
     * Extracts dimensions from a resized file path.
     *
     * @param  string  $filePath  The resized file path
     * @return array{width: int, height: int}|null The dimensions or null if not a resized path
     */
    public function extractDimensionsFromResized(string $filePath): ?array
    {
        $pattern = '/^'.preg_quote($this->directory.'/'.$this->basename, '/').'_(\d+)x(\d+)\.'.preg_quote($this->extension, '/').'$/';

        if (preg_match($pattern, $filePath, $matches) === 1) {
            return [
                'width' => (int) $matches[1],
                'height' => (int) $matches[2],
            ];
        }

        return null;
    }

    /**
     * Generates all thumbnail paths for the given sizes.
     *
     * @param  array<string, array{width: int, height: int}>  $sizes  The thumbnail sizes configuration
     * @return array<string, string> Map of size name to thumbnail path
     */
    public function getThumbnailPaths(array $sizes): array
    {
        $paths = [];

        foreach ($sizes as $size => $dimensions) {
            $paths[$size] = $this->getThumbnailPath($size);
        }

        return $paths;
    }

    /**
     * Creates an instance from a string path.
     *
     * @param  string  $path  The path string
     * @return self A new ImagePathVO instance
     */
    public static function fromString(string $path): self
    {
        return new self($path);
    }

    /**
     * Returns the path value as a string.
     */
    public function __toString(): string
    {
        return $this->fullPath;
    }

    /**
     * Returns the path components as a StrictAssociative object.
     *
     * @return StrictAssociative The path components
     */
    public function getValue(): StrictAssociative
    {
        return StrictAssociative::from([
            'full_path' => $this->fullPath,
            'directory' => $this->directory,
            'filename' => $this->filename,
            'basename' => $this->basename,
            'extension' => $this->extension,
        ]);
    }
}
