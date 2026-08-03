<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Models;

use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelCluster\ValueObjects\ClusterVO;
use AndyDefer\LaravelImages\Database\Factories\AlbumFactory;
use AndyDefer\LaravelImages\Database\Factories\ImageFactory;
use AndyDefer\LaravelImages\Datas\AlbumData;
use AndyDefer\LaravelImages\Datas\ImageData;
use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Tests\Fixtures\Models\TestUser;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;
use AndyDefer\PhpVo\ValueObjects\SlugVO;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for the Album model.
 *
 * Covers attribute accessors, relationships, factory states, soft deletes,
 * and data transformation to AlbumData DTO.
 *
 * @group integration
 * @group models
 * @group album-model
 */
final class AlbumTest extends IntegrationTestCase
{
    use RefreshDatabase;

    // ============================================================
    // ATTRIBUTE TESTS
    // ============================================================

    /**
     * Tests that the slug attribute returns a SlugVO instance.
     */
    public function test_slug_attribute_returns_slug_vo(): void
    {
        $album = AlbumFactory::new()
            ->withName('Mon Album Test')
            ->create();

        $this->assertInstanceOf(SlugVO::class, $album->slug);
        $this->assertStringContainsString('mon-album-test', (string) $album->slug);
    }

    /**
     * Tests that the image_count attribute returns the correct number of images.
     */
    public function test_image_count_attribute_returns_correct_count(): void
    {
        $album = AlbumFactory::new()->create();
        $images = ImageFactory::new()->count(3)->create();

        foreach ($images as $index => $image) {
            $album->images()->attach($image->id, ['order' => $index + 1]);
        }

        $album->refresh();

        $this->assertSame(3, $album->image_count);
    }

    /**
     * Tests that the image_count attribute returns zero when the album has no images.
     */
    public function test_image_count_returns_zero_when_no_images(): void
    {
        $album = AlbumFactory::new()->create();

        $this->assertSame(0, $album->image_count);
    }

    // ============================================================
    // RELATIONSHIP TESTS
    // ============================================================

    /**
     * Tests that the images relationship returns the correct collection.
     */
    public function test_images_relationship_works(): void
    {
        $album = AlbumFactory::new()->create();
        $images = ImageFactory::new()->count(3)->create();

        foreach ($images as $index => $image) {
            $album->images()->attach($image->id, ['order' => $index + 1]);
        }

        $album->refresh();

        $this->assertCount(3, $album->images);
        $this->assertInstanceOf(Image::class, $album->images->first());
        $this->assertSame($images->first()->id, $album->images->first()->id);
    }

    /**
     * Tests that images are ordered by the pivot table order column.
     */
    public function test_images_are_ordered_by_pivot_order(): void
    {
        $album = AlbumFactory::new()->create();

        $image1 = ImageFactory::new()->create();
        $image2 = ImageFactory::new()->create();
        $image3 = ImageFactory::new()->create();

        $album->images()->attach($image1->id, ['order' => 3]);
        $album->images()->attach($image2->id, ['order' => 1]);
        $album->images()->attach($image3->id, ['order' => 2]);

        $album->refresh();

        $this->assertSame($image2->id, $album->images[0]->id);
        $this->assertSame($image3->id, $album->images[1]->id);
        $this->assertSame($image1->id, $album->images[2]->id);
    }

    /**
     * Tests that the cover image relationship works correctly.
     */
    public function test_cover_image_relationship_works(): void
    {
        $album = AlbumFactory::new()->create();
        $coverImage = ImageFactory::new()->create();

        $album->cover_image_id = $coverImage->id;
        $album->save();
        $album->refresh();

        $this->assertInstanceOf(Image::class, $album->coverImage);
        $this->assertSame($coverImage->id, $album->coverImage->id);
    }

