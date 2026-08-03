<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Traits;

use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\LaravelImages\Models\Image;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;

/**
 * Trait for models that can have images and albums.
 *
 * Provides computed attributes for media management using direct queries.
 * No relations needed in the model.
 *
 * @property-read bool $has_images
 * @property-read int $images_count
 * @property-read Image|null $primary_image
 * @property-read Image|null $avatar
 * @property-read Image|null $cover
 * @property-read Image|null $banner
 * @property-read Image|null $logo
 * @property-read Image|null $icon
 * @property-read Collection<int, Image> $gallery_images
 * @property-read bool $has_albums
 * @property-read int $albums_count
 * @property-read Album|null $primary_album
 * @property-read Album|null $featured_album
 * @property-read Collection<int, Album> $public_albums
 * @property-read Collection<int, Album> $private_albums
 */
trait HasMediables
{
    // ============================================================
    // IMAGE ATTRIBUTES
    // ============================================================

    protected function hasImages(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => Image::where('imageable_type', $this->getMorphClass())
                ->where('imageable_id', $this->getKey())
                ->exists()
        );
    }

    protected function imagesCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => Image::where('imageable_type', $this->getMorphClass())
                ->where('imageable_id', $this->getKey())
                ->count()
        );
    }

    protected function primaryImage(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => Image::where('imageable_type', $this->getMorphClass())
                ->where('imageable_id', $this->getKey())
                ->where('is_primary', true)
                ->first()
        );
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => Image::where('imageable_type', $this->getMorphClass())
                ->where('imageable_id', $this->getKey())
                ->where('type', ImageType::AVATAR)
                ->first()
        );
    }

    protected function cover(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => Image::where('imageable_type', $this->getMorphClass())
                ->where('imageable_id', $this->getKey())
                ->where('type', ImageType::COVER)
                ->first()
        );
    }

    protected function banner(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => Image::where('imageable_type', $this->getMorphClass())
                ->where('imageable_id', $this->getKey())
                ->where('type', ImageType::BANNER)
                ->first()
        );
    }

    protected function logo(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => Image::where('imageable_type', $this->getMorphClass())
                ->where('imageable_id', $this->getKey())
                ->where('type', ImageType::LOGO)
                ->first()
        );
    }

    protected function icon(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => Image::where('imageable_type', $this->getMorphClass())
                ->where('imageable_id', $this->getKey())
                ->where('type', ImageType::ICON)
                ->first()
        );
    }

    protected function galleryImages(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => Image::where('imageable_type', $this->getMorphClass())
                ->where('imageable_id', $this->getKey())
                ->where('type', ImageType::GALLERY)
                ->orderBy('order')
                ->get()
        );
    }

    // ============================================================
    // ALBUM ATTRIBUTES
    // ============================================================

    protected function hasAlbums(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => Album::where('albumable_type', $this->getMorphClass())
                ->where('albumable_id', $this->getKey())
                ->exists()
        );
    }

    protected function albumsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => Album::where('albumable_type', $this->getMorphClass())
                ->where('albumable_id', $this->getKey())
                ->count()
        );
    }

    protected function primaryAlbum(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Album => Album::where('albumable_type', $this->getMorphClass())
                ->where('albumable_id', $this->getKey())
                ->orderBy('created_at')
                ->first()
        );
    }

    protected function featuredAlbum(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Album => Album::where('albumable_type', $this->getMorphClass())
                ->where('albumable_id', $this->getKey())
                ->where('is_featured', BinaryChoice::YES)
                ->first()
        );
    }

    protected function publicAlbums(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => Album::where('albumable_type', $this->getMorphClass())
                ->where('albumable_id', $this->getKey())
                ->where('is_public', BinaryChoice::YES)
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    protected function privateAlbums(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => Album::where('albumable_type', $this->getMorphClass())
                ->where('albumable_id', $this->getKey())
                ->where('is_public', BinaryChoice::NO)
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }
}
