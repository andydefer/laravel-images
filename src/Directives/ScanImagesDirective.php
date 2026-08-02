<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\LaravelImages\Contracts\Storage\ImageStorageInterface;
use AndyDefer\LaravelImages\Enums\ImageExtension;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * CLI directive for scanning images in a directory and generating JSON/Array output.
 *
 * This directive recursively scans a directory for images, extracts metadata
 * (path, filename, size, dimensions, MIME type, etc.), and exports the results
 * as either JSON or a PHP array file.
 *
 * The path structure preserves the original directory hierarchy relative to the source.
 *
 * @example
 * // Basic scan with JSON output
 * ./bin/app images:scan images
 *
 * // Scan with custom depth and PHP array output
 * ./bin/app images:scan images 2 array
 *
 * // Scan with extension filtering and exclusions
 * ./bin/app images:scan images 0 json png jpg compressed thumbnails
 *
 * // Scan with hash generation
 * ./bin/app images:scan images --hash
 *
 * // Scan with custom output file
 * ./bin/app images:scan images custom/scan-result.json 2
 *
 * // Using alias
 * ./bin/app ims images
 */
final class ScanImagesDirective extends AbstractDirective
{
    private const DEFAULT_OUTPUT_FORMAT = 'json';

    private FileSystemInterface $fileSystem;

    private ImageStorageInterface $storage;

    /**
     * {@inheritDoc}
     */
    public function getSignature(): string
    {
        return 'images:scan 
                {source}#"Source directory to scan for images" 
                {output-file=?}#"Custom output file path (relative to storage or absolute)" 
                {depth=0}#"Maximum depth to scan (0 = unlimited)" 
                {::output->[json,array]=json}#"Output format" 
                {extensions*}#"Image extensions to include (png, jpg, webp, ...)" 
                {excludes*}#"Directories to exclude from scan" 
                {--hash}#"Include MD5 hash of each image" 
                {--exclude-compressed}#"Exclude images already compressed"';
    }

    public function getAliases(): StringTypedCollection
    {
        return StringTypedCollection::from(['ims']);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Scan images in a directory and generate JSON/Array output with metadata';
    }

