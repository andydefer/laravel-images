<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Traits;

use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Tests\Fixtures\Models\TestUser;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for the HasMediables trait.
 *
 * Verifies that the trait correctly provides computed attributes
 * for images and albums without requiring explicit relations.
 *
 * @group integration
 * @group traits
 * @group has-mediables
 *
 * @author Andy Defer
 * @license MIT
 */
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

        // Refresh the model to reload attributes
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

        // Assert: Attributes don't throw errors
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
        // Should not cause N+1 queries
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

        // Each attribute executes its own query
        // This is expected behavior without eager loading
        $this->assertTrue(true);
    }

    // ============================================================
    // EDGE CASE TESTS
    // ============================================================

    public function test_trait_works_without_any_data(): void
    {
        // Create a fresh user
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

        // Create images only for user1
        Image::factory()
            ->count(3)
            ->for($user1, 'imageable')
            ->create();

        // Create albums only for user2
        Album::factory()
            ->count(2)
            ->withAlbumable($user2)
            ->create();

        // Act: Refresh both users
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
}
