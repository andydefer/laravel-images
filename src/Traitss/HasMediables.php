<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Traits;

use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\LaravelImages\Models\Image;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait for models that can have images and albums.
 *
 * Provides polymorphic relations and computed attributes for media management.
 *
 * @property-read Collection<int, Image> $images
 * @property-read Collection<int, Album> $albums
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
    // RELATIONS
    // ============================================================

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function albums(): MorphMany
    {
        return $this->morphMany(Album::class, 'albumable');
    }

    // ============================================================
    // IMAGE ATTRIBUTES
    // ============================================================

    protected function hasImages(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->images()->exists()
        );
    }

    protected function imagesCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->images()->count()
        );
    }

    protected function primaryImage(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => $this->images()
                ->where('is_primary', true)
                ->first()
        );
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => $this->images()
                ->where('type', ImageType::AVATAR)
                ->first()
        );
    }

    protected function cover(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => $this->images()
                ->where('type', ImageType::COVER)
                ->first()
        );
    }

    protected function banner(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => $this->images()
                ->where('type', ImageType::BANNER)
                ->first()
        );
    }

    protected function logo(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => $this->images()
                ->where('type', ImageType::LOGO)
                ->first()
        );
    }

    protected function icon(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Image => $this->images()
                ->where('type', ImageType::ICON)
                ->first()
        );
    }

    protected function galleryImages(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->images()
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
            get: fn (): bool => $this->albums()->exists()
        );
    }

    protected function albumsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->albums()->count()
        );
    }

    protected function primaryAlbum(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Album => $this->albums()
                ->orderBy('created_at')
                ->first()
        );
    }

    protected function featuredAlbum(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Album => $this->albums()
                ->where('is_featured', true)
                ->first()
        );
    }

    protected function publicAlbums(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->albums()
                ->where('is_public', true)
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    protected function privateAlbums(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->albums()
                ->where('is_public', false)
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }
}
