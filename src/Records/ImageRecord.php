<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelImages\Enums\ImageExtension;
use AndyDefer\LaravelImages\Enums\ImageMimeType;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\ValueObjects\ImageMetadataVO;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;

final class ImageRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $path = null,
        public readonly ?string $filename = null,
        public readonly ?string $original_filename = null,
        public readonly ?ImageExtension $extension = null,
        public readonly ?ImageMimeType $mime_type = null,
        public readonly ?int $size = null,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?ImageType $type = null,
        public readonly ?ImageMetadataVO $metadata = null,
        public readonly ?int $order = null,
        public readonly ?bool $is_primary = null,
        public readonly ?bool $is_processed = null,
        public readonly ?string $uploaded_by_type = null,
        public readonly ?int $uploaded_by_id = null,
        public readonly ?string $imageable_type = null,
        public readonly ?int $imageable_id = null,
        public readonly ?DateTimeVO $created_at = null,
        public readonly ?DateTimeVO $updated_at = null,
        public readonly ?DateTimeVO $deleted_at = null,
    ) {}
}
