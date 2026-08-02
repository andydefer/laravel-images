<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Repositories;

use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\LaravelImages\Records\AlbumFilterRecord;
use AndyDefer\LaravelImages\Repositories\AlbumRepository;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use AndyDefer\Repository\Records\FindByRecord;
use AndyDefer\Repository\ValueObjects\SortColumns;
use Illuminate\Foundation\Testing\DatabaseMigrations;

final class AlbumRepositoryTest extends IntegrationTestCase
{
    use DatabaseMigrations;

    private AlbumRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AlbumRepository;
    }

    // ============================================================
    // TESTS: applyFilters() - albumable_type
    // ============================================================

    public function test_filter_by_albumable_type(): void
    {
        $this->createAlbum(['albumable_type' => 'App\Models\User', 'albumable_id' => 1, 'name' => 'User Album']);
        $this->createAlbum(['albumable_type' => 'App\Models\Post', 'albumable_id' => 1, 'name' => 'Post Album']);

        $filter = new AlbumFilterRecord(albumable_type: 'App\Models\User');
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('User Album', $results->first()->name);
    }

    // ============================================================
    // TESTS: applyFilters() - albumable_id
    // ============================================================

    public function test_filter_by_albumable_id(): void
    {
        $this->createAlbum(['albumable_type' => 'App\Models\User', 'albumable_id' => 1, 'name' => 'User 1 Album']);
        $this->createAlbum(['albumable_type' => 'App\Models\User', 'albumable_id' => 2, 'name' => 'User 2 Album']);

        $filter = new AlbumFilterRecord(albumable_id: 1);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('User 1 Album', $results->first()->name);
    }

    // ============================================================
    // TESTS: applyFilters() - is_public (avec BinaryChoice)
    // ============================================================

    public function test_filter_by_is_public(): void
    {
        $this->createAlbum(['name' => 'Public Album', 'is_public' => BinaryChoice::YES]);
        $this->createAlbum(['name' => 'Private Album', 'is_public' => BinaryChoice::NO]);

        $filter = new AlbumFilterRecord(is_public: BinaryChoice::YES);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('Public Album', $results->first()->name);
    }

    // ============================================================
    // TESTS: applyFilters() - is_featured (avec BinaryChoice)
    // ============================================================

    public function test_filter_by_is_featured(): void
    {
        $this->createAlbum(['name' => 'Featured Album', 'is_featured' => BinaryChoice::YES]);
        $this->createAlbum(['name' => 'Normal Album', 'is_featured' => BinaryChoice::NO]);

        $filter = new AlbumFilterRecord(is_featured: BinaryChoice::YES);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('Featured Album', $results->first()->name);
    }

    // ============================================================
    // TESTS: applyFilters() - ids (StringTypedCollection)
    // ============================================================

    public function test_filter_by_ids(): void
    {
        $album1 = $this->createAlbum(['name' => 'Album 1']);
        $album2 = $this->createAlbum(['name' => 'Album 2']);
        $album3 = $this->createAlbum(['name' => 'Album 3']);

        $ids = new StringTypedCollection;
        $ids->add((string) $album1->id);
        $ids->add((string) $album3->id);

        $filter = new AlbumFilterRecord(ids: $ids);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('id')->contains($album1->id));
        $this->assertFalse($results->pluck('id')->contains($album2->id));
        $this->assertTrue($results->pluck('id')->contains($album3->id));
    }

    public function test_filter_by_ids_with_empty_collection_returns_all(): void
    {
        $this->createAlbum(['name' => 'Album 1']);
        $this->createAlbum(['name' => 'Album 2']);

        $ids = new StringTypedCollection;
        $filter = new AlbumFilterRecord(ids: $ids);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(2, $results);
    }

    // ============================================================
    // TESTS: applyFilters() - search
    // ============================================================

    public function test_filter_by_search_on_name(): void
    {
        $this->createAlbum(['name' => 'Vacation Photos', 'description' => 'Summer vacation']);
        $this->createAlbum(['name' => 'Work Documents', 'description' => 'Office files']);

        $filter = new AlbumFilterRecord(search: 'Vacation');
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('Vacation Photos', $results->first()->name);
    }

    public function test_filter_by_search_on_description(): void
    {
        $this->createAlbum(['name' => 'Vacation Photos', 'description' => 'Summer vacation']);
        $this->createAlbum(['name' => 'Work Documents', 'description' => 'Office files']);

        $filter = new AlbumFilterRecord(search: 'Summer');
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('Vacation Photos', $results->first()->name);
    }

    public function test_filter_by_search_partial_match(): void
    {
        $this->createAlbum(['name' => 'Vacation Photos', 'description' => 'Summer vacation']);
        $this->createAlbum(['name' => 'Vacation Videos', 'description' => 'Winter vacation']);

        $filter = new AlbumFilterRecord(search: 'Vacation');
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(2, $results);
    }

    // ============================================================
    // TESTS: applyFilters() - combinaison de filtres
    // ============================================================

    public function test_filter_with_multiple_conditions(): void
    {
        $this->createAlbum([
            'name' => 'Public User Album',
            'albumable_type' => 'App\Models\User',
            'albumable_id' => 1,
            'is_public' => BinaryChoice::YES,
        ]);

        $this->createAlbum([
            'name' => 'Private User Album',
            'albumable_type' => 'App\Models\User',
            'albumable_id' => 1,
            'is_public' => BinaryChoice::NO,
        ]);

        $this->createAlbum([
            'name' => 'Public Post Album',
            'albumable_type' => 'App\Models\Post',
            'albumable_id' => 1,
            'is_public' => BinaryChoice::YES,
        ]);

        $filter = new AlbumFilterRecord(
            albumable_type: 'App\Models\User',
            albumable_id: 1,
            is_public: BinaryChoice::YES,
        );

        $findBy = new FindByRecord(filters: $filter);
        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('Public User Album', $results->first()->name);
    }

    // ============================================================
    // TESTS: applyFilters() - recherche avec tri
    // ============================================================

    public function test_search_with_sort_by_name(): void
    {
        $this->createAlbum(['name' => 'Zebra Album']);
        $this->createAlbum(['name' => 'Apple Album']);
        $this->createAlbum(['name' => 'Banana Album']);

        $filter = new AlbumFilterRecord;
        $findBy = new FindByRecord(
            filters: $filter,
            sortBy: new SortColumns('name:asc'),
        );

        $results = $this->repository->findBy($findBy);

        $this->assertCount(3, $results);
        $this->assertEquals('Apple Album', $results->get(0)->name);
        $this->assertEquals('Banana Album', $results->get(1)->name);
        $this->assertEquals('Zebra Album', $results->get(2)->name);
    }

    // ============================================================
    // TESTS: setPublic() avec BinaryChoice
    // ============================================================

    public function test_set_public_to_yes(): void
    {
        $album = $this->createAlbum(['is_public' => BinaryChoice::NO]);

        $updated = $this->repository->setPublic($album->id, BinaryChoice::YES);

        $this->assertEquals(BinaryChoice::YES, $updated->is_public);
    }

    public function test_set_public_to_no(): void
    {
        $album = $this->createAlbum(['is_public' => BinaryChoice::YES]);

        $updated = $this->repository->setPublic($album->id, BinaryChoice::NO);

        $this->assertEquals(BinaryChoice::NO, $updated->is_public);
    }

    // ============================================================
    // TESTS: setFeatured() avec BinaryChoice
    // ============================================================

    public function test_set_featured_to_yes(): void
    {
        $album = $this->createAlbum(['is_featured' => BinaryChoice::NO]);

        $updated = $this->repository->setFeatured($album->id, BinaryChoice::YES);

        $this->assertEquals(BinaryChoice::YES, $updated->is_featured);
    }

    public function test_set_featured_to_no(): void
    {
        $album = $this->createAlbum(['is_featured' => BinaryChoice::YES]);

        $updated = $this->repository->setFeatured($album->id, BinaryChoice::NO);

        $this->assertEquals(BinaryChoice::NO, $updated->is_featured);
    }

    // ============================================================
    // METHODES UTILITAIRES
    // ============================================================

    private function createAlbum(array $attributes = []): Album
    {
        $defaults = [
            'name' => 'Test Album '.uniqid(),
            'slug' => 'test-album-'.uniqid(),
            'is_public' => BinaryChoice::YES,
            'is_featured' => BinaryChoice::NO,
            'albumable_type' => 'App\Models\User',
            'albumable_id' => 1,
        ];

        $data = array_merge($defaults, $attributes);

        if (isset($data['is_public']) && is_bool($data['is_public'])) {
            $data['is_public'] = $data['is_public'] ? BinaryChoice::YES : BinaryChoice::NO;
        }

        if (isset($data['is_featured']) && is_bool($data['is_featured'])) {
            $data['is_featured'] = $data['is_featured'] ? BinaryChoice::YES : BinaryChoice::NO;
        }

        $album = new Album;
        $album->fill($data);
        $album->save();

        return $album;
    }
}
