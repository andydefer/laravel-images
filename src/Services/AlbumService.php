<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Services;

use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Contracts\Services\AlbumServiceInterface;
use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Records\AlbumFilterRecord;
use AndyDefer\LaravelImages\Records\AlbumOptionsRecord;
use AndyDefer\LaravelImages\Records\AlbumRecord;
use AndyDefer\LaravelImages\Repositories\AlbumRepository;
use AndyDefer\PhpVo\ValueObjects\SlugVO;
use AndyDefer\Repository\Records\FindByRecord;
use AndyDefer\Repository\ValueObjects\SortColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Service for managing albums.
 *
 * Provides comprehensive album management including creation, image management,
 * ordering, duplication, and retrieval with polymorphic relations.
 */
final class AlbumService implements AlbumServiceInterface
{
    private const DEFAULT_ALBUM_ORDER = 0;

    private const DEFAULT_LIMIT = 10;

    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly ImageService $imageService,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function createAlbum(Model $albumable, string $name, ?AlbumOptionsRecord $options = null): Album
    {
        $options = $options ?? new AlbumOptionsRecord;

        $albumRecord = AlbumRecord::from([
            'name' => $name,
            'slug' => $this->generateSlug($name),
            'description' => $options->description,
            'is_public' => $options->is_public ?? BinaryChoice::YES,
            'is_featured' => $options->is_featured ?? BinaryChoice::NO,
            'metadata' => $options->metadata,
            'albumable_type' => $albumable->getMorphClass(),
            'albumable_id' => $albumable->getKey(),
        ]);

        return $this->albumRepository->create($albumRecord);
    }

    /**
     * {@inheritDoc}
     */
    public function addImagesToAlbum(Album $album, array $imageIds): void
    {
        foreach ($imageIds as $index => $imageId) {
            $album->images()->attach($imageId, [
                'order' => $index + 1,
                'created_at' => now(),
            ]);
        }

        $album->load('images');
    }

    /**
     * {@inheritDoc}
     */
    public function addImageToAlbum(Album $album, int $imageId, int $order = self::DEFAULT_ALBUM_ORDER): void
    {
        $maxOrder = $album->images()->max('album_image.order') ?? 0;
        $order = $order ?: $maxOrder + 1;

        $album->images()->attach($imageId, ['order' => $order]);

        $album->load('images');
    }

    /**
     * {@inheritDoc}
     */
    public function removeImageFromAlbum(Album $album, int $imageId): void
    {
        $album->images()->detach($imageId);
        $album->load('images');
    }

    /**
     * {@inheritDoc}
     */
    public function removeAllImagesFromAlbum(Album $album): void
    {
        $album->images()->detach();
        $album->load('images');
    }

    /**
     * {@inheritDoc}
     */
    public function setCoverImage(Album $album, int $imageId): void
    {
        $album->cover_image_id = $imageId;
        $album->save();
    }