    /**
     * Tests that the polymorphic albumable relationship works with a parent model.
     */
    public function test_albumable_relationship_works(): void
    {
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 30,
        ]);

        $album = AlbumFactory::new()
            ->withAlbumable($user)
            ->create();

        $album->refresh();

        $this->assertInstanceOf(TestUser::class, $album->albumable);
        $this->assertSame($user->id, $album->albumable->id);
    }

    // ============================================================
    // POLYMORPHIC TESTS
    // ============================================================

    /**
     * Tests that the album properly stores polymorphic relation columns.
     */
    public function test_album_belongs_to_albumable_polymorphically(): void
    {
        $user = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'status' => 'active',
            'role' => 'user',
            'age' => 28,
        ]);

        $album = AlbumFactory::new()
            ->withAlbumable($user)
            ->create();

        $album->refresh();

        $this->assertSame($user->getMorphClass(), $album->albumable_type);
        $this->assertSame($user->id, (int) $album->albumable_id);
        $this->assertInstanceOf(TestUser::class, $album->albumable);
    }

    // ============================================================
    // FACTORY TESTS
    // ============================================================

    /**
     * Tests that the factory creates a valid album with default values.
     */
    public function test_factory_creates_valid_album(): void
    {
        $album = AlbumFactory::new()->create();

        $this->assertNotNull($album->id);
        $this->assertNotNull($album->name);
        $this->assertNotNull($album->slug);
        $this->assertInstanceOf(BinaryChoice::class, $album->is_public);
        $this->assertInstanceOf(BinaryChoice::class, $album->is_featured);
        $this->assertSame(BinaryChoice::YES, $album->is_public);
        $this->assertSame(BinaryChoice::NO, $album->is_featured);
    }

    /**
     * Tests that the factory can create a public album.
     */
    public function test_factory_creates_public_album(): void
    {
        $album = AlbumFactory::new()->public()->create();

        $this->assertSame(BinaryChoice::YES, $album->is_public);
    }

    /**
     * Tests that the factory can create a private album.
     */
    public function test_factory_creates_private_album(): void
    {
        $album = AlbumFactory::new()->private()->create();

        $this->assertSame(BinaryChoice::NO, $album->is_public);
    }

    /**
     * Tests that the factory can create a featured album.
     */
    public function test_factory_creates_featured_album(): void
    {
        $album = AlbumFactory::new()->featured()->create();

        $this->assertSame(BinaryChoice::YES, $album->is_featured);
    }

    /**
     * Tests that the factory can set a custom album name.
     */
    public function test_factory_creates_album_with_custom_name(): void
    {
        $name = 'Mon Album Personnel';
        $album = AlbumFactory::new()->withName($name)->create();

        $this->assertSame($name, $album->name);
        $this->assertStringContainsString('mon-album-personnel', (string) $album->slug);
    }

    /**
     * Tests that the factory can set a custom description.
     */
    public function test_factory_creates_album_with_description(): void
    {
        $description = 'Ceci est une description de test';
        $album = AlbumFactory::new()->withDescription($description)->create();

        $this->assertSame($description, $album->description);
    }

    /**
     * Tests that the factory can set custom metadata.
     */
    public function test_factory_creates_album_with_metadata(): void
    {
        $metadata = [
            'category' => 'vacances',
            'tags' => ['mer', 'plage'],
            'year' => 2024,
        ];

        $album = AlbumFactory::new()->withMetadata($metadata)->create();

        $this->assertInstanceOf(ClusterVO::class, $album->metadata);
        $this->assertSame('vacances', $album->metadata->get('category'));
        $this->assertSame('yes', $album->metadata->get('tags_mer'));
        $this->assertSame('yes', $album->metadata->get('tags_plage'));
        $this->assertSame('2024', (string) $album->metadata->get('year'));
    }

    // ============================================================
    // SOFT DELETE TESTS
    // ============================================================

    /**
     * Tests that the album uses soft deletes.
     */
    public function test_album_uses_soft_deletes(): void
    {
        $album = AlbumFactory::new()->create();
        $albumId = $album->id;

        $album->delete();

        $this->assertSoftDeleted('albums', ['id' => $albumId]);
        $this->assertNull(Album::find($albumId));
        $this->assertNotNull(Album::withTrashed()->find($albumId));
    }

    /**
     * Tests that deleting an album detaches its images but does not delete them.
     */
    public function test_deleting_album_detaches_images_but_does_not_delete_images(): void
    {
        $album = AlbumFactory::new()->create();
        $images = ImageFactory::new()->count(3)->create();

        foreach ($images as $image) {
            $album->images()->attach($image->id);
        }

        $album->delete();

        $this->assertSoftDeleted('albums', ['id' => $album->id]);
        $this->assertDatabaseCount('images', 3);
        $this->assertDatabaseCount('album_image', 0);
    }

    // ============================================================
    // DATA CREATION TEST
    // ============================================================

    /**
     * Tests that AlbumData can be created from a normalized Album model
     * with all its relationships loaded.
     */
    public function test_can_create_album_data_from_model_with_all_relations(): void
    {
        // Arrange: Create a parent model
        $parent = TestUser::create([
            'name' => 'Parent User',
            'email' => 'parent@example.com',
            'status' => 'active',
            'role' => 'user',
            'age' => 25,
        ]);

        // Arrange: Create an album with all options
        $album = AlbumFactory::new()
            ->withAlbumable($parent)
            ->withName('Mon Album Test')
            ->withDescription('Description de test')
            ->withMetadata(['category' => 'photos', 'year' => 2024])
            ->featured()
            ->create();

        // Arrange: Create two images
        $image1 = ImageFactory::new()
            ->logo()
            ->withPath('images/logo1.png')
            ->withFilename('logo1.png')
            ->withImageable($parent)
            ->create();

        $image2 = ImageFactory::new()
            ->logo()
            ->withPath('images/logo2.png')
            ->withFilename('logo2.png')
            ->withImageable($parent)
            ->create();

        // Arrange: Attach images and set cover
        $album->images()->attach($image1->id, ['order' => 1]);
        $album->images()->attach($image2->id, ['order' => 2]);
        $album->cover_image_id = $image1->id;
        $album->save();

        // Arrange: Refresh and eager load relations
        $album->refresh();
        $album->load(['images', 'coverImage']);

        // Act: Normalize and create AlbumData
        $normalized = action_normalizer_chain(true)->normalize($album);
        $albumData = AlbumData::from($normalized);

        // Assert: Verify album data
        $this->assertInstanceOf(AlbumData::class, $albumData);
        $this->assertSame($album->id, $albumData->id);
        $this->assertSame('Mon Album Test', $albumData->name);
        $this->assertInstanceOf(SlugVO::class, $albumData->slug);
        $this->assertSame('Description de test', $albumData->description);
        $this->assertSame($image1->id, $albumData->coverImageId);
        $this->assertInstanceOf(BinaryChoice::class, $albumData->isPublic);
        $this->assertInstanceOf(BinaryChoice::class, $albumData->isFeatured);
        $this->assertSame(BinaryChoice::YES, $albumData->isFeatured);
        $this->assertInstanceOf(ClusterVO::class, $albumData->metadata);
        $this->assertSame('photos', $albumData->metadata->get('category'));
        $this->assertSame('2024', (string) $albumData->metadata->get('year'));
        $this->assertSame($parent->getMorphClass(), $albumData->albumableType);
        $this->assertSame($parent->id, (int) $albumData->albumableId);
        $this->assertSame(2, $albumData->imageCount);

        // Assert: Verify images collection
        $this->assertNotNull($albumData->images);
        $this->assertCount(2, $albumData->images->toArray());
        $this->assertSame($image1->id, $albumData->images->first()->id);
        $this->assertSame($image2->id, $albumData->images->last()->id);

        // Assert: Verify cover image
        $this->assertNotNull($albumData->coverImage);
        $this->assertInstanceOf(ImageData::class, $albumData->coverImage);
        $this->assertSame($image1->id, $albumData->coverImage->id);

        // Assert: Verify timestamps
        $this->assertInstanceOf(DateTimeVO::class, $albumData->createdAt);
        $this->assertInstanceOf(DateTimeVO::class, $albumData->updatedAt);
    }
}
