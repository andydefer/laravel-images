<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Database\Factories;

use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Database\Factories\AlbumFactory;
use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Tests\Fixtures\Models\TestUser;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Integration tests for the AlbumFactory.
 *
 * Verifies album creation, state methods, relationships with images,
 * polymorphic relations, and deletion behavior.
 *
 * @group integration
 * @group factories
 * @group album-factory
 *
 * @author Andy Defer
 * @license MIT
 */
final class AlbumFactoryTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private AlbumFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = AlbumFactory::new();
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_album(): void
    {
        // Arrange: Factory is already set up in setUp()

        // Act: Create an album
        $album = $this->factory->create();

        // Assert: Verify the album was created with default values
        $this->assertInstanceOf(Album::class, $album);
        $this->assertNotNull($album->id);
        $this->assertNotNull($album->name);
        $this->assertNotNull($album->slug);
        $this->assertNotNull($album->metadata);
        $this->assertEquals(BinaryChoice::YES, $album->is_public);
        $this->assertEquals(BinaryChoice::NO, $album->is_featured);
        $this->assertNull($album->cover_image_id);
        $this->assertNull($album->albumable_type);
        $this->assertNull($album->albumable_id);
        $this->assertDatabaseHas('albums', ['id' => $album->id]);
    }

    public function test_can_create_album_with_custom_name(): void
    {
        // Arrange: Define a custom name
        $name = 'Mon Album Personnel';

        // Act: Create an album with the custom name
        $album = $this->factory->withName($name)->create();

        // Assert: Verify the name and slug were generated correctly
        $this->assertEquals($name, $album->name);
        $this->assertStringContainsString('mon-album-personnel', (string) $album->slug);
    }

    public function test_can_create_album_with_custom_slug(): void
    {
        // Arrange: Define a custom slug
        $slug = 'mon-slug-personnalise-123';

        // Act: Create an album with the custom slug
        $album = $this->factory->withSlug($slug)->create();

        // Assert: Verify the slug was set correctly
        $this->assertEquals($slug, (string) $album->slug);
    }

    public function test_can_create_album_with_description(): void
    {
        // Arrange: Define a description
        $description = 'Ceci est une description de test';

        // Act: Create an album with the description
        $album = $this->factory->withDescription($description)->create();

        // Assert: Verify the description was set correctly
        $this->assertEquals($description, $album->description);
    }

    // ============================================================
    // STATE TESTS
    // ============================================================

    public function test_can_create_public_album(): void
    {
        // Act: Create a public album
        $album = $this->factory->public()->create();

        // Assert: Verify the album is public
        $this->assertEquals(BinaryChoice::YES, $album->is_public);
    }

    public function test_can_create_private_album(): void
    {
        // Act: Create a private album
        $album = $this->factory->private()->create();

        // Assert: Verify the album is private
        $this->assertEquals(BinaryChoice::NO, $album->is_public);
    }

    public function test_can_create_featured_album(): void
    {
        // Act: Create a featured album
        $album = $this->factory->featured()->create();

        // Assert: Verify the album is featured
        $this->assertEquals(BinaryChoice::YES, $album->is_featured);
    }

    public function test_can_create_not_featured_album(): void
    {
        // Act: Create a non-featured album
        $album = $this->factory->notFeatured()->create();

        // Assert: Verify the album is not featured
        $this->assertEquals(BinaryChoice::NO, $album->is_featured);
    }

    public function test_can_create_album_with_cover_image(): void
    {
        // Arrange: Create an album
        $album = $this->factory->create();

        // Act: Create an image and associate it as cover
        $image = Image::factory()
            ->for($album, 'imageable')
            ->create();

        $album->cover_image_id = $image->id;
        $album->save();
        $album->refresh();

        // Assert: Verify the cover image relationship
        $this->assertEquals($image->id, $album->cover_image_id);
        $this->assertInstanceOf(Image::class, $album->coverImage);
        $this->assertEquals($image->id, $album->coverImage->id);
    }

    // ============================================================
    // IMAGE RELATIONSHIP TESTS
    // ============================================================

    public function test_album_has_many_images_relation(): void
    {
        // Arrange: Create an album
        $album = $this->factory->create();

        // Act: Create images and attach them to the album
        $images = Image::factory()
            ->count(3)
            ->for($album, 'imageable')
            ->create();

        foreach ($images as $index => $image) {
            $album->images()->attach($image->id, [
                'order' => $index + 1,
                'created_at' => now(),
            ]);
        }

        $album->refresh();

        // Assert: Verify the relationship
        $this->assertCount(3, $album->images);
        $this->assertInstanceOf(Image::class, $album->images->first());
        $this->assertDatabaseCount('album_image', 3);

        foreach ($album->images as $image) {
            $this->assertEquals($album->getMorphClass(), $image->imageable_type);
            $this->assertEquals($album->id, $image->imageable_id);
        }
    }

    public function test_album_images_have_order_in_pivot(): void
    {
        // Arrange: Create an album
        $album = $this->factory->create();

        // Act: Create images with specific order
        $images = Image::factory()
            ->count(3)
            ->for($album, 'imageable')
            ->create();

        foreach ($images as $index => $image) {
            $album->images()->attach($image->id, [
                'order' => $index + 1,
                'created_at' => now(),
            ]);
        }

        $album->refresh();

        // Assert: Verify the order is preserved in pivot
        foreach ($album->images as $index => $image) {
            $this->assertEquals($index + 1, $image->pivot->order);
        }
    }

    public function test_album_can_add_images_from_existing_list(): void
    {
        // Arrange: Create an album and images
        $album = $this->factory->create();

        $existingImages = Image::factory()
            ->count(3)
            ->for($album, 'imageable')
            ->create();

        $imageIds = $existingImages->pluck('id')->toArray();

        // Act: Attach the existing images
        foreach ($imageIds as $index => $imageId) {
            $album->images()->attach($imageId, [
                'order' => $index + 1,
                'created_at' => now(),
            ]);
        }

        $album->refresh();

        // Assert: Verify all images are attached
        $this->assertCount(3, $album->images);
        $this->assertDatabaseCount('album_image', 3);

        foreach ($album->images as $index => $image) {
            $this->assertEquals($index + 1, $image->pivot->order);
            $this->assertContains($image->id, $imageIds);
        }
    }

    // ============================================================
    // POLYMORPHIC RELATION TESTS
    // ============================================================

    public function test_album_belongs_to_albumable_polymorphic_relation(): void
    {
        // Arrange: Create a user
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 30,
        ]);

        // Act: Create an album with the user as albumable
        $album = $this->factory
            ->withAlbumable($user)
            ->create();

        $images = Image::factory()
            ->count(2)
            ->for($album, 'imageable')
            ->create();

        foreach ($images as $index => $image) {
            $album->images()->attach($image->id, [
                'order' => $index + 1,
                'created_at' => now(),
            ]);
        }

        $album->refresh();

        // Assert: Verify the polymorphic relationship
        $this->assertEquals($user->getMorphClass(), $album->albumable_type);
        $this->assertEquals($user->getKey(), $album->albumable_id);
        $this->assertInstanceOf(TestUser::class, $album->albumable);

        foreach ($album->images as $image) {
            $this->assertEquals($album->getMorphClass(), $image->imageable_type);
            $this->assertEquals($album->id, $image->imageable_id);
        }
    }

    public function test_albumable_can_have_many_albums(): void
    {
        // Arrange: Create a user
        $user = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 28,
        ]);

        // Act: Create 3 albums for the user
        $albums = $this->factory
            ->count(3)
            ->withAlbumable($user)
            ->create();

        // Assert: Verify all albums belong to the user
        foreach ($albums as $album) {
            $this->assertEquals($user->getMorphClass(), $album->albumable_type);
            $this->assertEquals($user->getKey(), $album->albumable_id);
        }
    }

    // ============================================================
    // COMPLEX RELATIONSHIP TESTS
    // ============================================================

    public function test_album_with_images_and_albumable(): void
    {
        // Arrange: Create a user
        $user = TestUser::create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 35,
        ]);

        // Act: Create an album with images
        $album = $this->factory
            ->public()
            ->featured()
            ->withAlbumable($user)
            ->create();

        $images = Image::factory()
            ->count(5)
            ->for($album, 'imageable')
            ->create();

        foreach ($images as $index => $image) {
            $album->images()->attach($image->id, [
                'order' => $index + 1,
                'created_at' => now(),
            ]);
        }

        $album->refresh();

        // Assert: Verify albumable relationship
        $this->assertEquals($user->getMorphClass(), $album->albumable_type);
        $this->assertEquals($user->getKey(), $album->albumable_id);
        $this->assertInstanceOf(TestUser::class, $album->albumable);

        // Assert: Verify images relationship
        $this->assertCount(5, $album->images);
        $this->assertDatabaseCount('album_image', 5);

        foreach ($album->images as $index => $image) {
            $this->assertEquals($index + 1, $image->pivot->order);
            $this->assertInstanceOf(Image::class, $image);
            $this->assertEquals($album->getMorphClass(), $image->imageable_type);
            $this->assertEquals($album->id, $image->imageable_id);
        }
    }

    public function test_album_cover_image_is_one_of_its_images(): void
    {
        // Arrange: Create an album with images
        $album = $this->factory->create();

        $images = Image::factory()
            ->count(3)
            ->for($album, 'imageable')
            ->create();

        foreach ($images as $index => $image) {
            $album->images()->attach($image->id, [
                'order' => $index + 1,
                'created_at' => now(),
            ]);
        }

        // Act: Set the first image as cover
        $coverImage = $album->images->first();
        $album->cover_image_id = $coverImage->id;
        $album->save();
        $album->refresh();

        // Assert: Verify cover image is among album images
        $this->assertEquals($coverImage->id, $album->cover_image_id);
        $this->assertNotNull($album->coverImage);
        $this->assertEquals($coverImage->id, $album->coverImage->id);

        $imageIds = $album->images->pluck('id')->toArray();
        $this->assertContains($coverImage->id, $imageIds);
    }

    // ============================================================
    // VOLUME TESTS
    // ============================================================

    public function test_can_create_multiple_albums_with_images(): void
    {
        // Arrange: Prepare to create multiple albums
        $albums = [];

        // Act: Create 3 albums with 2 images each
        for ($i = 0; $i < 3; $i++) {
            $album = $this->factory->create();

            $images = Image::factory()
                ->count(2)
                ->for($album, 'imageable')
                ->create();

            foreach ($images as $index => $image) {
                $album->images()->attach($image->id, [
                    'order' => $index + 1,
                    'created_at' => now(),
                ]);
            }

            $albums[] = $album;
        }

        // Assert: Verify all records were created
        $this->assertCount(3, $albums);
        $this->assertDatabaseCount('albums', 3);
        $this->assertDatabaseCount('images', 6);
        $this->assertDatabaseCount('album_image', 6);

        foreach ($albums as $album) {
            $this->assertCount(2, $album->images);
            foreach ($album->images as $image) {
                $this->assertInstanceOf(Image::class, $image);
                $this->assertEquals($album->getMorphClass(), $image->imageable_type);
                $this->assertEquals($album->id, $image->imageable_id);
            }
        }
    }

    public function test_can_create_albums_with_different_image_counts(): void
    {
        // Arrange: Create albums with different image counts
        $album1 = $this->factory->create();
        $images1 = Image::factory()->count(1)->for($album1, 'imageable')->create();
        foreach ($images1 as $index => $image) {
            $album1->images()->attach($image->id, ['order' => $index + 1, 'created_at' => now()]);
        }

        $album2 = $this->factory->create();
        $images2 = Image::factory()->count(3)->for($album2, 'imageable')->create();
        foreach ($images2 as $index => $image) {
            $album2->images()->attach($image->id, ['order' => $index + 1, 'created_at' => now()]);
        }

        $album3 = $this->factory->create();
        $images3 = Image::factory()->count(5)->for($album3, 'imageable')->create();
        foreach ($images3 as $index => $image) {
            $album3->images()->attach($image->id, ['order' => $index + 1, 'created_at' => now()]);
        }

        // Assert: Verify each album has the expected number of images
        $this->assertCount(1, $album1->images);
        $this->assertCount(3, $album2->images);
        $this->assertCount(5, $album3->images);

        $this->assertDatabaseCount('album_image', 9);
        $this->assertDatabaseCount('images', 9);
    }

    // ============================================================
    // DELETION TESTS
    // ============================================================

    public function test_deleting_album_detaches_images_but_does_not_delete_images(): void
    {
        // Arrange: Create an album with images
        $album = $this->factory->create();

        $images = Image::factory()
            ->count(3)
            ->for($album, 'imageable')
            ->create();

        foreach ($images as $index => $image) {
            $album->images()->attach($image->id, [
                'order' => $index + 1,
                'created_at' => now(),
            ]);
        }

        $imageIds = $album->images->pluck('id')->toArray();

        // Assert: Before deletion - 3 records in pivot
        $this->assertDatabaseCount('album_image', 3);

        // Act: Soft delete the album
        $album->delete();

        // Assert: Album is soft deleted
        $this->assertSoftDeleted('albums', ['id' => $album->id]);

        // Assert: Pivot table is empty (cascade deletion)
        $this->assertDatabaseCount('album_image', 0);

        // Assert: Images still exist
        foreach ($imageIds as $imageId) {
            $this->assertDatabaseHas('images', [
                'id' => $imageId,
                'imageable_type' => $album->getMorphClass(),
                'imageable_id' => $album->id,
            ]);
        }
    }

    public function test_force_deleting_album_detaches_images(): void
    {
        // Arrange: Create an album with images
        $album = $this->factory->create();

        $images = Image::factory()
            ->count(3)
            ->for($album, 'imageable')
            ->create();

        foreach ($images as $index => $image) {
            $album->images()->attach($image->id, [
                'order' => $index + 1,
                'created_at' => now(),
            ]);
        }

        // Assert: Before deletion - 3 records in pivot
        $this->assertDatabaseCount('album_image', 3);

        // Act: Force delete the album
        $album->forceDelete();

        // Assert: Album is permanently deleted
        $this->assertDatabaseMissing('albums', ['id' => $album->id]);

        // Assert: Pivot table is empty
        $this->assertDatabaseCount('album_image', 0);

        // Assert: Images still exist
        $this->assertDatabaseCount('images', 3);
    }

    // ============================================================
    // METADATA TESTS
    // ============================================================

    public function test_can_create_album_with_custom_metadata(): void
    {
        // Arrange: Define custom metadata
        $metadata = [
            'category' => 'vacances',
            'tags' => ['mer', 'plage', 'soleil'],
            'year' => 2024,
            'location' => 'Kinshasa',
        ];

        // Act: Create an album with custom metadata
        $album = $this->factory->withMetadata($metadata)->create();

        // Assert: Verify metadata values
        $this->assertEquals('vacances', $album->metadata->get('category'));
        $this->assertEquals(2024, $album->metadata->get('year'));
        $this->assertEquals('Kinshasa', $album->metadata->get('location'));
        $this->assertEquals('yes', $album->metadata->get('tags_mer'));
        $this->assertEquals('yes', $album->metadata->get('tags_plage'));
        $this->assertEquals('yes', $album->metadata->get('tags_soleil'));
    }

    // ============================================================
    // VALIDATION TESTS
    // ============================================================

    public function test_album_slug_is_unique(): void
    {
        // Arrange: Create first album
        $album1 = $this->factory->create();

        // Act: Create second album with the same name
        $album2 = $this->factory->withName($album1->name)->create();

        // Assert: Slugs are different
        $this->assertNotEquals((string) $album1->slug, (string) $album2->slug);
        $this->assertStringContainsString(Str::slug($album1->name), (string) $album2->slug);
    }

    public function test_album_has_timestamps(): void
    {
        // Act: Create an album
        $album = $this->factory->create();

        // Assert: Timestamps are set
        $this->assertNotNull($album->created_at);
        $this->assertNotNull($album->updated_at);
    }

    public function test_album_uses_soft_deletes(): void
    {
        // Arrange: Create an album
        $album = $this->factory->create();

        // Act: Delete the album
        $album->delete();

        // Assert: Album is soft deleted
        $this->assertSoftDeleted('albums', ['id' => $album->id]);
        $this->assertDatabaseHas('albums', ['id' => $album->id]);
        $this->assertDatabaseMissing('albums', ['id' => $album->id, 'deleted_at' => null]);
    }

    public function test_album_image_pivot_has_order_and_timestamps(): void
    {
        // Arrange: Create an album with images
        $album = $this->factory->create();

        $images = Image::factory()
            ->count(2)
            ->for($album, 'imageable')
            ->create();

        foreach ($images as $index => $image) {
            $album->images()->attach($image->id, [
                'order' => $index + 1,
                'created_at' => now(),
            ]);
        }

        $album->refresh();

        // Assert: Pivot has order and timestamps
        $pivot = $album->images->first()->pivot;

        $this->assertNotNull($pivot->order);
        $this->assertNotNull($pivot->created_at);
        $this->assertIsInt($pivot->order);
        $this->assertInstanceOf(Carbon::class, $pivot->created_at);
    }

    // ============================================================
    // COMPLETE WORKFLOW TEST
    // ============================================================

    public function test_complete_album_creation_chain(): void
    {
        // Arrange: Create a user
        $user = TestUser::create([
            'name' => 'Alice Wonder',
            'email' => 'alice@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 25,
        ]);

        $metadata = [
            'category' => 'profil',
            'tags' => ['avatar', 'photo', 'profile'],
            'is_private' => 'yes',
        ];

        // Act: Create a complete album with all features
        $album = $this->factory
            ->public()
            ->featured()
            ->withName('Mes Photos de Profil')
            ->withDescription('Album pour mes photos de profil')
            ->withAlbumable($user)
            ->withMetadata($metadata)
            ->create();

        $images = Image::factory()
            ->count(4)
            ->for($album, 'imageable')
            ->create();

        foreach ($images as $index => $image) {
            $album->images()->attach($image->id, [
                'order' => $index + 1,
                'created_at' => now(),
            ]);
        }

        $album->refresh();

        // Assert: Verify all attributes
        $this->assertEquals('Mes Photos de Profil', $album->name);
        $this->assertEquals('Album pour mes photos de profil', $album->description);
        $this->assertEquals(BinaryChoice::YES, $album->is_public);
        $this->assertEquals(BinaryChoice::YES, $album->is_featured);
        $this->assertEquals('profil', $album->metadata->get('category'));
        $this->assertEquals('yes', $album->metadata->get('tags_avatar'));
        $this->assertEquals('yes', $album->metadata->get('tags_photo'));
        $this->assertEquals('yes', $album->metadata->get('tags_profile'));
        $this->assertEquals('yes', $album->metadata->get('is_private'));

        // Assert: Verify albumable relationship
        $this->assertEquals($user->getMorphClass(), $album->albumable_type);
        $this->assertEquals($user->getKey(), $album->albumable_id);
        $this->assertInstanceOf(TestUser::class, $album->albumable);

        // Assert: Verify images
        $this->assertCount(4, $album->images);
        $this->assertDatabaseCount('album_image', 4);

        foreach ($album->images as $index => $image) {
            $this->assertEquals($index + 1, $image->pivot->order);
            $this->assertInstanceOf(Image::class, $image);
            $this->assertEquals($album->getMorphClass(), $image->imageable_type);
            $this->assertEquals($album->id, $image->imageable_id);
        }
    }
}
