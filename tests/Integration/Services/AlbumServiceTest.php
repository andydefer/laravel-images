<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Services;

use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Records\AlbumOptionsRecord;
use AndyDefer\LaravelImages\Services\AlbumService;
use AndyDefer\LaravelImages\Services\ImageService;
use AndyDefer\LaravelImages\Tests\Fixtures\Models\TestUser;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use AndyDefer\LaravelImages\ValueObjects\ImageMetadataVO;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;

final class AlbumServiceTest extends IntegrationTestCase
{
    use DatabaseMigrations;

    private AlbumService $albumService;

    private ImageService $imageService;

    private TestUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->albumService = app(AlbumService::class);
        $this->imageService = app(ImageService::class);

        $this->user = TestUser::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'status' => 'active',
            'role' => 'admin',
        ]);
    }

    private function createTestImage(): int
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        $image = $this->imageService->upload($file, $this->user);

        return $image->id;
    }

    // ============================================================
    // TESTS: createAlbum()
    // ============================================================

    public function test_create_album(): void
    {
        $options = AlbumOptionsRecord::from([
            'description' => 'Test description',
            'is_public' => BinaryChoice::YES,
            'is_featured' => BinaryChoice::YES,
            'metadata' => new ImageMetadataVO(['category' => 'test']),
        ]);

        $album = $this->albumService->createAlbum($this->user, 'Test Album', $options);

        $this->assertNotNull($album);
        $this->assertEquals('Test Album', $album->name);
        $this->assertEquals('Test description', $album->description);
        $this->assertEquals(BinaryChoice::YES, $album->is_public);
        $this->assertEquals(BinaryChoice::YES, $album->is_featured);
        $this->assertEquals(TestUser::class, $album->albumable_type);
        $this->assertEquals($this->user->getKey(), $album->albumable_id);
        $this->assertStringContainsString('test-album', $album->slug->getValue());
    }

    public function test_create_album_with_default_options(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Default Album');

        $this->assertNotNull($album);
        $this->assertEquals('Default Album', $album->name);
        $this->assertEquals(BinaryChoice::YES, $album->is_public);
        $this->assertEquals(BinaryChoice::NO, $album->is_featured);
        $this->assertNull($album->description);
    }

    // ============================================================
    // TESTS: addImagesToAlbum()
    // ============================================================

    public function test_add_images_to_album(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $imageId1 = $this->createTestImage();
        $imageId2 = $this->createTestImage();
        $imageId3 = $this->createTestImage();

        $this->albumService->addImagesToAlbum($album, [$imageId1, $imageId2, $imageId3]);

        $images = $this->albumService->getAlbumImages($album);

        $this->assertCount(3, $images);
        $this->assertEquals($imageId1, $images[0]->id);
        $this->assertEquals($imageId2, $images[1]->id);
        $this->assertEquals($imageId3, $images[2]->id);
        $this->assertEquals(1, $images[0]->pivot->order);
        $this->assertEquals(2, $images[1]->pivot->order);
        $this->assertEquals(3, $images[2]->pivot->order);
    }

    // ============================================================
    // TESTS: addImageToAlbum()
    // ============================================================

    public function test_add_image_to_album(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $imageId = $this->createTestImage();

        $this->albumService->addImageToAlbum($album, $imageId);

        $images = $this->albumService->getAlbumImages($album);

        $this->assertCount(1, $images);
        $this->assertEquals($imageId, $images->first()->id);
        $this->assertEquals(1, $images->first()->pivot->order);
    }

    public function test_add_image_to_album_with_order(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $imageId1 = $this->createTestImage();
        $imageId2 = $this->createTestImage();

        $this->albumService->addImageToAlbum($album, $imageId1, 5);
        $this->albumService->addImageToAlbum($album, $imageId2);

        $images = $this->albumService->getAlbumImages($album);

        $this->assertCount(2, $images);
        $this->assertEquals(5, $images[0]->pivot->order);
        $this->assertEquals(6, $images[1]->pivot->order);
    }

    // ============================================================
    // TESTS: removeImageFromAlbum()
    // ============================================================

    public function test_remove_image_from_album(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $imageId1 = $this->createTestImage();
        $imageId2 = $this->createTestImage();

        $this->albumService->addImagesToAlbum($album, [$imageId1, $imageId2]);

        $this->assertCount(2, $this->albumService->getAlbumImages($album));

        $this->albumService->removeImageFromAlbum($album, $imageId1);

        $images = $this->albumService->getAlbumImages($album);

        $this->assertCount(1, $images);
        $this->assertEquals($imageId2, $images->first()->id);
    }

    // ============================================================
    // TESTS: removeAllImagesFromAlbum()
    // ============================================================

    public function test_remove_all_images_from_album(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $imageId1 = $this->createTestImage();
        $imageId2 = $this->createTestImage();

        $this->albumService->addImagesToAlbum($album, [$imageId1, $imageId2]);

        $this->assertCount(2, $this->albumService->getAlbumImages($album));

        $this->albumService->removeAllImagesFromAlbum($album);

        $this->assertCount(0, $this->albumService->getAlbumImages($album));
    }

    // ============================================================
    // TESTS: setCoverImage()
    // ============================================================

    public function test_set_cover_image(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $imageId1 = $this->createTestImage();
        $imageId2 = $this->createTestImage();

        $this->albumService->addImagesToAlbum($album, [$imageId1, $imageId2]);

        $this->assertNull($album->cover_image_id);

        $this->albumService->setCoverImage($album, $imageId2);

        $album->refresh();

        $this->assertEquals($imageId2, $album->cover_image_id);
    }

    // ============================================================
    // TESTS: getAlbumImages()
    // ============================================================

    public function test_get_album_images(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $imageId1 = $this->createTestImage();
        $imageId2 = $this->createTestImage();

        $this->albumService->addImagesToAlbum($album, [$imageId1, $imageId2]);

        $images = $this->albumService->getAlbumImages($album);

        $this->assertCount(2, $images);
        $this->assertEquals($imageId1, $images[0]->id);
        $this->assertEquals($imageId2, $images[1]->id);
    }

    // ============================================================
    // TESTS: getAlbumsForModel()
    // ============================================================

    public function test_get_albums_for_model(): void
    {
        $this->albumService->createAlbum($this->user, 'Album 1', AlbumOptionsRecord::from(['is_public' => BinaryChoice::YES]));
        $this->albumService->createAlbum($this->user, 'Album 2', AlbumOptionsRecord::from(['is_public' => BinaryChoice::NO]));

        $publicAlbums = $this->albumService->getAlbumsForModel($this->user, true);
        $allAlbums = $this->albumService->getAlbumsForModel($this->user, false);

        $this->assertCount(1, $publicAlbums);
        $this->assertCount(2, $allAlbums);
    }

    // ============================================================
    // TESTS: getAlbumBySlug()
    // ============================================================

    public function test_get_album_by_slug(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $found = $this->albumService->getAlbumBySlug($album->slug);

        $this->assertNotNull($found);
        $this->assertEquals($album->id, $found->id);
    }

    public function test_get_album_by_slug_returns_null_when_not_found(): void
    {
        $found = $this->albumService->getAlbumBySlug('nonexistent-slug');

        $this->assertNull($found);
    }

    // ============================================================
    // TESTS: updateAlbum()
    // ============================================================

    public function test_update_album(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Old Name');

        $options = AlbumOptionsRecord::from([
            'name' => 'New Name',
            'description' => 'New description',
            'is_public' => BinaryChoice::NO,
            'is_featured' => BinaryChoice::YES,
            'metadata' => new ImageMetadataVO(['updated' => 'yes']),
        ]);

        $updated = $this->albumService->updateAlbum($album->id, $options);

        $this->assertEquals('New Name', $updated->name);
        $this->assertEquals('New description', $updated->description);
        $this->assertEquals(BinaryChoice::NO, $updated->is_public);
        $this->assertEquals(BinaryChoice::YES, $updated->is_featured);
        $this->assertNotNull($updated->metadata);
    }

    public function test_update_album_throws_exception_when_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Album not found: 99999');

        $options = AlbumOptionsRecord::from(['name' => 'New Name']);
        $this->albumService->updateAlbum(99999, $options);
    }

    // ============================================================
    // TESTS: deleteAlbum()
    // ============================================================

    public function test_delete_album(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $albums = $this->albumService->getAlbumsForModel($this->user, false);
        $this->assertCount(1, $albums);

        $this->albumService->deleteAlbum($album->id);

        $albums = $this->albumService->getAlbumsForModel($this->user, false);
        $this->assertCount(0, $albums);
    }

    public function test_delete_album_with_images(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $imageId1 = $this->createTestImage();
        $imageId2 = $this->createTestImage();

        $this->albumService->addImagesToAlbum($album, [$imageId1, $imageId2]);

        $this->assertCount(2, $this->albumService->getAlbumImages($album));
        $this->assertCount(2, $this->imageService->getImagesForModel($this->user));

        $this->albumService->deleteAlbum($album->id, deleteImages: true);

        $this->assertCount(0, $this->imageService->getImagesForModel($this->user));
    }

    // ============================================================
    // TESTS: reorderAlbumImages()
    // ============================================================

    public function test_reorder_album_images(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $imageId1 = $this->createTestImage();
        $imageId2 = $this->createTestImage();
        $imageId3 = $this->createTestImage();

        $this->albumService->addImagesToAlbum($album, [$imageId1, $imageId2, $imageId3]);

        $reordered = [$imageId3, $imageId1, $imageId2];

        $this->albumService->reorderAlbumImages($album, $reordered);

        $images = $this->albumService->getAlbumImages($album);

        $this->assertEquals($imageId3, $images[0]->id);
        $this->assertEquals(1, $images[0]->pivot->order);
        $this->assertEquals($imageId1, $images[1]->id);
        $this->assertEquals(2, $images[1]->pivot->order);
        $this->assertEquals($imageId2, $images[2]->id);
        $this->assertEquals(3, $images[2]->pivot->order);
    }

    // ============================================================
    // TESTS: duplicateAlbum()
    // ============================================================

    public function test_duplicate_album(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Original Album');

        $imageId1 = $this->createTestImage();
        $imageId2 = $this->createTestImage();

        $this->albumService->addImagesToAlbum($album, [$imageId1, $imageId2]);
        $this->albumService->setCoverImage($album, $imageId1);

        $duplicate = $this->albumService->duplicateAlbum($album, 'Duplicate Album');

        $this->assertNotEquals($album->id, $duplicate->id);
        $this->assertEquals('Duplicate Album', $duplicate->name);
        $this->assertNotEquals($album->slug, $duplicate->slug);
        $this->assertEquals($album->description, $duplicate->description);
        $this->assertEquals($album->is_public, $duplicate->is_public);
        $this->assertEquals(BinaryChoice::NO, $duplicate->is_featured);

        $originalImages = $this->albumService->getAlbumImages($album);
        $duplicateImages = $this->albumService->getAlbumImages($duplicate);

        $this->assertCount(2, $duplicateImages);
        $this->assertEquals($album->cover_image_id, $duplicate->cover_image_id);
    }

    // ============================================================
    // TESTS: countAlbumImages()
    // ============================================================

    public function test_count_album_images(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $this->assertEquals(0, $this->albumService->countAlbumImages($album));

        $imageId1 = $this->createTestImage();
        $imageId2 = $this->createTestImage();

        $this->albumService->addImagesToAlbum($album, [$imageId1, $imageId2]);

        $this->assertEquals(2, $this->albumService->countAlbumImages($album));
    }

    // ============================================================
    // TESTS: isAlbumEmpty()
    // ============================================================

    public function test_is_album_empty(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $this->assertTrue($this->albumService->isAlbumEmpty($album));

        $imageId = $this->createTestImage();
        $this->albumService->addImageToAlbum($album, $imageId);

        $this->assertFalse($this->albumService->isAlbumEmpty($album));
    }

    // ============================================================
    // TESTS: getAlbumCoverImage()
    // ============================================================

    public function test_get_album_cover_image(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $imageId1 = $this->createTestImage();
        $imageId2 = $this->createTestImage();

        $this->albumService->addImagesToAlbum($album, [$imageId1, $imageId2]);
        $this->albumService->setCoverImage($album, $imageId2);

        $cover = $this->albumService->getAlbumCoverImage($album);

        $this->assertNotNull($cover);
        $this->assertEquals($imageId2, $cover->id);
    }

    public function test_get_album_cover_image_returns_first_image_when_no_cover_set(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $imageId1 = $this->createTestImage();
        $imageId2 = $this->createTestImage();

        $this->albumService->addImagesToAlbum($album, [$imageId1, $imageId2]);

        $cover = $this->albumService->getAlbumCoverImage($album);

        $this->assertNotNull($cover);
        $this->assertEquals($imageId1, $cover->id);
    }

    public function test_get_album_cover_image_returns_null_when_album_empty(): void
    {
        $album = $this->albumService->createAlbum($this->user, 'Test Album');

        $cover = $this->albumService->getAlbumCoverImage($album);

        $this->assertNull($cover);
    }

    // ============================================================
    // TESTS: getFeaturedAlbums()
    // ============================================================

    public function test_get_featured_albums(): void
    {
        $this->albumService->createAlbum($this->user, 'Featured 1', AlbumOptionsRecord::from([
            'is_featured' => BinaryChoice::YES,
            'is_public' => BinaryChoice::YES,
        ]));
        $this->albumService->createAlbum($this->user, 'Featured 2', AlbumOptionsRecord::from([
            'is_featured' => BinaryChoice::YES,
            'is_public' => BinaryChoice::YES,
        ]));
        $this->albumService->createAlbum($this->user, 'Not Featured', AlbumOptionsRecord::from([
            'is_featured' => BinaryChoice::NO,
            'is_public' => BinaryChoice::YES,
        ]));
        $this->albumService->createAlbum($this->user, 'Featured Private', AlbumOptionsRecord::from([
            'is_featured' => BinaryChoice::YES,
            'is_public' => BinaryChoice::NO,
        ]));

        $featured = $this->albumService->getFeaturedAlbums(10);

        $this->assertCount(2, $featured);
        $this->assertEquals('Featured 1', $featured[0]->name);
        $this->assertEquals('Featured 2', $featured[1]->name);
    }

    public function test_get_featured_albums_limited(): void
    {
        $this->albumService->createAlbum($this->user, 'Featured 1', AlbumOptionsRecord::from([
            'is_featured' => BinaryChoice::YES,
            'is_public' => BinaryChoice::YES,
        ]));
        $this->albumService->createAlbum($this->user, 'Featured 2', AlbumOptionsRecord::from([
            'is_featured' => BinaryChoice::YES,
            'is_public' => BinaryChoice::YES,
        ]));
        $this->albumService->createAlbum($this->user, 'Featured 3', AlbumOptionsRecord::from([
            'is_featured' => BinaryChoice::YES,
            'is_public' => BinaryChoice::YES,
        ]));

        $featured = $this->albumService->getFeaturedAlbums(2);

        $this->assertCount(2, $featured);
    }
}
