<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages;

use AndyDefer\LaravelImages\Configs\ImagesConfig;
use AndyDefer\LaravelImages\Contracts\Configs\ImagesConfigInterface;
use AndyDefer\LaravelImages\Contracts\Processors\ImageProcessorInterface;
use AndyDefer\LaravelImages\Contracts\Services\AlbumServiceInterface;
use AndyDefer\LaravelImages\Contracts\Services\ImageServiceInterface;
use AndyDefer\LaravelImages\Contracts\Storage\ImageStorageInterface;
use AndyDefer\LaravelImages\Factories\ImageProcessorFactory;
use AndyDefer\LaravelImages\Repositories\AlbumRepository;
use AndyDefer\LaravelImages\Repositories\ImageRepository;
use AndyDefer\LaravelImages\Services\AlbumService;
use AndyDefer\LaravelImages\Services\ImageService;
use AndyDefer\LaravelImages\Storage\LocalImageStorage;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use AndyDefer\PhpServices\Services\FileSystemService;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel service provider for the Images package.
 *
 * Registers all services, repositories, and implementations
 * with the Laravel service container.
 */
final class ImageServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container.
     */
    public function register(): void
    {
        $this->registerConfig();
        $this->registerFileSystem();
        $this->registerStorage();
        $this->registerRepositories();
        $this->registerImageProcessor();
        $this->registerServices();
    }

    /**
     * Boot services after registration.
     */
    public function boot(): void
    {
        $this->loadMigrations();
        $this->publishAssets();
    }

    /**
     * Registers configuration bindings.
     */
    private function registerConfig(): void
    {
        $this->app->singleton(
            ImagesConfigInterface::class,
            fn ($app): ImagesConfig => new ImagesConfig($app['config'])
        );
    }

    /**
     * Registers filesystem bindings.
     */
    private function registerFileSystem(): void
    {
        $this->app->singleton(
            FileSystemInterface::class,
            FileSystemService::class
        );
    }

    /**
     * Registers storage bindings.
     */
    private function registerStorage(): void
    {
        $this->app->singleton(
            ImageStorageInterface::class,
            function ($app): LocalImageStorage {
                $config = $app->make(ImagesConfigInterface::class);

                return new LocalImageStorage(
                    $app->make(FileSystemInterface::class),
                    $config->getDisk()
                );
            }
        );
    }

    /**
     * Registers repository bindings.
     */
    private function registerRepositories(): void
    {
        $this->app->singleton(ImageRepository::class);
        $this->app->singleton(AlbumRepository::class);
    }

    /**
     * Registers image processor bindings.
     */
    private function registerImageProcessor(): void
    {
        $this->app->singleton(
            ImageProcessorInterface::class,
            function ($app): ImageProcessorInterface {
                $config = $app->make(ImagesConfigInterface::class);

                return ImageProcessorFactory::create(
                    $config->getDriver(),
                    $app->make(ImageStorageInterface::class),
                    $app->make(FileSystemInterface::class),
                );
            }
        );
    }

    /**
     * Registers service bindings (interfaces and implementations).
     */
    private function registerServices(): void
    {
        // Image Service
        $this->app->singleton(
            ImageServiceInterface::class,
            ImageService::class
        );

        $this->app->singleton(
            ImageService::class,
            function ($app): ImageService {
                return new ImageService(
                    $app->make(ImageRepository::class),
                    $app->make(ImageProcessorInterface::class),
                    $app->make(ImageStorageInterface::class)
                );
            }
        );

        // Album Service
        $this->app->singleton(
            AlbumServiceInterface::class,
            AlbumService::class
        );

        $this->app->singleton(
            AlbumService::class,
            function ($app): AlbumService {
                return new AlbumService(
                    $app->make(AlbumRepository::class),
                    $app->make(ImageService::class)
                );
            }
        );
    }

    /**
     * Loads package migrations if running in console.
     */
    private function loadMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Publishes package assets for consumption.
     */
    private function publishAssets(): void
    {
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'images-migrations');

        $this->publishes([
            __DIR__.'/../config/images.php' => config_path('images.php'),
        ], 'images-config');
    }
}
