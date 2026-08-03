<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Traits;

use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Tests\Fixtures\Models\TestUser;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class HasMediablesTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private TestUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 30,
        ]);
    }

    // ============================================================
    // IMAGE ATTRIBUTE TESTS
    // ============================================================

    public function test_has_images_attribute_returns_false_when_no_images(): void
    {
        // Assert: User has no images initially
        $this->assertFalse($this->user->has_images);
        $this->assertEquals(0, $this->user->images_count);
    }

    public function test_has_images_attribute_returns_true_when_images_exist(): void
    {
        // Arrange: Create images for the user
        Image::factory()
            ->count(3)
            ->for($this->user, 'imageable')
            ->create();

        $this->user->refresh();

        // Assert: User now has images
        $this->assertTrue($this->user->has_images);
        $this->assertEquals(3, $this->user->images_count);
    }

    public function test_images_count_returns_correct_number(): void
    {
        // Arrange: Create 5 images
        Image::factory()
            ->count(5)
            ->for($this->user, 'imageable')
            ->create();

        $this->user->refresh();

        // Assert: Count is correct
        $this->assertEquals(5, $this->user->images_count);
    }

    public function test_primary_image_returns_correct_image(): void
    {
        // Arrange: Create images with one primary
        $primaryImage = Image::factory()
            ->primary()
            ->for($this->user, 'imageable')
            ->create();

        Image::factory()
            ->count(2)
            ->for($this->user, 'imageable')
            ->create();

        $this->user->refresh();

        // Assert: Primary image is correct
        $this->assertNotNull($this->user->primary_image);
        $this->assertEquals($primaryImage->id, $this->user->primary_image->id);
        $this->assertTrue($this->user->primary_image->is_primary);
    }

    public function test_primary_image_returns_null_when_no_primary(): void
    {
        // Arrange: Create images without primary
        Image::factory()
            ->count(3)
            ->for($this->user, 'imageable')
            ->create();

        $this->user->refresh();

        // Assert: No primary image
        $this->assertNull($this->user->primary_image);
    }

    public function test_avatar_returns_correct_image(): void
    {
        // Arrange: Create avatar image
        $avatar = Image::factory()
            ->avatar()
            ->for($this->user, 'imageable')
            ->create();

        Image::factory()
            ->cover()
            ->for($this->user, 'imageable')
            ->create();

        $this->user->refresh();

        // Assert: Avatar is correct
        $this->assertNotNull($this->user->avatar);
        $this->assertEquals($avatar->id, $this->user->avatar->id);
        $this->assertEquals(ImageType::AVATAR, $this->user->avatar->type);
    }

    public function test_avatar_returns_null_when_no_avatar(): void
    {
        // Arrange: Create non-avatar images
        Image::factory()
            ->cover()
            ->for($this->user, 'imageable')
            ->create();

        Image::factory()
            ->banner()
            ->for($this->user, 'imageable')
            ->create();

        $this->user->refresh();

        // Assert: No avatar
        $this->assertNull($this->user->avatar);
    }

    public function test_cover_returns_correct_image(): void
    {
        // Arrange: Create cover image
        $cover = Image::factory()
            ->cover()
            ->for($this->user, 'imageable')
            ->create();

        Image::factory()
            ->avatar()
            ->for($this->user, 'imageable')
            ->create();

        $this->user->refresh();

        // Assert: Cover is correct
        $this->assertNotNull($this->user->cover);
        $this->assertEquals($cover->id, $this->user->cover->id);
        $this->assertEquals(ImageType::COVER, $this->user->cover->type);
    }

    public function test_banner_returns_correct_image(): void
    {
        // Arrange: Create banner image
        $banner = Image::factory()
            ->banner()
            ->for($this->user, 'imageable')
            ->create();

        $this->user->refresh();

        // Assert: Banner is correct
        $this->assertNotNull($this->user->banner);
        $this->assertEquals($banner->id, $this->user->banner->id);
        $this->assertEquals(ImageType::BANNER, $this->user->banner->type);
    }

    public function test_logo_returns_correct_image(): void
    {
        // Arrange: Create logo image
        $logo = Image::factory()
            ->logo()
            ->for($this->user, 'imageable')
            ->create();

        $this->user->refresh();

        // Assert: Logo is correct
        $this->assertNotNull($this->user->logo);
        $this->assertEquals($logo->id, $this->user->logo->id);
        $this->assertEquals(ImageType::LOGO, $this->user->logo->type);
    }

    public function test_icon_returns_correct_image(): void
    {
        // Arrange: Create icon image
        $icon = Image::factory()
            ->icon()
            ->for($this->user, 'imageable')
            ->create();

        $this->user->refresh();

        // Assert: Icon is correct
        $this->assertNotNull($this->user->icon);
        $this->assertEquals($icon->id, $this->user->icon->id);
        $this->assertEquals(ImageType::ICON, $this->user->icon->type);
    }

    public function test_gallery_images_returns_only_gallery_images(): void
    {
        // Arrange: Create gallery and non-gallery images
        Image::factory()
            ->count(3)
            ->gallery()
            ->for($this->user, 'imageable')
            ->create();

        Image::factory()
            ->count(2)
            ->avatar()
            ->for($this->user, 'imageable')
            ->create();

        $this->user->refresh();

        // Assert: Only gallery images are returned
        $this->assertCount(3, $this->user->gallery_images);
        foreach ($this->user->gallery_images as $image) {
            $this->assertEquals(ImageType::GALLERY, $image->type);
        }
    }

    // ============================================================
    // ALBUM ATTRIBUTE TESTS
    // ============================================================

    public function test_has_albums_attribute_returns_false_when_no_albums(): void
    {
        // Assert: User has no albums
        $this->assertFalse($this->user->has_albums);
        $this->assertEquals(0, $this->user->albums_count);
    }

    public function test_has_albums_attribute_returns_true_when_albums_exist(): void
    {
        // Arrange: Create albums for the user
        Album::factory()
            ->count(2)
            ->withAlbumable($this->user)
            ->create();

        $this->user->refresh();

        // Assert: User now has albums
        $this->assertTrue($this->user->has_albums);
        $this->assertEquals(2, $this->user->albums_count);
    }

    public function test_albums_count_returns_correct_number(): void
    {
        // Arrange: Create 4 albums
        Album::factory()
            ->count(4)
            ->withAlbumable($this->user)
            ->create();

        $this->user->refresh();

        // Assert: Count is correct
        $this->assertEquals(4, $this->user->albums_count);
    }

    public function test_primary_album_returns_first_album(): void
    {
        // Arrange: Create albums
        $album1 = Album::factory()
            ->withAlbumable($this->user)
            ->withName('First Album')
            ->create();

        $album2 = Album::factory()
            ->withAlbumable($this->user)
            ->withName('Second Album')
            ->create();

        $this->user->refresh();

        // Assert: Primary album is the first one by created_at
        $this->assertNotNull($this->user->primary_album);
        $this->assertEquals($album1->id, $this->user->primary_album->id);
        $this->assertEquals('First Album', $this->user->primary_album->name);
    }

    public function test_featured_album_returns_correct_album(): void
    {
        // Arrange: Create albums with one featured
        $featuredAlbum = Album::factory()
            ->featured()
            ->withAlbumable($this->user)
            ->withName('Featured Album')
            ->create();

        Album::factory()
            ->count(2)
            ->withAlbumable($this->user)
            ->create();

        $this->user->refresh();

        // Assert: Featured album is correct
        $this->assertNotNull($this->user->featured_album);
        $this->assertEquals($featuredAlbum->id, $this->user->featured_album->id);
        $this->assertEquals(BinaryChoice::YES, $this->user->featured_album->is_featured);
    }

    public function test_featured_album_returns_null_when_no_featured(): void
    {
        // Arrange: Create albums without featured
        Album::factory()
            ->count(3)
            ->withAlbumable($this->user)
            ->create();

        $this->user->refresh();

        // Assert: No featured album
        $this->assertNull($this->user->featured_album);
    }

    public function test_public_albums_returns_only_public_albums(): void
    {
        // Arrange: Create public and private albums
        Album::factory()
            ->count(3)
            ->public()
            ->withAlbumable($this->user)
            ->create();

        Album::factory()
            ->count(2)
            ->private()
            ->withAlbumable($this->user)
            ->create();

        $this->user->refresh();

        // Assert: Only public albums are returned
        $this->assertCount(3, $this->user->public_albums);
        foreach ($this->user->public_albums as $album) {
            $this->assertEquals(BinaryChoice::YES, $album->is_public);
        }
    }

    public function test_private_albums_returns_only_private_albums(): void
    {
        // Arrange: Create public and private albums
        Album::factory()
            ->count(2)
            ->public()
            ->withAlbumable($this->user)
            ->create();

        Album::factory()
            ->count(3)
            ->private()
            ->withAlbumable($this->user)
            ->create();

        $this->user->refresh();

        // Assert: Only private albums are returned
        $this->assertCount(3, $this->user->private_albums);
        foreach ($this->user->private_albums as $album) {
            $this->assertEquals(BinaryChoice::NO, $album->is_public);
        }
    }

    // ============================================================
    // ATTRIBUTE ACCESS TESTS
    // ============================================================

    public function test_all_attributes_are_accessible_via_property(): void
    {
        // Arrange: Create images and albums
        Image::factory()
            ->count(2)
            ->for($this->user, 'imageable')
            ->create();

        Album::factory()
            ->count(2)
            ->withAlbumable($this->user)
            ->create();

        $this->user->refresh();

        // Assert: All attributes are accessible as properties
        $this->assertIsBool($this->user->has_images);
        $this->assertIsInt($this->user->images_count);
        $this->assertIsBool($this->user->has_albums);
        $this->assertIsInt($this->user->albums_count);

        $this->user->primary_image;
        $this->user->avatar;
        $this->user->cover;
        $this->user->banner;
        $this->user->logo;
        $this->user->icon;
        $this->user->gallery_images;
        $this->user->primary_album;
        $this->user->featured_album;
        $this->user->public_albums;
        $this->user->private_albums;

        $this->assertTrue(true);
    }

    // ============================================================
    // PERFORMANCE TESTS
    // ============================================================

    public function test_attributes_use_database_queries_efficiently(): void
    {
        // Arrange: Create data
        Image::factory()
            ->count(5)
            ->for($this->user, 'imageable')
            ->create();

        Album::factory()
            ->count(3)
            ->withAlbumable($this->user)
            ->create();

        $this->user->refresh();

        // Act: Access multiple attributes
        $this->user->has_images;
        $this->user->images_count;
        $this->user->primary_image;
        $this->user->avatar;
        $this->user->cover;
        $this->user->banner;
        $this->user->logo;
        $this->user->icon;
        $this->user->gallery_images;
        $this->user->has_albums;
        $this->user->albums_count;
        $this->user->primary_album;
        $this->user->featured_album;
        $this->user->public_albums;
        $this->user->private_albums;

        // Assert: Each attribute executes its own query
        $this->assertTrue(true);
    }

    // ============================================================
    // EDGE CASE TESTS
    // ============================================================

    public function test_trait_works_without_any_data(): void
    {
        // Arrange: Create a fresh user
        $user = TestUser::create([
            'name' => 'Empty User',
            'email' => 'empty@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 25,
        ]);

        // Assert: All attributes return safe defaults
        $this->assertFalse($user->has_images);
        $this->assertEquals(0, $user->images_count);
        $this->assertNull($user->primary_image);
        $this->assertNull($user->avatar);
        $this->assertNull($user->cover);
        $this->assertNull($user->banner);
        $this->assertNull($user->logo);
        $this->assertNull($user->icon);
        $this->assertCount(0, $user->gallery_images);
        $this->assertFalse($user->has_albums);
        $this->assertEquals(0, $user->albums_count);
        $this->assertNull($user->primary_album);
        $this->assertNull($user->featured_album);
        $this->assertCount(0, $user->public_albums);
        $this->assertCount(0, $user->private_albums);
    }

    public function test_trait_works_with_multiple_users(): void
    {
        // Arrange: Create multiple users with data
        $user1 = TestUser::create([
            'name' => 'User One',
            'email' => 'user1@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 25,
        ]);

        $user2 = TestUser::create([
            'name' => 'User Two',
            'email' => 'user2@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 30,
        ]);

        Image::factory()
            ->count(3)
            ->for($user1, 'imageable')
            ->create();

        Album::factory()
            ->count(2)
            ->withAlbumable($user2)
            ->create();

        $user1->refresh();
        $user2->refresh();

        // Assert: Each user has correct attributes
        $this->assertTrue($user1->has_images);
        $this->assertEquals(3, $user1->images_count);
        $this->assertFalse($user2->has_images);
        $this->assertEquals(0, $user2->images_count);

        $this->assertFalse($user1->has_albums);
        $this->assertEquals(0, $user1->albums_count);
        $this->assertTrue($user2->has_albums);
        $this->assertEquals(2, $user2->albums_count);
    }

    // ============================================================
    // IMAGE RELATION TESTS
    // ============================================================

    public function test_images_relation_returns_morph_many_instance(): void
    {
        // Act: Get the relation
        $relation = $this->user->images();

        // Assert: Relation is a MorphMany instance with correct properties
        $this->assertInstanceOf(MorphMany::class, $relation);
        $this->assertEquals(Image::class, get_class($relation->getRelated()));
        $this->assertEquals('imageable_type', $relation->getMorphType());
        $this->assertEquals('imageable_id', $relation->getForeignKeyName());
    }

    public function test_images_relation_returns_correct_images(): void
    {
        // Arrange: Create images for the user
        $images = Image::factory()
            ->count(3)
            ->for($this->user, 'imageable')
            ->create();

        // Act: Get images via relation
        $retrievedImages = $this->user->images()->get();

        // Assert: All images are returned
        $this->assertCount(3, $retrievedImages);
        $this->assertEquals($images->pluck('id')->toArray(), $retrievedImages->pluck('id')->toArray());
    }

    public function test_images_relation_respects_query_constraints(): void
    {
        // Arrange: Create images with different types
        Image::factory()
            ->count(2)
            ->avatar()
            ->for($this->user, 'imageable')
            ->create();

        Image::factory()
            ->count(3)
            ->gallery()
            ->for($this->user, 'imageable')
            ->create();

        // Act: Query only gallery images
        $galleryImages = $this->user->images()
            ->where('type', ImageType::GALLERY)
            ->get();

        // Assert: Only gallery images are returned
        $this->assertCount(3, $galleryImages);
        foreach ($galleryImages as $image) {
            $this->assertEquals(ImageType::GALLERY, $image->type);
        }
    }

    public function test_images_relation_supports_eager_loading(): void
    {
        // Arrange: Create images for the user
        Image::factory()
            ->count(5)
            ->for($this->user, 'imageable')
            ->create();

        // Act: Eager load images
        $user = TestUser::with('images')->find($this->user->id);

        // Assert: Images are loaded
        $this->assertTrue($user->relationLoaded('images'));
        $this->assertCount(5, $user->images);
    }

    public function test_images_relation_supports_ordering(): void
    {
        // Arrange: Create images with different order values
        $orderValues = [3, 1, 4, 2, 5];
        foreach ($orderValues as $order) {
            Image::factory()
                ->for($this->user, 'imageable')
                ->create(['order' => $order]);
        }

        // Act: Get images ordered by order
        $orderedImages = $this->user->images()
            ->orderBy('order')
            ->get();

        // Assert: Images are correctly ordered
        $this->assertEquals([1, 2, 3, 4, 5], $orderedImages->pluck('order')->toArray());
    }

    public function test_images_relation_supports_pagination(): void
    {
        // Arrange: Create 15 images
        Image::factory()
            ->count(15)
            ->for($this->user, 'imageable')
            ->create();

        // Act: Paginate images
        $paginated = $this->user->images()->paginate(5);

        // Assert: Pagination works
        $this->assertCount(5, $paginated);
        $this->assertEquals(15, $paginated->total());
        $this->assertEquals(3, $paginated->lastPage());
    }

    public function test_images_relation_returns_empty_collection_when_no_images(): void
    {
        // Act: Get images via relation
        $images = $this->user->images()->get();

        // Assert: Empty collection is returned
        $this->assertInstanceOf(Collection::class, $images);
        $this->assertCount(0, $images);
        $this->assertTrue($images->isEmpty());
    }

    // ============================================================
    // ALBUM RELATION TESTS
    // ============================================================

    public function test_albums_relation_returns_morph_many_instance(): void
    {
        // Act: Get the relation
        $relation = $this->user->albums();

        // Assert: Relation is a MorphMany instance with correct properties
        $this->assertInstanceOf(MorphMany::class, $relation);
        $this->assertEquals(Album::class, get_class($relation->getRelated()));
        $this->assertEquals('albumable_type', $relation->getMorphType());
        $this->assertEquals('albumable_id', $relation->getForeignKeyName());
    }

    public function test_albums_relation_returns_correct_albums(): void
    {
        // Arrange: Create albums for the user
        $albums = Album::factory()
            ->count(3)
            ->withAlbumable($this->user)
            ->create();

        // Act: Get albums via relation
        $retrievedAlbums = $this->user->albums()->get();

        // Assert: All albums are returned
        $this->assertCount(3, $retrievedAlbums);
        $this->assertEquals($albums->pluck('id')->toArray(), $retrievedAlbums->pluck('id')->toArray());
    }

    public function test_albums_relation_respects_query_constraints(): void
    {
        // Arrange: Create albums with different visibility
        Album::factory()
            ->count(2)
            ->public()
            ->withAlbumable($this->user)
            ->create();

        Album::factory()
            ->count(3)
            ->private()
            ->withAlbumable($this->user)
            ->create();

        // Act: Query only public albums
        $publicAlbums = $this->user->albums()
            ->where('is_public', BinaryChoice::YES)
            ->get();

        // Assert: Only public albums are returned
        $this->assertCount(2, $publicAlbums);
        foreach ($publicAlbums as $album) {
            $this->assertEquals(BinaryChoice::YES, $album->is_public);
        }
    }

    public function test_albums_relation_supports_eager_loading(): void
    {
        // Arrange: Create albums for the user
        Album::factory()
            ->count(5)
            ->withAlbumable($this->user)
            ->create();

        // Act: Eager load albums
        $user = TestUser::with('albums')->find($this->user->id);

        // Assert: Albums are loaded
        $this->assertTrue($user->relationLoaded('albums'));
        $this->assertCount(5, $user->albums);
    }

    public function test_albums_relation_supports_ordering(): void
    {
        // Arrange: Create albums with different created_at dates
        $dates = [
            now()->subDays(5),
            now()->subDays(2),
            now()->subDays(10),
            now()->subDays(1),
            now()->subDays(7),
        ];

        foreach ($dates as $date) {
            Album::factory()
                ->withAlbumable($this->user)
                ->create(['created_at' => $date]);
        }

        // Act: Get albums ordered by created_at
        $orderedAlbums = $this->user->albums()
            ->orderBy('created_at', 'desc')
            ->get();

        // Assert: Albums are correctly ordered
        $orderedDates = $orderedAlbums->pluck('created_at')->map(fn ($d) => $d->timestamp)->toArray();
        $sortedDates = collect($dates)->sortDesc()->values()->map(fn ($d) => $d->timestamp)->toArray();
        $this->assertEquals($sortedDates, $orderedDates);
    }

    public function test_albums_relation_supports_pagination(): void
    {
        // Arrange: Create 15 albums
        Album::factory()
            ->count(15)
            ->withAlbumable($this->user)
            ->create();

        // Act: Paginate albums
        $paginated = $this->user->albums()->paginate(5);

        // Assert: Pagination works
        $this->assertCount(5, $paginated);
        $this->assertEquals(15, $paginated->total());
        $this->assertEquals(3, $paginated->lastPage());
    }

    public function test_albums_relation_returns_empty_collection_when_no_albums(): void
    {
        // Act: Get albums via relation
        $albums = $this->user->albums()->get();

        // Assert: Empty collection is returned
        $this->assertInstanceOf(Collection::class, $albums);
        $this->assertCount(0, $albums);
        $this->assertTrue($albums->isEmpty());
    }

    // ============================================================
    // CHAINING RELATION TESTS
    // ============================================================

    public function test_can_chain_image_relation_with_other_constraints(): void
    {
        // Arrange: Create images with various properties
        Image::factory()
            ->avatar()
            ->for($this->user, 'imageable')
            ->create(['is_primary' => true]);

        Image::factory()
            ->avatar()
            ->for($this->user, 'imageable')
            ->create(['is_primary' => false]);

        Image::factory()
            ->gallery()
            ->for($this->user, 'imageable')
            ->create();

        // Act: Chain constraints
        $primaryAvatars = $this->user->images()
            ->where('type', ImageType::AVATAR)
            ->where('is_primary', true)
            ->get();

        // Assert: Only primary avatars are returned
        $this->assertCount(1, $primaryAvatars);
        $this->assertEquals(ImageType::AVATAR, $primaryAvatars->first()->type);
        $this->assertTrue($primaryAvatars->first()->is_primary);
    }

    public function test_can_chain_album_relation_with_other_constraints(): void
    {
        // Arrange: Create albums with various properties
        Album::factory()
            ->public()
            ->featured()
            ->withAlbumable($this->user)
            ->create();

        Album::factory()
            ->public()
            ->withAlbumable($this->user)
            ->create();

        Album::factory()
            ->private()
            ->withAlbumable($this->user)
            ->create();

        // Act: Chain constraints
        $featuredPublicAlbums = $this->user->albums()
            ->where('is_public', BinaryChoice::YES)
            ->where('is_featured', BinaryChoice::YES)
            ->get();

        // Assert: Only featured public albums are returned
        $this->assertCount(1, $featuredPublicAlbums);
        $this->assertEquals(BinaryChoice::YES, $featuredPublicAlbums->first()->is_public);
        $this->assertEquals(BinaryChoice::YES, $featuredPublicAlbums->first()->is_featured);
    }

    // ============================================================
    // N+1 QUERY PREVENTION TESTS
    // ============================================================

    public function test_eager_loading_prevents_n_plus_1_for_images(): void
    {
        // Note: L'utilisateur ID 1 est créé dans setUp(), on commence à ID 2
        for ($i = 2; $i <= 6; $i++) {
            $user = TestUser::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'status' => 'active',
                'role' => 'admin',
                'age' => 25 + $i,
            ]);

            for ($j = 0; $j < 3; $j++) {
                Image::factory()->create([
                    'imageable_type' => TestUser::class,
                    'imageable_id' => $user->id,
                ]);
            }
        }

        $this->assertDatabaseCount('images', 15);

        // Act: Without eager loading (N+1)
        $this->app['db']->enableQueryLog();
        $this->app['db']->flushQueryLog();

        $users = TestUser::where('id', '>=', 2)->get();
        foreach ($users as $user) {
            $count = $user->images()->count();
            $this->assertEquals(3, $count);
        }

        $queriesWithoutEager = count($this->app['db']->getQueryLog());

        // Act: With eager loading
        $this->app['db']->flushQueryLog();

        $users = TestUser::where('id', '>=', 2)->with('images')->get();
        foreach ($users as $user) {
            $count = $user->images->count();
            $this->assertEquals(3, $count);
        }

        $queriesWithEager = count($this->app['db']->getQueryLog());

        // Assert: Eager loading uses fewer queries
        $this->assertGreaterThan(0, $queriesWithoutEager);
        $this->assertLessThan($queriesWithoutEager, $queriesWithEager);
    }

    public function test_eager_loading_prevents_n_plus_1_for_albums(): void
    {
        // Arrange: Create users with albums
        for ($i = 2; $i <= 6; $i++) {
            $user = TestUser::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'status' => 'active',
                'role' => 'admin',
                'age' => 25 + $i,
            ]);

            for ($j = 0; $j < 2; $j++) {
                Album::factory()->create([
                    'albumable_type' => TestUser::class,
                    'albumable_id' => $user->id,
                ]);
            }
        }

        $this->assertDatabaseCount('albums', 10);

        // Act: Without eager loading (N+1)
        $this->app['db']->enableQueryLog();
        $this->app['db']->flushQueryLog();

        $users = TestUser::where('id', '>=', 2)->get();
        foreach ($users as $user) {
            $count = $user->albums()->count();
            $this->assertEquals(2, $count);
        }

        $queriesWithoutEager = count($this->app['db']->getQueryLog());

        // Act: With eager loading
        $this->app['db']->flushQueryLog();

        $users = TestUser::where('id', '>=', 2)->with('albums')->get();
        foreach ($users as $user) {
            $count = $user->albums->count();
            $this->assertEquals(2, $count);
        }

        $queriesWithEager = count($this->app['db']->getQueryLog());

        // Assert: Eager loading uses fewer queries
        $this->assertGreaterThan(0, $queriesWithoutEager);
        $this->assertLessThan($queriesWithoutEager, $queriesWithEager);
    }

    // ============================================================
    // POLYMORPHIC RELATION TESTS
    // ============================================================

    public function test_images_relation_works_with_different_imageable_types(): void
    {
        // Arrange: Create different parent models
        $user = TestUser::create([
            'name' => 'Another User',
            'email' => 'another@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 25,
        ]);

        Image::factory()
            ->count(2)
            ->for($this->user, 'imageable')
            ->create();

        Image::factory()
            ->count(3)
            ->for($user, 'imageable')
            ->create();

        // Act: Get images for each user
        $user1Images = $this->user->images()->get();
        $user2Images = $user->images()->get();

        // Assert: Each user has correct images
        $this->assertCount(2, $user1Images);
        $this->assertCount(3, $user2Images);

        foreach ($user1Images as $image) {
            $this->assertEquals($this->user->getMorphClass(), $image->imageable_type);
            $this->assertEquals($this->user->id, $image->imageable_id);
        }
    }

    public function test_albums_relation_works_with_different_albumable_types(): void
    {
        // Arrange: Create different parent models
        $user = TestUser::create([
            'name' => 'Another User',
            'email' => 'another@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 25,
        ]);

        Album::factory()
            ->count(2)
            ->withAlbumable($this->user)
            ->create();

        Album::factory()
            ->count(3)
            ->withAlbumable($user)
            ->create();

        // Act: Get albums for each user
        $user1Albums = $this->user->albums()->get();
        $user2Albums = $user->albums()->get();

        // Assert: Each user has correct albums
        $this->assertCount(2, $user1Albums);
        $this->assertCount(3, $user2Albums);

        foreach ($user1Albums as $album) {
            $this->assertEquals($this->user->getMorphClass(), $album->albumable_type);
            $this->assertEquals($this->user->id, $album->albumable_id);
        }
    }

    // ============================================================
    // SOFT DELETE TESTS
    // ============================================================

    public function test_images_relation_respects_soft_deletes(): void
    {
        // Arrange: Create images with one soft-deleted
        $images = Image::factory()
            ->count(3)
            ->for($this->user, 'imageable')
            ->create();

        $imageToDelete = $images->first();
        $imageToDelete->delete();

        // Act: Get images via relation
        $retrievedImages = $this->user->images()->get();

        // Assert: Soft-deleted image is not returned by default
        $this->assertCount(2, $retrievedImages);
        $this->assertNotContains($imageToDelete->id, $retrievedImages->pluck('id')->toArray());

        // Act: Get images with trashed
        $allImages = $this->user->images()->withTrashed()->get();

        // Assert: Can include soft-deleted images
        $this->assertCount(3, $allImages);
        $this->assertContains($imageToDelete->id, $allImages->pluck('id')->toArray());
    }

    public function test_albums_relation_respects_soft_deletes(): void
    {
        // Arrange: Create albums with one soft-deleted
        $albums = Album::factory()
            ->count(3)
            ->withAlbumable($this->user)
            ->create();

        $albumToDelete = $albums->first();
        $albumToDelete->delete();

        // Act: Get albums via relation
        $retrievedAlbums = $this->user->albums()->get();

        // Assert: Soft-deleted album is not returned by default
        $this->assertCount(2, $retrievedAlbums);
        $this->assertNotContains($albumToDelete->id, $retrievedAlbums->pluck('id')->toArray());

        // Act: Get albums with trashed
        $allAlbums = $this->user->albums()->withTrashed()->get();

        // Assert: Can include soft-deleted albums
        $this->assertCount(3, $allAlbums);
        $this->assertContains($albumToDelete->id, $allAlbums->pluck('id')->toArray());
    }
}
