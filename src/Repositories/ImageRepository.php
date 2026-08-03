<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Repositories;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelImages\Contracts\Repositories\ImageRepositoryInterface;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Records\ImageFilterRecord;
use AndyDefer\LaravelImages\Records\ImageRecord;
use AndyDefer\Repository\AbstractRepository;
use AndyDefer\Repository\Records\FindByRecord;
use Illuminate\Database\Eloquent\Builder;

final class ImageRepository extends AbstractRepository implements ImageRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(
            modelClass: Image::class,
            recordClass: ImageRecord::class,
        );
    }

    protected function applyFilters(Builder $query, AbstractRecord $filters): void
    {
        if (! $filters instanceof ImageFilterRecord) {
            return;
        }

        if ($filters->id !== null) {
            $query->where('id', $filters->id);
        }

        if ($filters->ids !== null && $filters->ids->isNotEmpty()) {
            $query->whereIn('id', $filters->ids->toArray());
        }

        if ($filters->imageable_type !== null) {
            $query->where('imageable_type', $filters->imageable_type);
        }

        if ($filters->imageable_id !== null) {
            $query->where('imageable_id', $filters->imageable_id);
        }

        if ($filters->type !== null) {
            $query->where('type', $filters->type->value);
        }

        if ($filters->types !== null && $filters->types->isNotEmpty()) {
            $query->whereIn('type', $filters->types->toCodes());
        }

        if ($filters->min_size !== null) {
            $query->where('size', '>=', $filters->min_size);
        }

        if ($filters->max_size !== null) {
            $query->where('size', '<=', $filters->max_size);
        }

        if ($filters->extension !== null) {
            $query->where('extension', $filters->extension->value);
        }

        if ($filters->mime_type !== null) {
            $query->where('mime_type', $filters->mime_type->value);
        }

        if ($filters->updated_at !== null) {
            $query->where('updated_at', '>=', $filters->updated_at->toDateTimeString());
        }

        if ($filters->is_primary !== null) {
            $query->where('is_primary', $filters->is_primary);
        }

        if ($filters->order !== null) {
            $query->where('order', $filters->order);
        }

        if ($filters->min_order !== null) {
            $query->where('order', '>=', $filters->min_order);
        }

        if ($filters->max_order !== null) {
            $query->where('order', '<=', $filters->max_order);
        }

        if ($filters->search !== null) {
            $search = '%'.$filters->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('filename', 'LIKE', $search)
                    ->orWhere('original_filename', 'LIKE', $search);
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryImageForModel(string $imageableType, string $imageableId): ?Image
    {
        $filter = new ImageFilterRecord(
            imageable_type: $imageableType,
            imageable_id: $imageableId,
            is_primary: true,
        );

        $findBy = new FindByRecord(filters: $filter, limit: 1);

        return $this->findBy($findBy)->first();
    }
}
