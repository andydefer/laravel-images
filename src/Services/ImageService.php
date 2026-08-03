<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Services;

use AndyDefer\LaravelImages\Contracts\Processors\ImageProcessorInterface;
use AndyDefer\LaravelImages\Contracts\Services\ImageServiceInterface;
use AndyDefer\LaravelImages\Contracts\Storage\ImageStorageInterface;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Records\ImageFilterRecord;
use AndyDefer\LaravelImages\Records\ImageOptionsRecord;
use AndyDefer\LaravelImages\Records\ImageRecord;
use AndyDefer\LaravelImages\Repositories\ImageRepository;
use AndyDefer\LaravelImages\ValueObjects\ImageMetadataVO;
use AndyDefer\LaravelImages\ValueObjects\ImagePathVO;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;
use AndyDefer\Repository\Records\FindByRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Orchestrates image management operations including upload, storage,
 * thumbnail generation, and relational handling.
 *
 * This service acts as the primary entry point for all image operations,
 * coordinating between storage, processing, and repository layers.
 */
final class ImageService implements ImageServiceInterface
{
    private const DEFAULT_THUMBNAIL_SIZE = 'small';

    private const DEFAULT_ORDER = 0;

    private const DEFAULT_IS_PRIMARY = false;

    public function __construct(
        private readonly ImageRepository $imageRepository,
        private readonly ImageProcessorInterface $imageProcessor,
        private readonly ImageStorageInterface $storage,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function findImage(string $id): ?Image
    {
        $filter = ImageFilterRecord::from(['id' => $id]);
        $query = new FindByRecord(filters: $filter, limit: 1);

        return $this->imageRepository->findBy($query)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function upload(
        UploadedFile $file,
        Model $imageable,
        ?Model $uploadedBy = null,
        ImageType $type = ImageType::GALLERY,
        ?ImageOptionsRecord $options = null,
    ): Image {
        $this->validateFile($file, $type);

        $options ??= new ImageOptionsRecord;
        $storagePath = $this->storeFile($file, $imageable, $type);
        $dimensions = $this->extractImageDimensions($file);
        $metadata = $this->buildMetadataFromOptions($options);

        $imageRecord = $this->buildImageRecord(
            file: $file,
            storagePath: $storagePath,
            imageable: $imageable,
            uploadedBy: $uploadedBy,
            type: $type,
            options: $options,
            metadata: $metadata,
            dimensions: $dimensions,
        );

        $createdImage = $this->imageRepository->create($imageRecord);

        if ($options->generate_thumbnails ?? true) {
            $this->generateThumbnails($storagePath, $type);
        }

        return $this->markImageAsProcessed($createdImage->id);
    }

    /**
     * {@inheritDoc}
     */
    public function uploadMultiple(
        array $files,
        Model $imageable,
        ?Model $uploadedBy = null,
        ImageType $type = ImageType::GALLERY,
        ?ImageOptionsRecord $options = null,
    ): Collection {
        $results = new Collection;

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $imageOptions = $this->resolveImageOptionsForMultiple($options, $index);
            $image = $this->upload($file, $imageable, $uploadedBy, $type, $imageOptions);
            $results->add($image);
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function update(ImageRecord $record, string $id): Image
    {
        return $this->imageRepository->update($id, $record);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id, bool $deleteFile = true): void
    {
        $image = $this->findImage($id);

        if ($image === null) {
            throw new RuntimeException("Image not found: {$id}");
        }

        if ($deleteFile) {
            $this->deletePhysicalFile($image->path);
            $this->deleteThumbnails($image->path);
        }

        $this->imageRepository->delete($id);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple(array $ids, bool $deleteFile = true): void
    {
        $filter = ImageFilterRecord::from(['ids' => $ids]);
        $images = $this->imageRepository->findBy(new FindByRecord(filters: $filter));

        foreach ($images as $image) {
            if ($deleteFile) {
                $this->deletePhysicalFile($image->path);
                $this->deleteThumbnails($image->path);
            }
        }

        $this->imageRepository->deleteBulk($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAllForModel(Model $model, bool $deleteFile = true): void
    {
        $filter = ImageFilterRecord::from([
            'imageable_type' => $model->getMorphClass(),
            'imageable_id' => $model->getKey(),
        ]);

        $images = $this->imageRepository->findBy(new FindByRecord(filters: $filter));

        foreach ($images as $image) {
            if ($deleteFile) {
                $this->deletePhysicalFile($image->path);
                $this->deleteThumbnails($image->path);
            }
        }

        $this->imageRepository->deleteBulk($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function getImagesForModel(Model $model, ?ImageType $type = null): Collection
    {
        $filter = ImageFilterRecord::from([
            'imageable_type' => $model->getMorphClass(),
            'imageable_id' => $model->getKey(),
            'type' => $type,
        ]);

        return $this->imageRepository->findBy(new FindByRecord(filters: $filter));
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryImage(Model $model): ?Image
    {
        $filter = ImageFilterRecord::from([
            'imageable_type' => $model->getMorphClass(),
            'imageable_id' => $model->getKey(),
            'is_primary' => true,
        ]);

        return $this->imageRepository->findBy(
            new FindByRecord(filters: $filter, limit: 1),
        )->first();
    }

    /**
     * {@inheritDoc}
     */
    public function setAsPrimary(string $id, Model $model): void
    {
        $filter = ImageFilterRecord::from([
            'imageable_type' => $model->getMorphClass(),
            'imageable_id' => $model->getKey(),
        ]);

        $images = $this->imageRepository->findBy(new FindByRecord(filters: $filter));

        foreach ($images as $image) {
            $isPrimary = $image->id === $id;
            $this->imageRepository->update(
                $image->id,
                ImageRecord::from(['is_primary' => $isPrimary]),
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function countImages(Model $model, ?ImageType $type = null): int
    {
        $filter = ImageFilterRecord::from([
            'imageable_type' => $model->getMorphClass(),
            'imageable_id' => $model->getKey(),
            'type' => $type,
        ]);

        return $this->imageRepository->count($filter);
    }

    /**
     * {@inheritDoc}
     */
    public function getImagesUpdatedAfter(DateTimeVO $date): Collection
    {
        $filter = ImageFilterRecord::from(['updated_at' => $date]);

        return $this->imageRepository->findBy(new FindByRecord(filters: $filter));
    }

    /**
     * {@inheritDoc}
     */
    public function reorder(array $ids): void
    {
        foreach ($ids as $index => $id) {
            $this->imageRepository->update(
                $id,
                ImageRecord::from(['order' => $index + 1]),
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getThumbnailUrl(string $imageId, string $size = self::DEFAULT_THUMBNAIL_SIZE): string
    {
        $image = $this->findImage($imageId);

        if ($image === null) {
            throw new RuntimeException("Image not found: {$imageId}");
        }

        $thumbnailPath = $image->path->getThumbnailPath($size);

        return $this->getPublicUrl($thumbnailPath);
    }

    /**
     * {@inheritDoc}
     */
    public function getImageProcessor(): ImageProcessorInterface
    {
        return $this->imageProcessor;
    }

    /**
     * {@inheritDoc}
     */
    public function getStorage(): ImageStorageInterface
    {
        return $this->storage;
    }

    /**
     * {@inheritDoc}
     */
    public function syncInverseRelation(Image $image): void
    {
        $variantType = $this->detectImageVariant($image->original_filename);

        if ($variantType === null) {
            return;
        }

        $inverseImage = $this->findInverseImage($image, $variantType);

        if ($inverseImage !== null && $inverseImage->id !== $image->id) {
            $this->linkImages($image, $inverseImage);
        } elseif ($image->inverse_image_id !== null) {
            $this->clearInverseRelation($image);
        }
    }

    /**
     * Removes the physical image file from storage.
     */
    private function deletePhysicalFile(ImagePathVO $imagePath): void
    {
        $this->storage->delete($imagePath->getFullPath());
    }

    /**
     * Marks an image as processed after successful thumbnail generation.
     */
    private function markImageAsProcessed(string $imageId): Image
    {
        $processedRecord = ImageRecord::from(['is_processed' => true]);

        return $this->imageRepository->update($imageId, $processedRecord);
    }

    /**
     * Resolves image options for batch uploads, ensuring proper ordering.
     */
    private function resolveImageOptionsForMultiple(?ImageOptionsRecord $options, int $index): ImageOptionsRecord
    {
        if ($options === null || $options->order === null) {
            return new ImageOptionsRecord(
                order: $index + 1,
                alt_text: $options?->alt_text,
                caption: $options?->caption,
                metadata: $options?->metadata,
                is_primary: $options?->is_primary,
                width: $options?->width,
                height: $options?->height,
                generate_thumbnails: $options?->generate_thumbnails,
            );
        }

        return $options;
    }

    /**
     * Builds metadata from the provided options.
     */
    private function buildMetadataFromOptions(ImageOptionsRecord $options): ?ImageMetadataVO
    {
        $data = $options->metadata?->toArray() ?? [];

        if ($options->alt_text !== null) {
            $data['alt_text'] = $options->alt_text;
        }

        if ($options->caption !== null) {
            $data['caption'] = $options->caption;
        }

        return ! empty($data) ? new ImageMetadataVO($data) : null;
    }

    /**
     * Constructs a complete ImageRecord from upload parameters.
     */
    private function buildImageRecord(
        UploadedFile $file,
        string $storagePath,
        Model $imageable,
        ?Model $uploadedBy,
        ImageType $type,
        ImageOptionsRecord $options,
        ?ImageMetadataVO $metadata,
        array $dimensions,
    ): ImageRecord {
        return ImageRecord::from([
            'path' => $storagePath,
            'filename' => $file->hashName(),
            'original_filename' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'size' => $this->getFileSize($file),
            'width' => $dimensions['width'] ?? null,
            'height' => $dimensions['height'] ?? null,
            'type' => $type,
            'metadata' => $metadata,
            'order' => $options->order ?? self::DEFAULT_ORDER,
            'is_primary' => $options->is_primary ?? self::DEFAULT_IS_PRIMARY,
            'is_processed' => false,
            'imageable_type' => $imageable->getMorphClass(),
            'imageable_id' => $imageable->getKey(),
            'uploaded_by_type' => $uploadedBy?->getMorphClass(),
            'uploaded_by_id' => $uploadedBy?->getKey(),
        ]);
    }

    /**
     * Extracts image dimensions from an uploaded file.
     *
     * @return array{width: int|null, height: int|null}
     */
    private function extractImageDimensions(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();

        if ($realPath === false || ! file_exists($realPath)) {
            return ['width' => null, 'height' => null];
        }

        $dimensions = getimagesize($realPath);

        return [
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
        ];
    }

    /**
     * Gets the file size with fallback for test environments.
     */
    private function getFileSize(UploadedFile $file): int
    {
        try {
            return $file->getSize();
        } catch (RuntimeException) {
            $realPath = $file->getRealPath();

            if ($realPath !== false && file_exists($realPath)) {
                return filesize($realPath);
            }

            return 0;
        }
    }

    /**
     * Generates thumbnails for the uploaded image based on type configuration.
     */
    private function generateThumbnails(string $path, ImageType $type): void
    {
        $imagePath = new ImagePathVO($path);
        $sizes = $type->getThumbnailSizes();

        foreach ($sizes as $dimensions) {
            $this->imageProcessor->resize(
                $imagePath,
                $dimensions['width'],
                $dimensions['height'],
            );
        }
    }

    /**
     * Deletes all associated thumbnails for an image.
     */
    private function deleteThumbnails(ImagePathVO $imagePath): void
    {
        $files = $this->storage->files($imagePath->getDirectory());

        foreach ($files as $file) {
            if ($imagePath->isThumbnail($file)) {
                $this->storage->delete($file);
            }
        }
    }

    /**
     * Stores the uploaded file in the configured storage.
     */
    private function storeFile(UploadedFile $file, Model $imageable, ImageType $type): string
    {
        $path = $this->buildStoragePath($imageable, $type);
        $filename = $file->hashName();

        return $this->storage->store($file, $path, $filename);
    }

    /**
     * Builds the storage path for an image based on the parent model and type.
     */
    private function buildStoragePath(Model $imageable, ImageType $type): string
    {
        return sprintf(
            '%s/%s/%s',
            $imageable->getMorphClass(),
            $imageable->getKey(),
            $type->value,
        );
    }

    /**
     * Gets the public URL for a given storage path.
     */
    private function getPublicUrl(string $path): string
    {
        return asset('storage/'.$path);
    }

    /**
     * Validates the uploaded file against type-specific constraints.
     *
     * @throws RuntimeException When file validation fails
     */
    private function validateFile(UploadedFile $file, ImageType $type): void
    {
        $maxSize = $type->getMaxSize();
        $allowedMimes = $type->getAllowedMimeTypes();

        $fileSizeInKB = $this->getFileSize($file) / 1024;

        if ($fileSizeInKB > $maxSize) {
            throw new RuntimeException(
                sprintf('File size exceeds limit of %s KB', $maxSize),
            );
        }

        $fileMimeType = $file->getMimeType();

        if (! in_array($fileMimeType, $allowedMimes, true)) {
            throw new RuntimeException(
                sprintf('MIME type %s not allowed', $fileMimeType),
            );
        }
    }

    /**
     * Detects if an image filename indicates a light or dark variant.
     *
     * @return 'light'|'dark'|null The variant type, or null if none detected
     */
    private function detectImageVariant(string $filename): ?string
    {
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        if (str_ends_with($basename, '-light')) {
            return 'light';
        }

        if (str_ends_with($basename, '-dark')) {
            return 'dark';
        }

        return null;
    }

    /**
     * Finds the inverse image (light ↔ dark) based on the current image.
     *
     * @param  'light'|'dark'  $variantType
     */
    private function findInverseImage(Image $image, string $variantType): ?Image
    {
        $basename = pathinfo($image->original_filename, PATHINFO_FILENAME);
        $extension = pathinfo($image->original_filename, PATHINFO_EXTENSION);

        $inverseBasename = $variantType === 'light'
            ? str_replace('-light', '-dark', $basename)
            : str_replace('-dark', '-light', $basename);

        $inverseFilename = $inverseBasename.'.'.$extension;

        $filter = ImageFilterRecord::from([
            'imageable_type' => $image->imageable_type,
            'imageable_id' => $image->imageable_id,
            'type' => $image->type,
            'search' => $inverseFilename,
        ]);

        $findByRecord = new FindByRecord(filters: $filter, limit: 1);

        return $this->imageRepository->findBy($findByRecord)->first();
    }

    /**
     * Establishes a bidirectional inverse relationship between two images.
     */
    private function linkImages(Image $image, Image $inverseImage): void
    {
        if ($image->inverse_image_id !== $inverseImage->id) {
            $image->inverse_image_id = $inverseImage->id;
            $image->saveQuietly();
        }

        if ($inverseImage->inverse_image_id !== $image->id) {
            $inverseImage->inverse_image_id = $image->id;
            $inverseImage->saveQuietly();
        }
    }

    /**
     * Clears the inverse relationship when the counterpart no longer exists.
     */
    private function clearInverseRelation(Image $image): void
    {
        $image->inverse_image_id = null;
        $image->saveQuietly();
    }
}
