<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Processors;

use AndyDefer\LaravelImages\Contracts\Processors\ImageProcessorInterface;
use AndyDefer\LaravelImages\Contracts\Storage\ImageStorageInterface;
use AndyDefer\LaravelImages\Enums\ImageExtension;
use AndyDefer\LaravelImages\ValueObjects\ImagePathVO;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use Intervention\Image\ImageManager;
use RuntimeException;

/**
 * Image processor using the Imagick extension.
 *
 * This processor leverages ImageMagick's powerful image manipulation capabilities
 * through the PHP Imagick extension. It provides superior image quality and
 * supports a wider range of formats compared to GD.
 *
 * @see https://www.php.net/manual/en/book.imagick.php
 */
final class ImagickImageProcessor implements ImageProcessorInterface
{
    private readonly ImageManager $imageManager;

    private const string DRIVER_NAME = 'imagick';

    public function __construct(
        private readonly ImageStorageInterface $storage,
        private readonly FileSystemInterface $fileSystem,
    ) {
        $this->imageManager = ImageManager::imagick();
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

        $extension = ImageExtension::tryFrom($imagePath->getExtension());

        $this->saveImageWithFormat($image, $fullResizedPath, $extension, $quality);

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
    private function saveImageWithFormat(
        mixed $image,
        string $fullPath,
        ?ImageExtension $extension,
        ?int $quality
    ): void {
        if ($quality === null) {
            $image->save($fullPath);

            return;
        }

        $this->saveWithQuality($image, $fullPath, $extension, $quality);
    }

    /**
     * Saves the image with quality settings based on the image extension.
     */
    private function saveWithQuality(
        mixed $image,
        string $fullPath,
        ?ImageExtension $extension,
        int $quality
    ): void {
        $extensionValue = $extension?->value ?? 'jpg';

        match (true) {
            $extension === ImageExtension::JPEG,
            $extension === ImageExtension::JPG => $image->toJpeg($quality)->save($fullPath),

            $extension === ImageExtension::WEBP => $image->toWebp($quality)->save($fullPath),

            $extension === ImageExtension::PNG => $image->toPng()->save($fullPath),

            $extension === ImageExtension::GIF => $image->toGif()->save($fullPath),

            default => $image->encodeByExtension($extensionValue, quality: $quality)->save($fullPath),
        };
    }
}
