<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\LaravelImages\Contracts\Storage\ImageStorageInterface;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use Illuminate\Support\Collection;
use Override;
use Symfony\Component\Process\Process;

/**
 * Directive pour compresser les images (PNG, JPG, JPEG).
 *
 * Utilise les outils système : pngquant et jpegoptim.
 */
final class CompressImagesDirective extends AbstractDirective
{
    private FileSystemInterface $fileSystem;

    private ImageStorageInterface $storage;

    /**
     * {@inheritDoc}
     */
    public function getSignature(): string
    {
        return 'images:compress 
                {source}#"Source directory containing images to compress" 
                {destination=?}#"Destination directory (source directory if omitted)" 
                {png-quality=45-50}#"PNG quality range (min-max, e.g. 30-40)" 
                {jpg-quality=50}#"JPEG quality (0-100)" 
                {--strip-meta}#"Remove metadata (Exif, comments, etc.)" 
                {--recursive}#"Process subdirectories recursively" 
                {--dry-run}#"Simulate compression without modifying files" 
                {--force}#"Force overwrite existing files"';
    }

    #[Override]
    public function getAliases(): StringTypedCollection
    {
        return StringTypedCollection::from(['imc']);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Compress PNG and JPG/JPEG images using pngquant and jpegoptim';
    }

