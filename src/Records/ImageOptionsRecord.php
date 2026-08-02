<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelImages\ValueObjects\ImageMetadataVO;

/**
 * Record for image upload options.
 *
 * @property-read string|null $alt_text        Alternative text for accessibility
 * @property-read string|null $caption         Caption/description of the image
 * @property-read ImageMetadataVO|null $metadata Additional metadata (source, location, tags, etc.)
 * @property-read int|null $order             Order position in collection
 * @property-read bool|null $is_primary       Whether this is the primary image
 * @property-read int|null $width             Desired width for resizing
 * @property-read int|null $height            Desired height for resizing
 * @property-read bool|null $generate_thumbnails Whether to generate thumbnails
 */
final class ImageOptionsRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $alt_text = null,
        public readonly ?string $caption = null,
        public readonly ?ImageMetadataVO $metadata = null,
        public readonly ?int $order = null,
        public readonly ?bool $is_primary = null,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?bool $generate_thumbnails = null,
    ) {}
}
