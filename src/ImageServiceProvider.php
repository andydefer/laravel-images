<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages;

use AndyDefer\LaravelImages\Configs\ImagesConfig;
use AndyDefer\LaravelImages\Contracts\Configs\ImagesConfigInterface;
use AndyDefer\LaravelImages\Contracts\Processors\ImageProcessorInterface;
use AndyDefer\LaravelImages\Contracts\Repositories\AlbumRepositoryInterface;
use AndyDefer\LaravelImages\Contracts\Repositories\ImageRepositoryInterface;
use AndyDefer\LaravelImages\Contracts\Services\AlbumServiceInterface;
use AndyDefer\LaravelImages\Contracts\Services\ImageServiceInterface;
use AndyDefer\LaravelImages\Contracts\Storage\ImageStorageInterface;
use AndyDefer\LaravelImages\Factories\ImageProcessorFactory;
use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Observers\AlbumObserver;
use AndyDefer\LaravelImages\Observers\ImageObserver;
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
 */
final class ImageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
        $this->registerFileSystem();
        $this->registerStorage();
        $this->registerRepositories();
        $this->registerImageProcessor();
        $this->registerServices();
    }

    public function boot(): void
    {
        $this->registerObservers();
        $this->loadMigrations();
        $this->publishAssets();
    }

    private function registerConfig(): void
    {
        $this->app->singleton(
            ImagesConfigInterface::class,
            fn ($app): ImagesConfig => new ImagesConfig($app['config'])
        );
    }

    private function registerFileSystem(): void
    {
        $this->app->singleton(
            FileSystemInterface::class,
            FileSystemService::class
        );
    }

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

    private function registerRepositories(): void
    {
        $this->app->singleton(ImageRepository::class);
        $this->app->singleton(AlbumRepository::class);

        $this->app->bind(
            ImageRepositoryInterface::class,
            ImageRepository::class
        );

        $this->app->bind(
            AlbumRepositoryInterface::class,
            AlbumRepository::class
        );
    }

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

    private function registerServices(): void
    {
        $this->app->singleton(
            ImageServiceInterface::class,
            ImageService::class
        );

        $this->app->singleton(
            ImageService::class,
            function ($app): ImageService {
                return new ImageService(
                    $app->make(ImageRepositoryInterface::class),
                    $app->make(ImageProcessorInterface::class),
                    $app->make(ImageStorageInterface::class)
                );
            }
        );

        $this->app->singleton(
            AlbumServiceInterface::class,
            AlbumService::class
        );

        $this->app->singleton(
            AlbumService::class,
            function ($app): AlbumService {
                return new AlbumService(
                    $app->make(AlbumRepositoryInterface::class),
                    $app->make(ImageService::class)
                );
            }
        );
    }

    private function registerObservers(): void
    {
        Album::observe(AlbumObserver::class);
        Image::observe(ImageObserver::class);
    }

    private function loadMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

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