    /**
     * {@inheritDoc}
     */
    public function getAlbumImages(Album $album): Collection
    {
        return $album->images()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getAlbumsForModel(Model $model, bool $onlyPublic = true): Collection
    {
        $filter = AlbumFilterRecord::from([
            'albumable_type' => $model->getMorphClass(),
            'albumable_id' => $model->getKey(),
            'is_public' => $onlyPublic ? BinaryChoice::YES : null,
        ]);

        $findByRecord = new FindByRecord(filters: $filter);

        return $this->albumRepository->findBy($findByRecord);
    }

    /**
     * {@inheritDoc}
     */
    public function getAlbumBySlug(string|SlugVO $slug): ?Album
    {
        $slugValue = $slug instanceof SlugVO ? $slug->getValue() : $slug;

        $filter = AlbumFilterRecord::from(['slug' => $slugValue]);
        $findByRecord = new FindByRecord(filters: $filter, limit: 1);

        return $this->albumRepository->findBy($findByRecord)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function updateAlbum(int $id, AlbumOptionsRecord $options): Album
    {
        $album = $this->albumRepository->find($id);

        if ($album === null) {
            throw new RuntimeException("Album not found: {$id}");
        }

        $updateData = $this->buildUpdateData($options);

        if (empty($updateData)) {
            return $album;
        }

        $albumRecord = AlbumRecord::from($updateData);

        return $this->albumRepository->update($id, $albumRecord);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAlbum(int $id, bool $deleteImages = false): void
    {
        $album = $this->albumRepository->find($id);

        if ($album === null) {
            throw new RuntimeException("Album not found: {$id}");
        }

        if ($deleteImages) {
            $imageIds = $album->images->pluck('id')->toArray();
            $this->imageService->deleteMultiple($imageIds);
        }

        $this->albumRepository->delete($id);
    }

    /**
     * {@inheritDoc}
     */
    public function reorderAlbumImages(Album $album, array $imageIds): void
    {
        foreach ($imageIds as $index => $imageId) {
            $album->images()->updateExistingPivot($imageId, [
                'order' => $index + 1,
            ]);
        }

        $album->load('images');
    }

    /**
     * {@inheritDoc}
     */
    public function duplicateAlbum(Album $album, string $newName): Album
    {
        $albumRecord = AlbumRecord::from([
            'name' => $newName,
            'slug' => $this->generateSlug($newName),
            'description' => $album->description,
            'is_public' => $album->is_public ?? BinaryChoice::YES,
            'is_featured' => BinaryChoice::NO,
            'metadata' => $album->metadata,
            'albumable_type' => $album->albumable_type,
            'albumable_id' => $album->albumable_id,
        ]);

        $newAlbum = $this->albumRepository->create($albumRecord);

        $this->copyAlbumImages($album, $newAlbum);

        if ($album->cover_image_id) {
            $newAlbum->cover_image_id = $album->cover_image_id;
            $newAlbum->save();
        }

        return $newAlbum;
    }

    /**
     * {@inheritDoc}
     */
    public function countAlbumImages(Album $album): int
    {
        return $album->images()->count();
    }

    /**
     * {@inheritDoc}
     */
    public function isAlbumEmpty(Album $album): bool
    {
        return $this->countAlbumImages($album) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getAlbumCoverImage(Album $album): ?Image
    {
        if ($album->cover_image_id) {
            return $this->imageService->findImage($album->cover_image_id);
        }

        return $album->images->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getFeaturedAlbums(int $limit = self::DEFAULT_LIMIT): Collection
    {
        $filter = AlbumFilterRecord::from([
            'is_featured' => BinaryChoice::YES,
            'is_public' => BinaryChoice::YES,
        ]);

        $findByRecord = new FindByRecord(
            filters: $filter,
            limit: $limit,
            sortBy: new SortColumns('created_at:desc'),
        );

        return $this->albumRepository->findBy($findByRecord);
    }

    /**
     * Generates a unique slug from a name.
     */
    private function generateSlug(string $name): SlugVO
    {
        return new SlugVO(Str::slug($name).'-'.uniqid());
    }

    /**
     * Builds update data from options.
     *
     * @return array<string, mixed>
     */
    private function buildUpdateData(AlbumOptionsRecord $options): array
    {
        $updateData = [];

        if ($options->name !== null) {
            $updateData['name'] = $options->name;
            $updateData['slug'] = $this->generateSlug($options->name);
        }

        if ($options->description !== null) {
            $updateData['description'] = $options->description;
        }

        if ($options->is_public !== null) {
            $updateData['is_public'] = $options->is_public;
        }

        if ($options->is_featured !== null) {
            $updateData['is_featured'] = $options->is_featured;
        }

        if ($options->metadata !== null) {
            $updateData['metadata'] = $options->metadata;
        }

        return $updateData;
    }

    /**
     * Copies images from one album to another.
     */
    private function copyAlbumImages(Album $sourceAlbum, Album $targetAlbum): void
    {
        foreach ($sourceAlbum->images as $image) {
            $targetAlbum->images()->attach($image->id, [
                'order' => $image->pivot->order,
                'created_at' => now(),
            ]);
        }
    }
}
