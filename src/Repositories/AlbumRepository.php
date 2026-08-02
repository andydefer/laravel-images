<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Repositories;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Contracts\Repositories\AlbumRepositoryInterface;
use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\LaravelImages\Records\AlbumFilterRecord;
use AndyDefer\LaravelImages\Records\AlbumRecord;
use AndyDefer\Repository\AbstractRepository;
use Illuminate\Database\Eloquent\Builder;

final class AlbumRepository extends AbstractRepository implements AlbumRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(
            modelClass: Album::class,
            recordClass: AlbumRecord::class,
        );
    }

    protected function applyFilters(Builder $query, AbstractRecord $filters): void
    {
        if (! $filters instanceof AlbumFilterRecord) {
            return;
        }

        if ($filters->albumable_type !== null) {
            $query->where('albumable_type', $filters->albumable_type);
        }

        if ($filters->albumable_id !== null) {
            $query->where('albumable_id', $filters->albumable_id);
        }

        if ($filters->is_public !== null) {
            $query->where('is_public', $filters->is_public);
        }

        if ($filters->is_featured !== null) {
            $query->where('is_featured', $filters->is_featured);
        }

        if ($filters->ids !== null && $filters->ids->isNotEmpty()) {
            $query->whereIn('id', $filters->ids->toArray());
        }

        if ($filters->slug !== null) {
            $query->where('slug', $filters->slug->getValue());
        }

        if ($filters->search !== null) {
            $search = '%'.$filters->search.'%';
            $query->where('name', 'LIKE', $search)
                ->orWhere('description', 'LIKE', $search);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setPublic(int $id, BinaryChoice $isPublic): Album
    {
        $record = AlbumRecord::from([
            'is_public' => $isPublic,
        ]);

        return $this->update($id, $record);
    }

    /**
     * {@inheritDoc}
     */
    public function setFeatured(int $id, BinaryChoice $isFeatured): Album
    {
        $record = AlbumRecord::from([
            'is_featured' => $isFeatured,
        ]);

        return $this->update($id, $record);
    }
}
