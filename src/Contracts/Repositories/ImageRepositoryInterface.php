<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Contracts\Repositories;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\Repository\AbstractRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends AbstractRepositoryInterface<Model, AbstractRecord>
 */
interface ImageRepositoryInterface extends AbstractRepositoryInterface
{
    /**
     * Get the primary image for a model.
     *
     * @param  string  $imageableType  The morph class of the parent model
     * @param  int  $imageableId  The ID of the parent model
     * @return Image|null The primary image or null if not found
     */
    public function getPrimaryImageForModel(string $imageableType, int $imageableId): ?Image;
}
