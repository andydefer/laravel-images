<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\LaravelImages\Datas\ImageData;
use AndyDefer\LaravelImages\Enums\ImageType;

/**
 * Collection of ImageData objects.
 *
 * @extends AbstractTypedCollection<ImageData>
 */
final class ImageDataCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(ImageData::class);
    }

    /**
     * Filter images by type.
     */
    public function filterByType(ImageType $type): self
    {
        return $this->filter(fn (ImageData $image) => $image->type === $type);
    }

    /**
     * Get only primary images.
     */
    public function getPrimary(): self
    {
        return $this->filter(fn (ImageData $image) => $image->isPrimary === true);
    }

    /**
     * Get images ordered by order property.
     */
    public function sortByOrder(): self
    {
        $items = $this->toArray();
        usort($items, fn (ImageData $a, ImageData $b) => $a->order <=> $b->order);

        $collection = new self;
        foreach ($items as $item) {
            $collection->add($item);
        }

        return $collection;
    }
}
