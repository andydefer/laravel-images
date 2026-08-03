<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Repositories;

use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\LaravelImages\Collections\ImageTypeCollection;
use AndyDefer\LaravelImages\Enums\ImageExtension;
use AndyDefer\LaravelImages\Enums\ImageMimeType;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Records\ImageFilterRecord;
use AndyDefer\LaravelImages\Repositories\ImageRepository;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use AndyDefer\Repository\Records\FindByRecord;
use AndyDefer\Repository\ValueObjects\SortColumns;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;

final class ImageRepositoryTest extends IntegrationTestCase
{
    use DatabaseMigrations;

    private ImageRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ImageRepository;
    }

    // ============================================================
    // TESTS: applyFilters() - id
    // ============================================================

    public function test_filter_by_id(): void
    {
        $image1 = $this->createImage(['filename' => 'image1.jpg']);
        $image2 = $this->createImage(['filename' => 'image2.jpg']);

        $filter = new ImageFilterRecord(id: $image1->id);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals($image1->id, $results->first()->id);
        $this->assertEquals('image1.jpg', $results->first()->filename);
    }

    // ============================================================
    // TESTS: applyFilters() - ids (StringTypedCollection)
    // ============================================================

    public function test_filter_by_ids(): void
    {
        $image1 = $this->createImage(['filename' => 'image1.jpg']);
        $image2 = $this->createImage(['filename' => 'image2.jpg']);
        $image3 = $this->createImage(['filename' => 'image3.jpg']);

        $ids = new StringTypedCollection;
        $ids->add($image1->id);
        $ids->add($image3->id);

        $filter = new ImageFilterRecord(ids: $ids);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('id')->contains($image1->id));
        $this->assertFalse($results->pluck('id')->contains($image2->id));
        $this->assertTrue($results->pluck('id')->contains($image3->id));
    }

    public function test_filter_by_ids_with_empty_collection_returns_all(): void
    {
        $this->createImage(['filename' => 'image1.jpg']);
        $this->createImage(['filename' => 'image2.jpg']);

        $ids = new StringTypedCollection;
        $filter = new ImageFilterRecord(ids: $ids);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(2, $results);
    }

    // ============================================================
    // TESTS: applyFilters() - imageable_type
    // ============================================================

    public function test_filter_by_imageable_type(): void
    {
        $this->createImage(['imageable_type' => 'App\Models\User', 'filename' => 'user.jpg']);
        $this->createImage(['imageable_type' => 'App\Models\Post', 'filename' => 'post.jpg']);

        $filter = new ImageFilterRecord(imageable_type: 'App\Models\User');
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('user.jpg', $results->first()->filename);
    }

    // ============================================================
    // TESTS: applyFilters() - imageable_id
    // ============================================================

    public function test_filter_by_imageable_id(): void
    {
        $user1Id = (string) Str::uuid();
        $user2Id = (string) Str::uuid();

        $this->createImage(['imageable_type' => 'App\Models\User', 'imageable_id' => $user1Id, 'filename' => 'user1.jpg']);
        $this->createImage(['imageable_type' => 'App\Models\User', 'imageable_id' => $user2Id, 'filename' => 'user2.jpg']);

        $filter = new ImageFilterRecord(imageable_id: $user1Id);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('user1.jpg', $results->first()->filename);
    }

    // ============================================================
    // TESTS: applyFilters() - type
    // ============================================================

    public function test_filter_by_type(): void
    {
        $this->createImage(['type' => ImageType::AVATAR, 'filename' => 'avatar.jpg']);
        $this->createImage(['type' => ImageType::COVER, 'filename' => 'cover.jpg']);

        $filter = new ImageFilterRecord(type: ImageType::AVATAR);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('avatar.jpg', $results->first()->filename);
    }

    // ============================================================
    // TESTS: applyFilters() - types (ImageTypeCollection)
    // ============================================================

    public function test_filter_by_types(): void
    {
        $this->createImage(['type' => ImageType::AVATAR, 'filename' => 'avatar.jpg']);
        $this->createImage(['type' => ImageType::COVER, 'filename' => 'cover.jpg']);
        $this->createImage(['type' => ImageType::GALLERY, 'filename' => 'gallery.jpg']);

        $types = new ImageTypeCollection;
        $types->add(ImageType::AVATAR);
        $types->add(ImageType::GALLERY);

        $filter = new ImageFilterRecord(types: $types);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('filename')->contains('avatar.jpg'));
        $this->assertFalse($results->pluck('filename')->contains('cover.jpg'));
        $this->assertTrue($results->pluck('filename')->contains('gallery.jpg'));
    }

    // ============================================================
    // TESTS: applyFilters() - min_size / max_size
    // ============================================================

    public function test_filter_by_min_size(): void
    {
        $this->createImage(['size' => 100, 'filename' => 'small.jpg']);
        $this->createImage(['size' => 500, 'filename' => 'medium.jpg']);
        $this->createImage(['size' => 1000, 'filename' => 'large.jpg']);

        $filter = new ImageFilterRecord(min_size: 500);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('filename')->contains('medium.jpg'));
        $this->assertTrue($results->pluck('filename')->contains('large.jpg'));
        $this->assertFalse($results->pluck('filename')->contains('small.jpg'));
    }

    public function test_filter_by_max_size(): void
    {
        $this->createImage(['size' => 100, 'filename' => 'small.jpg']);
        $this->createImage(['size' => 500, 'filename' => 'medium.jpg']);
        $this->createImage(['size' => 1000, 'filename' => 'large.jpg']);

        $filter = new ImageFilterRecord(max_size: 500);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('filename')->contains('small.jpg'));
        $this->assertTrue($results->pluck('filename')->contains('medium.jpg'));
        $this->assertFalse($results->pluck('filename')->contains('large.jpg'));
    }

    public function test_filter_by_between_size(): void
    {
        $this->createImage(['size' => 100, 'filename' => 'small.jpg']);
        $this->createImage(['size' => 500, 'filename' => 'medium.jpg']);
        $this->createImage(['size' => 1000, 'filename' => 'large.jpg']);

        $filter = new ImageFilterRecord(min_size: 200, max_size: 800);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('medium.jpg', $results->first()->filename);
    }

    // ============================================================
    // TESTS: applyFilters() - extension
    // ============================================================

    public function test_filter_by_extension(): void
    {
        $this->createImage(['extension' => ImageExtension::JPG, 'filename' => 'image1.jpg']);
        $this->createImage(['extension' => ImageExtension::PNG, 'filename' => 'image2.png']);

        $filter = new ImageFilterRecord(extension: ImageExtension::JPG);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('image1.jpg', $results->first()->filename);
    }

    // ============================================================
    // TESTS: applyFilters() - mime_type
    // ============================================================

    public function test_filter_by_mime_type(): void
    {
        $this->createImage(['mime_type' => ImageMimeType::JPEG, 'filename' => 'image1.jpg']);
        $this->createImage(['mime_type' => ImageMimeType::PNG, 'filename' => 'image2.png']);

        $filter = new ImageFilterRecord(mime_type: ImageMimeType::JPEG);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('image1.jpg', $results->first()->filename);
    }

    // ============================================================
    // TESTS: applyFilters() - search
    // ============================================================

    public function test_filter_by_search_on_filename(): void
    {
        $this->createImage(['filename' => 'vacation-2024.jpg', 'original_filename' => 'img_001.jpg']);
        $this->createImage(['filename' => 'work-document.pdf', 'original_filename' => 'doc_001.pdf']);

        $filter = new ImageFilterRecord(search: 'vacation');
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('vacation-2024.jpg', $results->first()->filename);
    }

    public function test_filter_by_search_on_original_filename(): void
    {
        $this->createImage(['filename' => 'vacation-2024.jpg', 'original_filename' => 'img_001.jpg']);
        $this->createImage(['filename' => 'work-document.pdf', 'original_filename' => 'doc_001.pdf']);

        $filter = new ImageFilterRecord(search: 'img_001');
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('vacation-2024.jpg', $results->first()->filename);
    }

    // ============================================================
    // TESTS: applyFilters() - is_primary
    // ============================================================

    public function test_filter_by_is_primary(): void
    {
        $this->createImage(['is_primary' => true, 'filename' => 'primary.jpg']);
        $this->createImage(['is_primary' => false, 'filename' => 'secondary.jpg']);

        $filter = new ImageFilterRecord(is_primary: true);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('primary.jpg', $results->first()->filename);
        $this->assertTrue($results->first()->is_primary);
    }

    // ============================================================
    // TESTS: applyFilters() - order
    // ============================================================

    public function test_filter_by_order(): void
    {
        $this->createImage(['order' => 1, 'filename' => 'first.jpg']);
        $this->createImage(['order' => 2, 'filename' => 'second.jpg']);
        $this->createImage(['order' => 3, 'filename' => 'third.jpg']);

        $filter = new ImageFilterRecord(order: 2);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('second.jpg', $results->first()->filename);
        $this->assertEquals(2, $results->first()->order);
    }

    public function test_filter_by_min_order(): void
    {
        $this->createImage(['order' => 1, 'filename' => 'first.jpg']);
        $this->createImage(['order' => 2, 'filename' => 'second.jpg']);
        $this->createImage(['order' => 3, 'filename' => 'third.jpg']);

        $filter = new ImageFilterRecord(min_order: 2);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(2, $results);
        $this->assertEquals('second.jpg', $results[0]->filename);
        $this->assertEquals('third.jpg', $results[1]->filename);
    }

    public function test_filter_by_max_order(): void
    {
        $this->createImage(['order' => 1, 'filename' => 'first.jpg']);
        $this->createImage(['order' => 2, 'filename' => 'second.jpg']);
        $this->createImage(['order' => 3, 'filename' => 'third.jpg']);

        $filter = new ImageFilterRecord(max_order: 2);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(2, $results);
        $this->assertEquals('first.jpg', $results[0]->filename);
        $this->assertEquals('second.jpg', $results[1]->filename);
    }

    public function test_filter_by_order_range(): void
    {
        $this->createImage(['order' => 1, 'filename' => 'first.jpg']);
        $this->createImage(['order' => 2, 'filename' => 'second.jpg']);
        $this->createImage(['order' => 3, 'filename' => 'third.jpg']);
        $this->createImage(['order' => 4, 'filename' => 'fourth.jpg']);

        $filter = new ImageFilterRecord(min_order: 2, max_order: 3);
        $findBy = new FindByRecord(filters: $filter);

        $results = $this->repository->findBy($findBy);

        $this->assertCount(2, $results);
        $this->assertEquals('second.jpg', $results[0]->filename);
        $this->assertEquals('third.jpg', $results[1]->filename);
    }

    // ============================================================
    // TESTS: getPrimaryImageForModel()
    // ============================================================

    public function test_get_primary_image_for_model(): void
    {
        $imageableType = 'App\Models\User';
        $imageableId = (string) Str::uuid();

        $this->createImage([
            'imageable_type' => $imageableType,
            'imageable_id' => $imageableId,
            'is_primary' => true,
            'filename' => 'primary.jpg',
        ]);
        $this->createImage([
            'imageable_type' => $imageableType,
            'imageable_id' => $imageableId,
            'is_primary' => false,
            'filename' => 'secondary.jpg',
        ]);

        $primary = $this->repository->getPrimaryImageForModel($imageableType, $imageableId);

        $this->assertNotNull($primary);
        $this->assertEquals('primary.jpg', $primary->filename);
        $this->assertTrue($primary->is_primary);
    }

    public function test_get_primary_image_for_model_returns_null_when_none(): void
    {
        $imageableType = 'App\Models\User';
        $imageableId = (string) Str::uuid();

        $this->createImage([
            'imageable_type' => $imageableType,
            'imageable_id' => $imageableId,
            'is_primary' => false,
            'filename' => 'not_primary.jpg',
        ]);

        $primary = $this->repository->getPrimaryImageForModel($imageableType, $imageableId);

        $this->assertNull($primary);
    }

    // ============================================================
    // TESTS: combinaison de filtres
    // ============================================================

    public function test_filter_with_multiple_conditions(): void
    {
        $imageableId = (string) Str::uuid();

        $this->createImage([
            'filename' => 'user-avatar.jpg',
            'imageable_type' => 'App\Models\User',
            'imageable_id' => $imageableId,
            'type' => ImageType::AVATAR,
            'size' => 500,
        ]);

        $this->createImage([
            'filename' => 'user-cover.jpg',
            'imageable_type' => 'App\Models\User',
            'imageable_id' => $imageableId,
            'type' => ImageType::COVER,
            'size' => 1000,
        ]);

        $filter = new ImageFilterRecord(
            imageable_type: 'App\Models\User',
            imageable_id: $imageableId,
            type: ImageType::AVATAR,
        );

        $findBy = new FindByRecord(filters: $filter);
        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('user-avatar.jpg', $results->first()->filename);
    }

    public function test_filter_with_is_primary_and_type(): void
    {
        $imageableId = (string) Str::uuid();

        $this->createImage([
            'imageable_type' => 'App\Models\User',
            'imageable_id' => $imageableId,
            'type' => ImageType::AVATAR,
            'is_primary' => true,
            'filename' => 'primary_avatar.jpg',
        ]);
        $this->createImage([
            'imageable_type' => 'App\Models\User',
            'imageable_id' => $imageableId,
            'type' => ImageType::COVER,
            'is_primary' => true,
            'filename' => 'primary_cover.jpg',
        ]);

        $filter = new ImageFilterRecord(
            imageable_type: 'App\Models\User',
            imageable_id: $imageableId,
            type: ImageType::AVATAR,
            is_primary: true,
        );

        $findBy = new FindByRecord(filters: $filter);
        $results = $this->repository->findBy($findBy);

        $this->assertCount(1, $results);
        $this->assertEquals('primary_avatar.jpg', $results->first()->filename);
    }

    // ============================================================
    // TESTS: recherche avec tri
    // ============================================================

    public function test_search_with_sort_by_size(): void
    {
        $this->createImage(['filename' => 'small.jpg', 'size' => 100]);
        $this->createImage(['filename' => 'large.jpg', 'size' => 1000]);
        $this->createImage(['filename' => 'medium.jpg', 'size' => 500]);

        $filter = new ImageFilterRecord;
        $findBy = new FindByRecord(
            filters: $filter,
            sortBy: new SortColumns('size:desc'),
        );

        $results = $this->repository->findBy($findBy);

        $this->assertCount(3, $results);
        $this->assertEquals('large.jpg', $results->get(0)->filename);
        $this->assertEquals('medium.jpg', $results->get(1)->filename);
        $this->assertEquals('small.jpg', $results->get(2)->filename);
    }

    public function test_search_with_sort_by_order(): void
    {
        $this->createImage(['order' => 3, 'filename' => 'third.jpg']);
        $this->createImage(['order' => 1, 'filename' => 'first.jpg']);
        $this->createImage(['order' => 2, 'filename' => 'second.jpg']);

        $filter = new ImageFilterRecord;
        $findBy = new FindByRecord(
            filters: $filter,
            sortBy: new SortColumns('order:asc'),
        );

        $results = $this->repository->findBy($findBy);

        $this->assertCount(3, $results);
        $this->assertEquals('first.jpg', $results->get(0)->filename);
        $this->assertEquals('second.jpg', $results->get(1)->filename);
        $this->assertEquals('third.jpg', $results->get(2)->filename);
    }

    // ============================================================
    // METHODES UTILITAIRES
    // ============================================================

    private function createImage(array $attributes = []): Image
    {
        $defaults = [
            'id' => (string) Str::uuid(),
            'path' => 'images/test-'.Str::uuid().'.jpg',
            'filename' => 'test-'.Str::uuid().'.jpg',
            'original_filename' => 'original-test.jpg',
            'extension' => ImageExtension::JPG->value,
            'mime_type' => ImageMimeType::JPEG->value,
            'size' => 1024,
            'type' => ImageType::GALLERY->value,
            'imageable_type' => 'App\Models\User',
            'imageable_id' => (string) Str::uuid(),
            'order' => 0,
            'is_primary' => false,
            'is_processed' => true,
        ];

        $data = array_merge($defaults, $attributes);

        $image = new Image;
        $image->fill($data);
        $image->save();

        return $image;
    }
}
