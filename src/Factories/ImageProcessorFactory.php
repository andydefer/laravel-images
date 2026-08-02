<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Factories;

use AndyDefer\LaravelImages\Contracts\Processors\ImageProcessorInterface;
use AndyDefer\LaravelImages\Contracts\Storage\ImageStorageInterface;
use AndyDefer\LaravelImages\Processors\GdImageProcessor;
use AndyDefer\LaravelImages\Processors\ImagickImageProcessor;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use RuntimeException;

/**
 * Factory for creating image processor instances.
 *
 * This factory provides a centralized way to instantiate image processors
 * based on the requested driver name. It encapsulates the creation logic
 * and ensures consistent configuration across the application.
 *
 * Supported drivers:
 * - 'gd': Uses the GD extension (bundled with PHP)
 * - 'imagick': Uses the Imagick extension (requires ImageMagick)
 *
 * @example
 * $processor = ImageProcessorFactory::create('gd', $storage, $fileSystem);
 */
final class ImageProcessorFactory
{
    private const SUPPORTED_DRIVERS = ['gd', 'imagick'];

    /**
     * Creates an image processor instance for the specified driver.
     *
     * @param  string  $driver  The processor driver name ('gd' or 'imagick')
     * @param  ImageStorageInterface  $storage  The storage implementation for file operations
     * @param  FileSystemInterface  $fileSystem  The filesystem implementation
     * @return ImageProcessorInterface The instantiated image processor
     *
     * @throws RuntimeException When an unsupported driver is requested
     */
    public static function create(
        string $driver,
        ImageStorageInterface $storage,
        FileSystemInterface $fileSystem,
    ): ImageProcessorInterface {
        return match ($driver) {
            'gd' => new GdImageProcessor($storage, $fileSystem),
            'imagick' => new ImagickImageProcessor($storage, $fileSystem),
            default => throw new RuntimeException(
                sprintf(
                    'Unsupported image processor driver: "%s". Supported drivers: %s',
                    $driver,
                    implode(', ', self::SUPPORTED_DRIVERS)
                )
            ),
        };
    }

    /**
     * Returns the list of supported driver names.
     *
     * @return array<int, string> Array of supported driver names
     */
    public static function getSupportedDrivers(): array
    {
        return self::SUPPORTED_DRIVERS;
    }

    /**
     * Checks if a given driver is supported.
     *
     * @param  string  $driver  The driver name to check
     * @return bool True if the driver is supported, false otherwise
     */
    public static function isSupported(string $driver): bool
    {
        return in_array($driver, self::SUPPORTED_DRIVERS, true);
    }
}
