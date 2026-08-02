<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Contracts\Processors;

use AndyDefer\LaravelImages\ValueObjects\ImagePathVO;

interface ImageProcessorInterface
{
    /**
     * Resize an image.
     *
     * @param  ImagePathVO  $imagePath  The image path VO
     * @param  int  $width  Target width
     * @param  int|null  $height  Target height (if null, aspect ratio is maintained)
     * @param  int|null  $quality  JPEG/WebP quality (0-100)
     * @return ImagePathVO The resized image path VO
     */
    public function resize(ImagePathVO $imagePath, int $width, ?int $height = null, ?int $quality = null): ImagePathVO;

    /**
     * Read an image from a file.
     *
     * @param  string  $path  The file path
     * @return mixed The image instance
     */
    public function read(string $path): mixed;

    /**
     * Save an image to a file.
     *
     * @param  mixed  $image  The image instance
     * @param  string  $path  The file path
     */
    public function save(mixed $image, string $path): void;

    /**
     * Get the driver name.
     *
     * @return string 'gd' or 'imagick'
     */
    public function getDriverName(): string;
}
