<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Datas;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\LaravelImages\Enums\ImageExtension;
use AndyDefer\LaravelImages\Enums\ImageMimeType;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\ValueObjects\ImageMetadataVO;
use AndyDefer\LaravelImages\ValueObjects\ImagePathVO;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;

/**
 * Data transfer object for Image API responses.
 *
 * @property-read string $id
 * @property-read ImagePathVO $path
 * @property-read string $filename
 * @property-read string $originalFilename
 * @property-read ImageExtension $extension
 * @property-read ImageMimeType $mimeType
 * @property-read int $size
 * @property-read ImageType $type
 * @property-read ImageMetadataVO|null $metadata
 * @property-read string $imageableType
 * @property-read int $imageableId
 * @property-read int|null $width
 * @property-read int|null $height
 * @property-read int $order
 * @property-read bool $isPrimary
 * @property-read bool $isProcessed
 * @property-read int|null $inverseImageId
 * @property-read StringTypedCollection $inverseImageIds
 * @property-read string|null $uploadedByType
 * @property-read int|null $uploadedById
 * @property-read DateTimeVO $createdAt
 * @property-read DateTimeVO $updatedAt
 * @property-read string $fullUrl
 * @property-read string $fileSizeForHumans
 * @property-read string $dimensions
 */
final class ImageData extends AbstractData
{
    public function __construct(
        public readonly string $id,
        public readonly ImagePathVO $path,
        public readonly string $filename,
        public readonly string $originalFilename,
        public readonly ImageExtension $extension,
        public readonly ImageMimeType $mimeType,
        public readonly int $size,
        public readonly ImageType $type,
        public readonly ?ImageMetadataVO $metadata,
        public readonly string $imageableType,
        public readonly int $imageableId,
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly int $order,
        public readonly bool $isPrimary,
        public readonly bool $isProcessed,
        public readonly ?int $inverseImageId,
        public readonly ?StringTypedCollection $inverseImageIds,
        public readonly ?string $uploadedByType,
        public readonly ?int $uploadedById,
        public readonly DateTimeVO $createdAt,
        public readonly DateTimeVO $updatedAt,
        public readonly string $fullUrl,
        public readonly string $fileSizeForHumans,
        public readonly string $dimensions,
    ) {}
}
