<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelCluster\ValueObjects\ClusterVO;

final class AlbumOptionsRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?BinaryChoice $is_public = null,
        public readonly ?BinaryChoice $is_featured = null,
        public readonly ?ClusterVO $metadata = null,
    ) {}
}
