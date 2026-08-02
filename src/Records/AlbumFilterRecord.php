<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\PhpVo\ValueObjects\SlugVO;

final class AlbumFilterRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $albumable_type = null,
        public readonly ?int $albumable_id = null,
        public readonly ?BinaryChoice $is_public = null,
        public readonly ?BinaryChoice $is_featured = null,
        public readonly ?StringTypedCollection $ids = null,
        public readonly ?SlugVO $slug = null,
        public readonly ?string $search = null,
    ) {}
}
