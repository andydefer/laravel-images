<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Traits;

use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Contracts\Repositories\AlbumRepositoryInterface;
use AndyDefer\LaravelImages\Contracts\Repositories\ImageRepositoryInterface;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Records\AlbumFilterRecord;
use AndyDefer\LaravelImages\Records\ImageFilterRecord;
use AndyDefer\Repository\Records\FindByRecord;
use AndyDefer\Repository\ValueObjects\SortColumns;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Trait for models that can have images and albums.
 *
 * Uses the dedicated repositories (ImageRepository, AlbumRepository) instead
 * of direct Eloquent facade calls, so that filtering logic stays centralised.
 *
 * @property-read bool $has_images
 * @property-read int $images_count
 * @property-read Image|null $primary_image
 * @property-read Image|null $avatar
 * @property-read Image|null $cover
 * @property-read Image|null $banner
 * @property-read Image|null $logo
 * @property-read Image|null $icon
 * @property-read SupportCollection<int, Image> $gallery_images
 * @property-read bool $has_albums
 * @property-read int $albums_count
 * @property-read Album|null $primary_album
 * @property-read Album|null $featured_album
 * @property-read SupportCollection<int, Album> $public_albums
 * @property-read SupportCollection<int, Album> $private_albums
 */
trait HasMediables
{
    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    /**
     * @return MorphMany<Image>
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * @return MorphMany<Album>
     */
    public function albums(): MorphMany
    {
        return $this->morphMany(Album::class, 'albumable');
    }

    // ============================================================
    // REPOSITORIES
    // ============================================================

    protected function imageRepository(): ImageRepositoryInterface
    {
        return app(ImageRepositoryInterface::class);
    }

    protected function albumRepository(): AlbumRepositoryInterface
    {
        return app(AlbumRepositoryInterface::class);
    }

    // ============================================================
    // IMAGE ATTRIBUTES
    // ============================================================

    protected function hasImages(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->imageRepository()
                ->exists(new ImageFilterRecord(
                    imageable_type: $this->getMorphClass(),
                    imageable_id: (string) $this->getKey(),
                ))
        );
    }

    protected function imagesCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->imageRepository()
                ->count(new ImageFilterRecord(
                    imageable_type: $this->getMorphClass(),
                    imageable_id: (string) $this->getKey(),
                ))
        );
    }

    protected function primaryImage(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => $this->imageRepository()
                ->findBy(new FindByRecord(
                    filters: new ImageFilterRecord(
                        imageable_type: $this->getMorphClass(),
                        imageable_id: (string) $this->getKey(),
                        is_primary: true,
                    ),
                    sortBy: new SortColumns('created_at:desc'),
                    limit: 1,
                ))
                ->first()
        );
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => $this->firstImageOfType(ImageType::AVATAR)
        );
    }

    protected function cover(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => $this->firstImageOfType(ImageType::COVER)
        );
    }

    protected function banner(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => $this->firstImageOfType(ImageType::BANNER)
        );
    }

    protected function logo(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => $this->firstImageOfType(ImageType::LOGO)
        );
    }

    protected function icon(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => $this->firstImageOfType(ImageType::ICON)
        );
    }

    protected function galleryImages(): Attribute
    {
        return Attribute::make(
            get: fn (): SupportCollection => $this->imageRepository()
                ->findBy(new FindByRecord(
                    filters: new ImageFilterRecord(
                        imageable_type: $this->getMorphClass(),
                        imageable_id: (string) $this->getKey(),
                        type: ImageType::GALLERY,
                    ),
                    sortBy: new SortColumns('created_at:desc'),
                ))
        );
    }

    private function firstImageOfType(ImageType $type): ?Image
    {
        return $this->imageRepository()
            ->findBy(new FindByRecord(
                filters: new ImageFilterRecord(
                    imageable_type: $this->getMorphClass(),
                    imageable_id: (string) $this->getKey(),
                    type: $type,
                ),
                sortBy: new SortColumns('created_at:desc'),
                limit: 1,
            ))
            ->first();
    }

    // ============================================================
    // ALBUM ATTRIBUTES
    // ============================================================

    protected function hasAlbums(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->albumRepository()
                ->exists(new AlbumFilterRecord(
                    albumable_type: $this->getMorphClass(),
                    albumable_id: (string) $this->getKey(),
                ))
        );
    }

    protected function albumsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->albumRepository()
                ->count(new AlbumFilterRecord(
                    albumable_type: $this->getMorphClass(),
                    albumable_id: (string) $this->getKey(),
                ))
        );
    }

    protected function primaryAlbum(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Album => $this->albumRepository()
                ->findBy(new FindByRecord(
                    filters: new AlbumFilterRecord(
                        albumable_type: $this->getMorphClass(),
                        albumable_id: (string) $this->getKey(),
                    ),
                    sortBy: new SortColumns('created_at:desc'),
                    limit: 1,
                ))
                ->first()
        );
    }

    protected function featuredAlbum(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Album => $this->albumRepository()
                ->findBy(new FindByRecord(
                    filters: new AlbumFilterRecord(
                        albumable_type: $this->getMorphClass(),
                        albumable_id: (string) $this->getKey(),
                        is_featured: BinaryChoice::YES,
                    ),
                    sortBy: new SortColumns('created_at:desc'),
                    limit: 1,
                ))
                ->first()
        );
    }

    protected function publicAlbums(): Attribute
    {
        return Attribute::make(
            get: fn (): SupportCollection => $this->albumRepository()
                ->findBy(new FindByRecord(
                    filters: new AlbumFilterRecord(
                        albumable_type: $this->getMorphClass(),
                        albumable_id: (string) $this->getKey(),
                        is_public: BinaryChoice::YES,
                    ),
                    sortBy: new SortColumns('created_at:desc'),
                ))
        );
    }

    protected function privateAlbums(): Attribute
    {
        return Attribute::make(
            get: fn (): SupportCollection => $this->albumRepository()
                ->findBy(new FindByRecord(
                    filters: new AlbumFilterRecord(
                        albumable_type: $this->getMorphClass(),
                        albumable_id: (string) $this->getKey(),
                        is_public: BinaryChoice::NO,
                    ),
                    sortBy: new SortColumns('created_at:desc'),
                ))
        );
    }
}
