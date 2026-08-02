<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Datas\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Datas\AlbumData;

/**
 * Collection of AlbumData objects.
 *
 * @extends AbstractTypedCollection<AlbumData>
 */
final class AlbumDataCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(AlbumData::class);
    }

    /**
     * Filter only public albums.
     */
    public function getPublic(): self
    {
        return $this->filter(fn (AlbumData $album) => $album->is_public === BinaryChoice::YES);
    }

    /**
     * Filter only featured albums.
     */
    public function getFeatured(): self
    {
        return $this->filter(fn (AlbumData $album) => $album->is_featured === BinaryChoice::YES);
    }

    /**
     * Filter albums by albumable type.
     */
    public function filterByAlbumableType(string $albumableType): self
    {
        return $this->filter(fn (AlbumData $album) => $album->albumable_type === $albumableType);
    }
}
