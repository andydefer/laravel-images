<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Observers;

use AndyDefer\LaravelImages\Models\Album;
use Illuminate\Support\Str;

/**
 * Observer responsible for maintaining album-image relationship integrity.
 *
 * Ensures that when an album is deleted, all associated image relationships
 * are properly cleaned up to prevent orphaned pivot records.
 */
final class AlbumObserver
{
    /**
     * Generates a UUID for the album before creation if not already set.
     */
    public function creating(Album $album): void
    {
        if (empty($album->id)) {
            $album->id = (string) Str::uuid();
        }
    }

    /**
     * Removes image relationships before an album is soft-deleted.
     *
     * Detaches all associated images to prevent orphaned relationships
     * and maintain database referential integrity.
     */
    public function deleting(Album $album): void
    {
        $album->images()->detach();
    }

    /**
     * Removes image relationships when an album is permanently deleted.
     *
     * Ensures clean-up even when bypassing soft delete, maintaining
     * consistent behavior with the standard deletion flow.
     */
    public function forceDeleted(Album $album): void
    {
        $album->images()->detach();
    }
}
