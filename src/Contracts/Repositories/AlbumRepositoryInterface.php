<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Contracts\Repositories;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\Repository\AbstractRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends AbstractRepositoryInterface<Model, AbstractRecord>
 */
interface AlbumRepositoryInterface extends AbstractRepositoryInterface
{
    /**
     * Set the public status of an album using BinaryChoice.
     *
     * @param  int  $id  The album ID
     * @param  BinaryChoice  $isPublic  The public status (YES or NO)
     * @return Album The updated album
     */
    public function setPublic(int $id, BinaryChoice $isPublic): Album;

    /**
     * Set the featured status of an album using BinaryChoice.
     *
     * @param  int  $id  The album ID
     * @param  BinaryChoice  $isFeatured  The featured status (YES or NO)
     * @return Album The updated album
     */
    public function setFeatured(int $id, BinaryChoice $isFeatured): Album;
}
