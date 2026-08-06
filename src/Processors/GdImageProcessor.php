<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Processors;

use AndyDefer\LaravelImages\Contracts\Processors\ImageProcessorInterface;
use AndyDefer\LaravelImages\Contracts\Storage\ImageStorageInterface;
use AndyDefer\LaravelImages\ValueObjects\ImagePathVO;
use AndyDefer\LaravelUtils\Enums\ImageExtension;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use Intervention\Image\ImageManager;
use RuntimeException;

/**
 * Image processor using the GD extension.
 *
 * This processor leverages PHP's built-in GD extension for image manipulation.
 * It supports common image formats including JPEG, PNG, WebP, and GIF.
 *
 * @see https://www.php.net/manual/en/book.image.php
 */
final class GdImageProcessor implements ImageProcessorInterface
{
    private readonly ImageManager $imageManager;

    private const string DRIVER_NAME = 'gd';

    public function __construct(
        private readonly ImageStorageInterface $storage,
        private readonly FileSystemInterface $fileSystem,
    ) {
        $this->imageManager = ImageManager::gd();
    }

    /**
     * {@inheritDoc}
     */
    public function resize(ImagePathVO $imagePath, int $width, ?int $height = null, ?int $quality = null): ImagePathVO
    {
        $fullPath = $this->storage->getFullPath($imagePath->getFullPath());

        $this->ensureImageExists($fullPath, $imagePath);

        $image = $this->read($fullPath);

        $this->applyScale($image, $width, $height);

        $resizedPath = $imagePath->getResizedPath($width, $height ?? $width);
        $fullResizedPath = $this->storage->getFullPath($resizedPath);

        $this->ensureDirectoryExists($fullResizedPath);

        $this->saveImageWithFormat($image, $fullResizedPath, $imagePath->getExtension(), $quality);

        return new ImagePathVO($resizedPath);
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $path): mixed
    {
        return $this->imageManager->read($path);
    }

    /**
     * {@inheritDoc}
     */
    public function save(mixed $image, string $path): void
    {
        $image->save($path);
    }

    /**
     * {@inheritDoc}
     */
    public function getDriverName(): string
    {
        return self::DRIVER_NAME;
    }

    /**
     * Ensures the source image exists.
     *
     * @throws RuntimeException When the image file is not found
     */
    private function ensureImageExists(string $fullPath, ImagePathVO $imagePath): void
    {
        if (! $this->fileSystem->exists($fullPath)) {
            throw new RuntimeException("Image not found: {$imagePath->getFullPath()}");
        }
    }

    /**
     * Ensures the directory for the target file exists.
     */
    private function ensureDirectoryExists(string $fullPath): void
    {
        $this->fileSystem->ensureDirectoryExists(dirname($fullPath));
    }

    /**
     * Applies scaling to the image.
     */
    private function applyScale(mixed $image, int $width, ?int $height): void
    {
        if ($height !== null) {
            $image->scale($width, $height);
        } else {
            $image->scale($width);
        }
    }

    /**
     * Saves the image with the appropriate format and quality.
     */
    private function saveImageWithFormat(mixed $image, string $fullPath, string $extension, ?int $quality): void
    {
        if ($quality === null) {
            $image->save($fullPath);

            return;
        }

        $this->saveWithQuality($image, $fullPath, $extension, $quality);
    }

    /**
     * Saves the image with quality settings based on the file extension.
     */
    private function saveWithQuality(mixed $image, string $fullPath, string $extension, int $quality): void
    {
        match (true) {
            $extension === ImageExtension::JPEG->value,
            $extension === ImageExtension::JPG->value => $image->toJpeg($quality)->save($fullPath),

            $extension === ImageExtension::WEBP->value => $image->toWebp($quality)->save($fullPath),

            $extension === ImageExtension::PNG->value => $image->toPng()->save($fullPath),

            $extension === ImageExtension::GIF->value => $image->toGif()->save($fullPath),

            default => $image->encodeByExtension($extension, quality: $quality)->save($fullPath),
        };
    }
}
