<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Observers;

use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Services\ImageService;

/**
 * Observer responsible for maintaining image relationships and integrity.
 *
 * This observer automatically manages inverse relationships between light/dark
 * image variants and ensures data consistency when images are created,
 * updated, or deleted.
 */
final class ImageObserver
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    /**
     * Synchronizes inverse relationships when a new image is created.
     *
     * Automatically links light/dark image variants if they exist in the
     * same context (same parent model and type).
     */
    public function created(Image $image): void
    {
        $this->imageService->syncInverseRelation($image);
    }

    /**
     * Re-evaluates inverse relationships when an image is updated.
     *
     * Useful when an image's filename changes, potentially altering
     * its relationship to a light/dark variant.
     */
    public function updated(Image $image): void
    {
        $this->imageService->syncInverseRelation($image);
    }

    /**
     * Cleans up inverse relationships when an image is deleted.
     *
     * Ensures that any image referencing the deleted image as its inverse
     * has its inverse_image_id set to null to maintain referential integrity.
     */
    public function deleted(Image $image): void
    {
        Image::where('inverse_image_id', $image->id)
            ->update(['inverse_image_id' => null]);
    }
}
