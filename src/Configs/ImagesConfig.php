<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Configs;

use AndyDefer\LaravelImages\Contracts\Configs\ImagesConfigInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Configuration manager for Laravel Images.
 *
 * Provides a typed interface to the Laravel configuration repository,
 * ensuring type safety and providing default values for all configuration options.
 */
final class ImagesConfig implements ImagesConfigInterface
{
    private const CONFIG_KEY = 'images';

    private const DEFAULT_DRIVER = 'gd';

    private const DEFAULT_DISK = 'public';

    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getDriver(): string
    {
        return (string) $this->config->get(
            self::CONFIG_KEY.'.driver',
            self::DEFAULT_DRIVER
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getDisk(): string
    {
        return (string) $this->config->get(
            self::CONFIG_KEY.'.disk',
            self::DEFAULT_DISK
        );
    }
}
