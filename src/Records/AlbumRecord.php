<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelCluster\ValueObjects\ClusterVO;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;
use AndyDefer\PhpVo\ValueObjects\SlugVO;

final class AlbumRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $name = null,
        public readonly ?SlugVO $slug = null,
        public readonly ?string $description = null,
        public readonly ?string $cover_image_id = null,
        public readonly ?BinaryChoice $is_public = null,
        public readonly ?BinaryChoice $is_featured = null,
        public readonly ?ClusterVO $metadata = null,
        public readonly ?string $albumable_type = null,
        public readonly ?string $albumable_id = null,
        public readonly ?DateTimeVO $created_at = null,
        public readonly ?DateTimeVO $updated_at = null,
        public readonly ?DateTimeVO $deleted_at = null,
    ) {}
}