    /**
     * {@inheritDoc}
     */
    protected function beforeExecute(): void
    {
        $this->info('🔍 Scanning images...');
        $this->newLine();

        $this->initializeServices();
        $this->ensureSourceExists($this->getArgument('source'));
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(): ExitCode
    {
        $source = $this->getArgument('source');
        $config = $this->buildScanConfig();

        $this->info("📁 Scanning: {$source}");
        $this->newLine();

        $images = $this->scanImages($source, $config);

        if ($images->isEmpty()) {
            $this->getConsole()->alertWarning('⚠️ No images found');

            return ExitCode::SUCCESS;
        }

        $this->info("📊 Found: {$images->count()} images");
        $this->newLine();

        $output = $this->formatOutput($images, $config);
        $filePath = $this->saveOutput($output, $config);

        $this->info("💾 Output saved to: {$filePath}");

        return ExitCode::SUCCESS;
    }

    /**
     * {@inheritDoc}
     */
    protected function afterExecute(ExitCode $exitCode): void
    {
        $this->newLine();
        $this->info('✅ Scan completed');
    }

    private function initializeServices(): void
    {
        $app = $this->getApplication();
        $this->fileSystem = $app->make(FileSystemInterface::class);
        $this->storage = $app->make(ImageStorageInterface::class);
    }

    private function ensureSourceExists(string $source): void
    {
        $fullPath = $this->storage->getFullPath($source);

        if ($this->fileSystem->exists($fullPath)) {
            $this->info("✅ Source directory: {$source}");

            return;
        }

        if ($this->fileSystem->exists($source)) {
            $this->info("✅ Source directory: {$source}");

            return;
        }

        $this->error("❌ Source directory not found: {$source}");
        throw new RuntimeException("Source directory not found: {$source}");
    }

    /**
     * Builds the scan configuration from CLI arguments.
     *
     * @return array{
     *     output: string,
     *     depth: int,
     *     extensions: array<string>,
     *     excludes: array<string>,
     *     outputFile: string|null,
     *     hash: bool,
     *     excludeCompressed: bool
     * }
     */
    private function buildScanConfig(): array
    {
        $extensions = $this->getVariadic('extensions');
        $excludes = $this->getVariadic('excludes');

        if (count($extensions) === 1 && str_contains($extensions[0], ',')) {
            $extensions = array_map('trim', explode(',', $extensions[0]));
        }

        if (count($excludes) === 1 && str_contains($excludes[0], ',')) {
            $excludes = array_map('trim', explode(',', $excludes[0]));
        }

        return [
            'output' => $this->getArgument('output') ?? self::DEFAULT_OUTPUT_FORMAT,
            'depth' => (int) ($this->getArgument('depth') ?? 0),
            'extensions' => ! empty($extensions) ? array_map('strtolower', $extensions) : [],
            'excludes' => $excludes ?? [],
            'outputFile' => $this->getArgument('output-file'),
            'hash' => $this->getFlag('hash'),
            'excludeCompressed' => $this->getFlag('exclude-compressed'),
        ];
    }

    private function scanImages(string $source, array $config): Collection
    {
        $basePath = $this->resolveBasePath($source);
        $files = $this->findImages($basePath, $config);

        $images = new Collection;

        foreach ($files as $file) {
            $imageData = $this->extractImageData($file, $config, $basePath);

            if ($imageData !== null) {
                $images->add($imageData);
            }
        }

        return $images;
    }

    private function resolveBasePath(string $source): string
    {
        $storagePath = $this->storage->getFullPath($source);

        if ($this->fileSystem->exists($storagePath)) {
            return $storagePath;
        }

        if ($this->fileSystem->exists($source)) {
            return $source;
        }

        return $storagePath;
    }

    /**
     * Finds image files in the directory with applied filters.
     *
     * @param  string  $directory  The base directory to scan
     * @param array{
     *     extensions: array<string>,
     *     excludes: array<string>,
     *     depth: int,
     *     excludeCompressed: bool
     * } $config Scan configuration
     * @return array<int, string> List of absolute file paths
     */
    private function findImages(string $directory, array $config): array
    {
        $extensions = $this->getExtensions($config);
        $excludedDirectories = $config['excludes'];
        $maxDepth = (int) $config['depth'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $foundFiles = [];

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();

            if ($this->isPathExcluded($path, $excludedDirectories)) {
                continue;
            }

            $extension = strtolower($file->getExtension());

            if ($config['excludeCompressed'] && $this->isCompressedPath($path)) {
                continue;
            }

            if (! empty($extensions) && ! in_array($extension, $extensions, true)) {
                continue;
            }

            if ($maxDepth > 0 && $iterator->getDepth() > $maxDepth) {
                continue;
            }

            $foundFiles[] = $path;
        }

        return $foundFiles;
    }

    private function isPathExcluded(string $path, array $excludedDirectories): bool
    {
        foreach ($excludedDirectories as $excluded) {
            if (str_contains($path, DIRECTORY_SEPARATOR.$excluded.DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    private function getExtensions(array $config): array
    {
        if (! empty($config['extensions'])) {
            return $config['extensions'];
        }

        return array_map(
            fn (ImageExtension $ext): string => strtolower($ext->value),
            ImageExtension::getSupportedExtensions()
        );
    }

    private function isCompressedPath(string $path): bool
    {
        return str_contains($path, '/compressed/')
            || str_contains($path, '/thumbnails/');
    }

    /**
     * Extracts metadata from an image file preserving directory structure.
     *
     * @param  string  $file  Absolute path to the image file
     * @param  array<string, mixed>  $config  Scan configuration
     * @param  string  $basePath  Base path to strip from the relative path
     * @return array<string, mixed>|null Image metadata or null on error
     */
    private function extractImageData(string $file, array $config, string $basePath): ?array
    {
        try {
            $relativePath = $this->getRelativePath($file, $basePath);
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $imageExtension = ImageExtension::tryFrom($extension);
            $dimensions = $this->getImageDimensions($file);

            $data = [
                'path' => $relativePath,
                'filename' => basename($file),
                'original_filename' => basename($file),
                'extension' => $extension,
                'mime_type' => $imageExtension?->getMimeType()->value,
                'size' => $this->fileSystem->size($file),
                'width' => $dimensions['width'] ?? null,
                'height' => $dimensions['height'] ?? null,
            ];

            if ($config['hash']) {
                $data['hash'] = md5_file($file);
            }

            return $data;
        } catch (\Exception $e) {
            $this->warn("⚠️ Error processing: {$file} - ".$e->getMessage());

            return null;
        }
    }

    private function getImageDimensions(string $file): array
    {
        $dimensions = @getimagesize($file);

        if ($dimensions === false) {
            return ['width' => null, 'height' => null];
        }

        return [
            'width' => $dimensions[0],
            'height' => $dimensions[1],
        ];
    }

    private function formatOutput(Collection $images, array $config): string
    {
        $output = $config['output'];

        return match ($output) {
            'array' => $this->formatAsPhpArray($images),
            default => json_encode($images->toArray(), JSON_UNESCAPED_SLASHES),
        };
    }

    private function formatAsPhpArray(Collection $images): string
    {
        $outputLines = ['<?php', '', 'return ['];

        foreach ($images as $image) {
            $outputLines[] = '    [';

            foreach ($image as $key => $value) {
                $formattedValue = $this->formatValueForPhpArray($value);
                $outputLines[] = "        '{$key}' => {$formattedValue},";
            }

            $outputLines[] = '    ],';
        }

        $outputLines[] = '];';

        return implode("\n", $outputLines);
    }

    private function formatValueForPhpArray(mixed $value): string
    {
        return match (true) {
            is_array($value) => json_encode($value, JSON_UNESCAPED_SLASHES),
            is_string($value) => "'{$value}'",
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => (string) $value,
        };
    }

    private function saveOutput(string $output, array $config): string
    {
        $outputFormat = $config['output'];
        $customOutputFile = $config['outputFile'];

        // Si un fichier de sortie personnalisé est fourni, l'utiliser
        if ($customOutputFile !== null && $customOutputFile !== '') {
            $path = $this->resolveOutputPath($customOutputFile, $outputFormat);

            // Créer le dossier parent si nécessaire
            $this->fileSystem->ensureDirectoryExists(dirname($path));

            // Écrire le fichier
            $this->fileSystem->put($path, $output);

            return $path;
        }

        // Sinon, utiliser le comportement par défaut
        $extension = $outputFormat === 'array' ? 'php' : 'json';
        $filename = 'scan_result_'.date('Y-m-d_H-i-s').'.'.$extension;
        $path = storage_path('app/public/'.$filename);

        $this->fileSystem->ensureDirectoryExists(dirname($path));
        $this->fileSystem->put($path, $output);

        return $path;
    }

    /**
     * Résout le chemin de sortie personnalisé.
     *
     * @param  string  $path  Le chemin personnalisé (relatif ou absolu)
     * @param  string  $format  Le format de sortie (json, array)
     * @return string Le chemin absolu résolu
     */
    private function resolveOutputPath(string $path, string $format): string
    {
        // Si le chemin est déjà absolu, le retourner
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Si le chemin est relatif et commence par storage/, le résoudre
        if (str_starts_with($path, 'storage/')) {
            return base_path($path);
        }

        // Si le chemin est relatif, le placer dans storage/app/public/
        return storage_path('app/public/'.$path);
    }

    /**
     * Gets the relative path of a file while preserving directory structure.
     *
     * @param  string  $file  Absolute path to the file
     * @param  string  $basePath  Base path to strip
     * @return string Relative path preserving directory structure
     */
    private function getRelativePath(string $file, string $basePath): string
    {
        if (str_starts_with($file, $basePath)) {
            return ltrim(substr($file, strlen($basePath)), DIRECTORY_SEPARATOR);
        }

        return $file;
    }
}
