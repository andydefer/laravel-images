<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Contracts\Configs;

interface ImagesConfigInterface
{
    /**
     * Get the image processor driver.
     *
     * @return string The driver name ('gd' or 'imagick')
     */
    public function getDriver(): string;

    /**
     * Get the storage disk.
     *
     * @return string The disk name
     */
    public function getDisk(): string;
}
