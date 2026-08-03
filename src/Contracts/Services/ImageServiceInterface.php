<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Contracts\Services;

use AndyDefer\LaravelImages\Contracts\Processors\ImageProcessorInterface;
use AndyDefer\LaravelImages\Contracts\Storage\ImageStorageInterface;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Records\ImageOptionsRecord;
use AndyDefer\LaravelImages\Records\ImageRecord;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * Interface for image management service.
 *
 * Provides comprehensive image management operations including upload,
 * deletion, retrieval, and manipulation of images with polymorphic relations.
 */
interface ImageServiceInterface
{
    /**
     * Find an image by its ID.
     *
     * @param  string  $id  The image UUID
     * @return Image|null The found image or null if not found
     */
    public function findImage(string $id): ?Image;

    /**
     * Upload and attach an image to a model.
     *
     * @param  UploadedFile  $file  The uploaded file
     * @param  Model  $imageable  The parent model (polymorphic relation)
     * @param  Model|null  $uploadedBy  The user who uploaded the image (optional)
     * @param  ImageType  $type  The image type (avatar, cover, gallery, etc.)
     * @param  ImageOptionsRecord|null  $options  Upload options (alt_text, caption, order, etc.)
     * @return Image The created image model
     *
     * @throws \RuntimeException If file validation fails (size or mime type)
     */
    public function upload(
        UploadedFile $file,
        Model $imageable,
        ?Model $uploadedBy = null,
        ImageType $type = ImageType::GALLERY,
        ?ImageOptionsRecord $options = null,
    ): Image;

    /**
     * Upload multiple images at once.
     *
     * @param  array<UploadedFile>  $files  Array of uploaded files
     * @param  Model  $imageable  The parent model
     * @param  Model|null  $uploadedBy  The user who uploaded the images
     * @param  ImageType  $type  The image type for all images
     * @param  ImageOptionsRecord|null  $options  Upload options applied to all images
     * @return Collection<int, Image> Collection of created images
     */
    public function uploadMultiple(
        array $files,
        Model $imageable,
        ?Model $uploadedBy = null,
        ImageType $type = ImageType::GALLERY,
        ?ImageOptionsRecord $options = null,
    ): Collection;

    /**
     * Update an existing image.
     *
     * @param  ImageRecord  $record  The record containing updated data
     * @param  string  $id  The image UUID to update
     * @return Image The updated image model
     */
    public function update(ImageRecord $record, string $id): Image;

    /**
     * Delete an image.
     *
     * @param  string  $id  The image UUID to delete
     * @param  bool  $deleteFile  Whether to delete the physical file
     *
     * @throws \RuntimeException If image not found
     */
    public function delete(string $id, bool $deleteFile = true): void;

    /**
     * Delete multiple images.
     *
     * @param  array<string>  $ids  Array of image UUIDs to delete
     * @param  bool  $deleteFile  Whether to delete the physical files
     */
    public function deleteMultiple(array $ids, bool $deleteFile = true): void;

    /**
     * Delete all images associated with a model.
     *
     * @param  Model  $model  The parent model
     * @param  bool  $deleteFile  Whether to delete the physical files
     */
    public function deleteAllForModel(Model $model, bool $deleteFile = true): void;

    /**
     * Get all images for a model.
     *
     * @param  Model  $model  The parent model
     * @param  ImageType|null  $type  Filter by image type (optional)
     * @return Collection<int, Image> Collection of images
     */
    public function getImagesForModel(Model $model, ?ImageType $type = null): Collection;

    /**
     * Get the primary image for a model.
     *
     * @param  Model  $model  The parent model
     * @return Image|null The primary image or null if none
     */
    public function getPrimaryImage(Model $model): ?Image;

    /**
     * Set an image as the primary image for a model.
     * This will remove the primary flag from all other images of the same model.
     *
     * @param  string  $id  The image UUID to set as primary
     * @param  Model  $model  The parent model
     */
    public function setAsPrimary(string $id, Model $model): void;

    /**
     * Count images for a model.
     *
     * @param  Model  $model  The parent model
     * @param  ImageType|null  $type  Filter by image type (optional)
     * @return int Number of images
     */
    public function countImages(Model $model, ?ImageType $type = null): int;

    /**
     * Get images that were updated after a specific date.
     *
     * @param  DateTimeVO  $date  The date threshold
     * @return Collection<int, Image> Collection of images
     */
    public function getImagesUpdatedAfter(DateTimeVO $date): Collection;

    /**
     * Reorder images by their IDs.
     * The order of IDs in the array determines the new order.
     *
     * @param  array<string>  $ids  Array of image UUIDs in the desired order
     */
    public function reorder(array $ids): void;

    /**
     * Get the thumbnail URL for an image.
     *
     * @param  string  $imageId  The image UUID
     * @param  string  $size  The thumbnail size (small, medium, large)
     * @return string The thumbnail URL
     *
     * @throws \RuntimeException If image not found
     */
    public function getThumbnailUrl(string $imageId, string $size = 'small'): string;

    /**
     * Get the image processor instance.
     *
     * @return ImageProcessorInterface The image processor (GD or Imagick)
     */
    public function getImageProcessor(): ImageProcessorInterface;

    /**
     * Get the storage instance.
     *
     * @return ImageStorageInterface The storage handler
     */
    public function getStorage(): ImageStorageInterface;

    /**
     * Synchronize the inverse relation between light and dark variants.
     *
     * This method detects if an image is a light or dark variant and links it
     * with its counterpart automatically.
     *
     * @param  Image  $image  The image to synchronize
     */
    public function syncInverseRelation(Image $image): void;
}
