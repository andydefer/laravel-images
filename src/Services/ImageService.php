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
 * Service for managing images.
 *
 * Provides comprehensive image management including upload, deletion,
 * retrieval, reordering, and thumbnail generation.
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
    public function findImage(int $id): ?Image
    {
        $filter = ImageFilterRecord::from(['id' => $id]);
        $findByRecord = new FindByRecord(filters: $filter, limit: 1);

        return $this->imageRepository->findBy($findByRecord)->first();
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

        $storagePath = $this->storeFile($file, $imageable, $type);
        $dimensions = $this->extractImageDimensions($file);
        $options = $options ?? new ImageOptionsRecord;

        $metadata = $this->buildMetadataFromOptions($options);

        $imageRecord = ImageRecord::from([
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

            $imageOptions = $this->resolveImageOptions($options, $index);
            $image = $this->upload($file, $imageable, $uploadedBy, $type, $imageOptions);
            $results->add($image);
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function update(ImageRecord $record, int $id): Image
    {
        return $this->imageRepository->update($id, $record);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id, bool $deleteFile = true): void
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
            new FindByRecord(filters: $filter, limit: 1)
        )->first();
    }

    /**
     * {@inheritDoc}
     */
    public function setAsPrimary(int $id, Model $model): void
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
                ImageRecord::from(['is_primary' => $isPrimary])
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
                ImageRecord::from(['order' => $index + 1])
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getThumbnailUrl(int $imageId, string $size = self::DEFAULT_THUMBNAIL_SIZE): string
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
     * Deletes a physical file from storage.
     */
    private function deletePhysicalFile(ImagePathVO $imagePath): void
    {
        $this->storage->delete($imagePath->getFullPath());
    }

    /**
     * Marks an image as processed after thumbnail generation.
     */
    private function markImageAsProcessed(int $imageId): Image
    {
        $processedRecord = ImageRecord::from(['is_processed' => true]);

        return $this->imageRepository->update($imageId, $processedRecord);
    }

    /**
     * Resolves image options for upload multiple.
     */
    private function resolveImageOptions(?ImageOptionsRecord $options, int $index): ImageOptionsRecord
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
     * Builds metadata from options.
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
        } catch (RuntimeException $e) {
            $realPath = $file->getRealPath();

            if ($realPath !== false && file_exists($realPath)) {
                return filesize($realPath);
            }

            return 0;
        }
    }

    /**
     * Generates thumbnails for an image.
     */
    private function generateThumbnails(string $path, ImageType $type): void
    {
        $imagePath = new ImagePathVO($path);
        $sizes = $type->getThumbnailSizes();

        foreach ($sizes as $dimensions) {
            $this->imageProcessor->resize(
                $imagePath,
                $dimensions['width'],
                $dimensions['height']
            );
        }
    }

    /**
     * Deletes thumbnails for an image.
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
     * Stores the uploaded file.
     */
    private function storeFile(UploadedFile $file, Model $imageable, ImageType $type): string
    {
        $path = $this->buildStoragePath($imageable, $type);
        $filename = $file->hashName();

        return $this->storage->store($file, $path, $filename);
    }

    /**
     * Builds the storage path for an image.
     */
    private function buildStoragePath(Model $imageable, ImageType $type): string
    {
        return $imageable->getMorphClass()
            .'/'.$imageable->getKey()
            .'/'.$type->value;
    }

    /**
     * Gets the public URL for a path.
     */
    private function getPublicUrl(string $path): string
    {
        return asset('storage/'.$path);
    }

    /**
     * Validates the uploaded file against type constraints.
     */
    private function validateFile(UploadedFile $file, ImageType $type): void
    {
        $maxSize = $type->getMaxSize();
        $allowedMimes = $type->getAllowedMimeTypes();

        $fileSizeInKB = $this->getFileSize($file) / 1024;

        if ($fileSizeInKB > $maxSize) {
            throw new RuntimeException(
                sprintf('File size exceeds limit of %s KB', $maxSize)
            );
        }

        $fileMimeType = $file->getMimeType();

        if (! in_array($fileMimeType, $allowedMimes, true)) {
            throw new RuntimeException(
                sprintf('MIME type %s not allowed', $fileMimeType)
            );
        }
    }
}
