<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\LaravelUtils\Enums\ImageExtension;
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
 * ./bin/app images:scan images scan-result.json
 *
 * // Scan with custom depth and PHP array output
 * ./bin/app images:scan images scan-result.php 2
 *
 * // Scan with extension filtering and exclusions
 * ./bin/app images:scan images scan-result.json 0 png jpg compressed thumbnails
 *
 * // Scan with hash generation
 * ./bin/app images:scan images scan-result.json --hash
 *
 * // Scan with relative paths
 * ./bin/app images:scan images scan-result.json --relative
 *
 * // Using alias
 * ./bin/app ims images scan-result.json
 */
final class ScanImagesDirective extends AbstractDirective
{
    private FileSystemInterface $fileSystem;

    /**
     * {@inheritDoc}
     */
    public function getSignature(): string
    {
        return 'images:scan 
                {source}#"Source directory to scan for images" 
                {output}#"Output file path" 
                {depth=0}#"Maximum depth to scan (0 = unlimited)" 
                {extensions*}#"Image extensions to include (png, jpg, webp, ...)" 
                {excludes*}#"Directories to exclude from scan" 
                {--hash}#"Include MD5 hash of each image" 
                {--relative}#"Make paths relative to source directory"';
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
    }

    private function ensureSourceExists(string $source): void
    {
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
     *     outputPath: string,
     *     hash: bool,
     *     relative: bool
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

        $outputPath = $this->getArgument('output');

        // Déterminer le format à partir de l'extension du fichier
        $extension = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));

        // Vérifier que l'extension est supportée
        if (! in_array($extension, ['json', 'php'])) {
            $this->error("❌ Unsupported file format: .{$extension}");
            $this->line('💡 Supported formats: .json and .php');
            throw new RuntimeException("Unsupported file format: .{$extension}. Please use .json or .php");
        }

        $outputFormat = $extension === 'php' ? 'array' : 'json';

        return [
            'output' => $outputFormat,
            'depth' => (int) ($this->getArgument('depth') ?? 0),
            'extensions' => ! empty($extensions) ? array_map('strtolower', $extensions) : [],
            'excludes' => $excludes ?? [],
            'outputPath' => $outputPath,
            'hash' => $this->getFlag('hash'),
            'relative' => $this->getFlag('relative'),
        ];
    }

    private function scanImages(string $source, array $config): Collection
    {
        $files = $this->findImages($source, $config);

        $images = new Collection;

        foreach ($files as $file) {
            $imageData = $this->extractImageData($file, $config);

            if ($imageData !== null) {
                $images->add($imageData);
            }
        }

        return $images;
    }

    /**
     * Finds image files in the directory with applied filters.
     *
     * @param  string  $directory  The base directory to scan
     * @param array{
     *     extensions: array<string>,
     *     excludes: array<string>,
     *     depth: int
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

    /**
     * Extracts metadata from an image file preserving directory structure.
     *
     * @param  string  $file  Absolute path to the image file
     * @param  array<string, mixed>  $config  Scan configuration
     * @return array<string, mixed>|null Image metadata or null on error
     */
    private function extractImageData(string $file, array $config): ?array
    {
        try {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $imageExtension = ImageExtension::tryFrom($extension);
            $dimensions = $this->getImageDimensions($file);

            $path = $file;

            // Si le flag --relative est activé, rendre le chemin relatif à la source
            if ($config['relative']) {
                $source = $this->getArgument('source');
                $source = rtrim($source, '/');
                if (str_starts_with($file, $source.'/')) {
                    $path = ltrim(substr($file, strlen($source) + 1), '/');
                }
            }

            $data = [
                'path' => $path,
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
            $this->getConsole()->alertWarning("⚠️ Error processing: {$file} - ".$e->getMessage());

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
            default => json_encode($images->toArray(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
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
        $outputPath = $config['outputPath'];

        if ($outputPath !== null && $outputPath !== '') {
            $this->fileSystem->ensureDirectoryExists(dirname($outputPath));
            $this->fileSystem->put($outputPath, $output);

            return $outputPath;
        }

        $outputFormat = $config['output'];
        $extension = $outputFormat === 'array' ? 'php' : 'json';
        $filename = 'scan_result_'.date('Y-m-d_H-i-s').'.'.$extension;

        $this->fileSystem->put($filename, $output);

        return $filename;
    }
}
