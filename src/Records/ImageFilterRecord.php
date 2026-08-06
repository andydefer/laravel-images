<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\LaravelImages\Collections\ImageTypeCollection;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelUtils\Enums\ImageExtension;
use AndyDefer\LaravelUtils\Enums\ImageMimeType;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;

final class ImageFilterRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?StringTypedCollection $ids = null,
        public readonly ?string $imageable_type = null,
        public readonly ?string $imageable_id = null,
        public readonly ?ImageType $type = null,
        public readonly ?ImageTypeCollection $types = null,
        public readonly ?int $min_size = null,
        public readonly ?int $max_size = null,
        public readonly ?ImageExtension $extension = null,
        public readonly ?ImageMimeType $mime_type = null,
        public readonly ?DateTimeVO $updated_at = null,
        public readonly ?string $search = null,
        // Nouveaux filtres
        public readonly ?bool $is_primary = null,
        public readonly ?int $order = null,
        public readonly ?int $min_order = null,
        public readonly ?int $max_order = null,
    ) {}
}
