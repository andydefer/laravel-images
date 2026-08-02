<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Datas;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelCluster\ValueObjects\ClusterVO;
use AndyDefer\LaravelImages\Datas\Collections\ImageDataCollection;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;
use AndyDefer\PhpVo\ValueObjects\SlugVO;

/**
 * Data transfer object for Album API responses.
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read SlugVO $slug
 * @property-read string|null $description
 * @property-read int|null $coverImageId
 * @property-read BinaryChoice $isPublic
 * @property-read BinaryChoice $isFeatured
 * @property-read ClusterVO|null $metadata
 * @property-read string|null $albumableType
 * @property-read int|null $albumableId
 * @property-read int $imageCount
 * @property-read ImageDataCollection $images
 * @property-read ImageData|null $coverImage
 * @property-read DateTimeVO $createdAt
 * @property-read DateTimeVO $updatedAt
 */
final class AlbumData extends AbstractData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly SlugVO $slug,
        public readonly ?string $description,
        public readonly ?int $coverImageId,
        public readonly BinaryChoice $isPublic,
        public readonly BinaryChoice $isFeatured,
        public readonly ?ClusterVO $metadata,
        public readonly ?string $albumableType,
        public readonly ?int $albumableId,
        public readonly int $imageCount,
        public readonly ImageDataCollection $images,
        public readonly ?ImageData $coverImage,
        public readonly DateTimeVO $createdAt,
        public readonly DateTimeVO $updatedAt,
    ) {}
}
