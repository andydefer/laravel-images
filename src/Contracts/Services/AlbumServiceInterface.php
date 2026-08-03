<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Contracts\Services;

use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Records\AlbumOptionsRecord;
use AndyDefer\PhpVo\ValueObjects\SlugVO;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Interface for album management service.
 *
 * Provides comprehensive album operations including creation, image management,
 * ordering, duplication, and retrieval with polymorphic relations.
 */
interface AlbumServiceInterface
{
    /**
     * Create a new album.
     *
     * @param  Model  $albumable  The parent model (polymorphic relation)
     * @param  string  $name  The album name
     * @param  AlbumOptionsRecord|null  $options  Album options (description, visibility, etc.)
     * @return Album The created album
     */
    public function createAlbum(Model $albumable, string $name, ?AlbumOptionsRecord $options = null): Album;

    /**
     * Add multiple images to an album.
     * Images are added in the order they appear in the array.
     *
     * @param  Album  $album  The target album
     * @param  array<string>  $imageIds  Array of image UUIDs to add
     */
    public function addImagesToAlbum(Album $album, array $imageIds): void;

    /**
     * Add a single image to an album.
     *
     * @param  Album  $album  The target album
     * @param  string  $imageId  The image UUID to add
     * @param  int  $order  The position order (0 = auto-assign at the end)
     */
    public function addImageToAlbum(Album $album, string $imageId, int $order = 0): void;

    /**
     * Remove an image from an album.
     *
     * @param  Album  $album  The album
     * @param  string  $imageId  The image UUID to remove
     */
    public function removeImageFromAlbum(Album $album, string $imageId): void;

    /**
     * Remove all images from an album.
     *
     * @param  Album  $album  The album to clear
     */
    public function removeAllImagesFromAlbum(Album $album): void;

    /**
     * Set the cover image for an album.
     *
     * @param  Album  $album  The album
     * @param  string  $imageId  The image UUID to set as cover
     */
    public function setCoverImage(Album $album, string $imageId): void;

    /**
     * Get all images in an album.
     *
     * @param  Album  $album  The album
     * @return Collection<int, Image> Collection of images
     */
    public function getAlbumImages(Album $album): Collection;

    /**
     * Get all albums for a specific model.
     *
     * @param  Model  $model  The parent model
     * @param  bool  $onlyPublic  Only return public albums (default: true)
     * @return Collection<int, Album> Collection of albums
     */
    public function getAlbumsForModel(Model $model, bool $onlyPublic = true): Collection;

    /**
     * Get an album by its slug.
     *
     * @param  string|SlugVO  $slug  The album slug
     * @return Album|null The found album or null
     */
    public function getAlbumBySlug(string|SlugVO $slug): ?Album;

    /**
     * Update an existing album.
     *
     * @param  string  $id  The album UUID to update
     * @param  AlbumOptionsRecord  $options  The updated options
     * @return Album The updated album
     *
     * @throws \RuntimeException If album not found
     */
    public function updateAlbum(string $id, AlbumOptionsRecord $options): Album;

    /**
     * Delete an album.
     *
     * @param  string  $id  The album UUID to delete
     * @param  bool  $deleteImages  Whether to also delete associated images
     *
     * @throws \RuntimeException If album not found
     */
    public function deleteAlbum(string $id, bool $deleteImages = false): void;

    /**
     * Reorder images within an album.
     * The order of IDs in the array determines the new order.
     *
     * @param  Album  $album  The album
     * @param  array<string>  $imageIds  Array of image UUIDs in the desired order
     */
    public function reorderAlbumImages(Album $album, array $imageIds): void;

    /**
     * Duplicate an album with all its images.
     *
     * @param  Album  $album  The album to duplicate
     * @param  string  $newName  The name for the new album
     * @return Album The duplicated album
     */
    public function duplicateAlbum(Album $album, string $newName): Album;

    /**
     * Count the number of images in an album.
     *
     * @param  Album  $album  The album
     * @return int Number of images
     */
    public function countAlbumImages(Album $album): int;

    /**
     * Check if an album is empty (has no images).
     *
     * @param  Album  $album  The album
     * @return bool True if album has no images
     */
    public function isAlbumEmpty(Album $album): bool;

    /**
     * Get the cover image of an album.
     * If no cover is set, returns the first image in the album.
     *
     * @param  Album  $album  The album
     * @return Image|null The cover image or null
     */
    public function getAlbumCoverImage(Album $album): ?Image;

    /**
     * Get featured albums.
     * Returns albums that are both featured and public.
     *
     * @param  int  $limit  Maximum number of albums to return (default: 10)
     * @return Collection<int, Album> Collection of featured albums
     */
    public function getFeaturedAlbums(int $limit = 10): Collection;
}