    /**
     * {@inheritDoc}
     */
    protected function beforeExecute(): void
    {
        $this->info('📷 Starting image compression...');
        $this->newLine();

        // Récupération des services via le conteneur
        $app = $this->getApplication();
        $this->fileSystem = $app->make(FileSystemInterface::class);
        $this->storage = $app->make(ImageStorageInterface::class);

        $source = $this->getArgument('source');
        $this->ensureSourceExists($source);

        $this->checkDependencies();
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(): ExitCode
    {
        $source = $this->getArgument('source');
        $destination = $this->getArgument('destination');
        $pngQuality = $this->getArgument('png-quality') ?? '45-50';
        $jpgQuality = (int) ($this->getArgument('jpg-quality') ?? 50);
        $stripMeta = $this->getFlag('strip-meta');
        $recursive = $this->getFlag('recursive');
        $dryRun = $this->getFlag('dry-run');
        $force = $this->getFlag('force');

        $destination = $destination ?? $source;

        $this->contextSet('processed_count', 0);
        $this->contextSet('total_size_before', 0);
        $this->contextSet('total_size_after', 0);
        $this->contextSet('errors', []);

        $files = $this->findImages($source, $recursive);

        if ($files->isEmpty()) {
            $this->getConsole()->alertWarning('⚠️ No images found to compress');

            return ExitCode::SUCCESS;
        }

        $this->info('📁 Found '.$files->count().' images to process');

        if ($dryRun) {
            $this->newLine();
            $this->info('📋 DRY RUN - No changes will be made');
            $this->newLine();
            $this->listFiles($files);

            return ExitCode::SUCCESS;
        }

        $this->ensureDestinationExists($destination);

        foreach ($files as $file) {
            $this->compressImage($file, $destination, $pngQuality, $jpgQuality, $stripMeta, $force);
        }

        $this->showSummary();

        return ExitCode::SUCCESS;
    }

    /**
     * {@inheritDoc}
     */
    protected function afterExecute(ExitCode $exitCode): void
    {
        $this->newLine();
        $this->info('✅ Compression completed');
    }

    /**
     * Vérifie que les outils nécessaires sont installés.
     */
    private function checkDependencies(): void
    {
        $tools = ['pngquant', 'jpegoptim'];
        $missing = [];

        foreach ($tools as $tool) {
            $process = new Process(['which', $tool]);
            $process->run();

            if (! $process->isSuccessful()) {
                $missing[] = $tool;
            }
        }

        if (! empty($missing)) {
            $this->error('❌ Required tools not installed: '.implode(', ', $missing));
            $this->line('📦 Install them with:');
            $this->line('   sudo apt install '.implode(' ', $missing));
            throw new \RuntimeException('Missing dependencies');
        }
    }

    /**
     * Vérifie que la source existe.
     */
    private function ensureSourceExists(string $source): void
    {
        $fullPath = $this->storage->getFullPath($source);

        if (! $this->fileSystem->exists($fullPath)) {
            $this->error("❌ Source directory not found: {$source}");
            throw new \RuntimeException("Source directory not found: {$source}");
        }

        $this->info("✅ Source directory: {$source}");
    }

    /**
     * Vérifie que la destination est accessible.
     */
    private function ensureDestinationExists(string $destination): void
    {
        $fullPath = $this->storage->getFullPath($destination);

        if (! $this->fileSystem->exists($fullPath)) {
            $this->fileSystem->ensureDirectoryExists($fullPath);
            $this->info("📁 Created destination directory: {$destination}");
        }
    }

    /**
     * Trouve les images dans la source.
     */
    private function findImages(string $source, bool $recursive): Collection
    {
        $fullPath = $this->storage->getFullPath($source);
        $files = [];

        if ($recursive) {
            $files = $this->findImagesRecursively($fullPath);
        } else {
            $pattern = $fullPath.'/*.{jpg,jpeg,png}';
            $files = glob($pattern, GLOB_BRACE) ?: [];
        }

        return collect($files)->filter(fn ($file) => is_file($file));
    }

    /**
     * Recherche récursive des images.
     */
    private function findImagesRecursively(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Liste les fichiers (pour dry-run).
     */
    private function listFiles(Collection $files): void
    {
        $this->line('📋 Files to compress:');
        $this->newLine();

        foreach ($files as $file) {
            $size = $this->fileSystem->size($file);
            $sizeHuman = $this->formatSize($size);
            $relative = $this->getRelativePath($file);
            $this->line("   • {$relative} ({$sizeHuman})");
        }

        $totalSize = $files->reduce(fn ($carry, $file) => $carry + $this->fileSystem->size($file), 0);
        $this->newLine();
        $this->line('📊 Total: '.$files->count().' files, '.$this->formatSize($totalSize));
    }

    /**
     * Compresse une image.
     */
    private function compressImage(
        string $file,
        string $destination,
        string $pngQuality,
        int $jpgQuality,
        bool $stripMeta,
        bool $force
    ): void {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $filename = basename($file);
        $relativePath = $this->getRelativePath($file);
        $destinationPath = $this->storage->getFullPath($destination.'/'.$filename);

        $sizeBefore = $this->fileSystem->size($file);

        if ($extension === 'png') {
            $this->compressPng($file, $destinationPath, $pngQuality, $force);
        } elseif (in_array($extension, ['jpg', 'jpeg'])) {
            $this->compressJpg($file, $destinationPath, $jpgQuality, $stripMeta, $force);
        }

        $sizeAfter = $this->fileSystem->exists($destinationPath) ? $this->fileSystem->size($destinationPath) : $sizeBefore;

        $this->contextSet('processed_count', $this->contextGet('processed_count') + 1);
        $this->contextSet('total_size_before', $this->contextGet('total_size_before') + $sizeBefore);
        $this->contextSet('total_size_after', $this->contextGet('total_size_after') + $sizeAfter);

        $saved = $sizeBefore - $sizeAfter;
        $savedPercent = $sizeBefore > 0 ? round(($saved / $sizeBefore) * 100, 1) : 0;

        if ($saved > 0) {
            $savedHuman = $this->formatSize($saved);
            $this->info("   ✅ {$relativePath} - saved {$savedHuman} ({$savedPercent}%)");
        } else {
            $this->line("   ⏭️  {$relativePath} - no size reduction");
        }
    }

    /**
     * Compresse une image PNG.
     */
    private function compressPng(string $source, string $destination, string $quality, bool $force): void
    {
        $args = [
            'pngquant',
            '--quality='.$quality,
            '--force',
            '--output',
            $destination,
            $source,
        ];

        if ($source === $destination && ! $force) {
            $tempFile = $destination.'.tmp';
            $args = [
                'pngquant',
                '--quality='.$quality,
                '--force',
                '--output',
                $tempFile,
                $source,
            ];

            $process = new Process($args);
            $process->run();

            if ($process->isSuccessful() && $this->fileSystem->exists($tempFile)) {
                $this->fileSystem->delete($source);
                $this->fileSystem->move($tempFile, $destination);
            }
        } else {
            $process = new Process($args);
            $process->run();
        }

        if (! $process->isSuccessful() && $process->getErrorOutput()) {
            $this->warn("⚠️ Error compressing {$source}: ".$process->getErrorOutput());
        }
    }

    /**
     * Compresse une image JPG/JPEG.
     */
    private function compressJpg(string $source, string $destination, int $quality, bool $stripMeta, bool $force): void
    {
        $args = [
            'jpegoptim',
            '--max='.$quality,
            '--dest='.dirname($destination),
        ];

        if ($stripMeta) {
            $args[] = '--strip-all';
        }

        if ($force) {
            $args[] = '--force';
        }

        $args[] = $source;

        $process = new Process($args);
        $process->run();

        if ($source !== $destination && $process->isSuccessful()) {
            // jpegoptim écrase le fichier source, on vérifie si le fichier a été créé
            $sourceSize = $this->fileSystem->size($source);
            $destSize = $this->fileSystem->exists($destination) ? $this->fileSystem->size($destination) : 0;

            // Si le fichier source est plus petit que la destination, on le garde
            if ($sourceSize < $destSize) {
                $this->fileSystem->delete($destination);
                $this->fileSystem->copy($source, $destination);
            }
        }
    }

    /**
     * Affiche le résumé de la compression.
     */
    private function showSummary(): void
    {
        $count = $this->contextGet('processed_count');
        $sizeBefore = $this->contextGet('total_size_before');
        $sizeAfter = $this->contextGet('total_size_after');
        $saved = $sizeBefore - $sizeAfter;
        $savedPercent = $sizeBefore > 0 ? round(($saved / $sizeBefore) * 100, 1) : 0;

        $this->newLine();
        $this->line('📊 Summary:');
        $this->line("   📁 Files processed: {$count}");
        $this->line('   📦 Size before: '.$this->formatSize($sizeBefore));
        $this->line('   📦 Size after: '.$this->formatSize($sizeAfter));
        $this->line('   💾 Space saved: '.$this->formatSize($saved)." ({$savedPercent}%)");
    }

    /**
     * Formate une taille en bytes en format lisible.
     */
    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Obtient le chemin relatif d'un fichier.
     */
    private function getRelativePath(string $file): string
    {
        $basePath = storage_path('app/public/');

        return str_replace($basePath, '', $file);
    }
}
