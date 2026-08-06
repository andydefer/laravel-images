<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Database\Factories;

use AndyDefer\LaravelImages\Database\Factories\ImageFactory;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Models\Album;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Tests\Fixtures\Models\TestUser;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use AndyDefer\LaravelUtils\Enums\ImageExtension;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for the ImageFactory.
 *
 * Verifies image creation, type states, metadata handling, polymorphic
 * relationships, and file path generation.
 *
 * @group integration
 * @group factories
 * @group image-factory
 *
 * @author Andy Defer
 * @license MIT
 */
final class ImageFactoryTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private ImageFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = ImageFactory::new();
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_image(): void
    {
        // Act: Create an image
        $image = $this->factory->create();

        // Assert: Verify the image was created with default values
        $this->assertInstanceOf(Image::class, $image);
        $this->assertNotNull($image->id);
        $this->assertNotNull($image->path);
        $this->assertNotNull($image->filename);
        $this->assertNotNull($image->original_filename);
        $this->assertNotNull($image->extension);
        $this->assertNotNull($image->mime_type);
        $this->assertNotNull($image->size);
        $this->assertNotNull($image->type);
        $this->assertNotNull($image->width);
        $this->assertNotNull($image->height);
        $this->assertNotNull($image->metadata);
        $this->assertEquals(0, $image->order);
        $this->assertFalse($image->is_primary);
        $this->assertTrue($image->is_processed);
        $this->assertEquals('App\Models\DefaultModel', $image->imageable_type);
        $this->assertEquals(1, $image->imageable_id);
        $this->assertNull($image->uploaded_by_type);
        $this->assertNull($image->uploaded_by_id);
        $this->assertDatabaseHas('images', ['id' => $image->id]);
    }

    public function test_can_create_image_with_for_relation(): void
    {
        // Arrange: Create an album
        $album = Album::factory()->create();

        // Act: Create an image with the album as imageable
        $image = $this->factory
            ->for($album, 'imageable')
            ->create();

        // Assert: Verify the relationship
        $this->assertEquals($album->getMorphClass(), $image->imageable_type);
        $this->assertEquals($album->id, $image->imageable_id);
    }

    public function test_can_create_image_with_custom_path(): void
    {
        // Arrange: Define a custom path
        $path = 'images/custom/path/image.jpg';

        // Act: Create an image with the custom path
        $image = $this->factory->withPath($path)->create();

        // Assert: Verify the path was set
        $this->assertEquals($path, (string) $image->path);
    }

    public function test_can_create_image_with_custom_filename(): void
    {
        // Arrange: Define a custom filename
        $filename = 'custom-filename.jpg';

        // Act: Create an image with the custom filename
        $image = $this->factory->withFilename($filename)->create();

        // Assert: Verify the filename was set
        $this->assertEquals($filename, $image->filename);
    }

    public function test_can_create_image_with_custom_original_filename(): void
    {
        // Arrange: Define a custom original filename
        $originalFilename = 'original-photo.jpg';

        // Act: Create an image with the custom original filename
        $image = $this->factory->withOriginalFilename($originalFilename)->create();

        // Assert: Verify the original filename was set
        $this->assertEquals($originalFilename, $image->original_filename);
    }

    public function test_can_create_image_with_custom_extension(): void
    {
        // Arrange: Define a custom extension
        $extension = 'webp';

        // Act: Create an image with the custom extension
        $image = $this->factory->withExtension($extension)->create();

        // Assert: Verify the extension and MIME type
        $this->assertEquals($extension, $image->extension);
        $this->assertEquals('image/webp', $image->mime_type);
    }

    public function test_can_create_image_with_custom_dimensions(): void
    {
        // Arrange: Define custom dimensions
        $width = 1920;
        $height = 1080;

        // Act: Create an image with custom dimensions
        $image = $this->factory->withDimensions($width, $height)->create();

        // Assert: Verify the dimensions were set
        $this->assertEquals($width, $image->width);
        $this->assertEquals($height, $image->height);
    }

    public function test_can_create_image_with_custom_size(): void
    {
        // Arrange: Define a custom size
        $size = 1024 * 1024;

        // Act: Create an image with the custom size
        $image = $this->factory->withSize($size)->create();

        // Assert: Verify the size was set
        $this->assertEquals($size, $image->size);
    }

    public function test_can_create_image_with_custom_order(): void
    {
        // Arrange: Define a custom order
        $order = 5;

        // Act: Create an image with the custom order
        $image = $this->factory->withOrder($order)->create();

        // Assert: Verify the order was set
        $this->assertEquals($order, $image->order);
    }

    // ============================================================
    // IMAGE TYPE STATE TESTS
    // ============================================================

    public function test_can_create_avatar_image(): void
    {
        // Act: Create an avatar image
        $image = $this->factory->avatar()->create();

        // Assert: Verify the type
        $this->assertEquals(ImageType::AVATAR, $image->type);
    }

    public function test_can_create_cover_image(): void
    {
        // Act: Create a cover image
        $image = $this->factory->cover()->create();

        // Assert: Verify the type
        $this->assertEquals(ImageType::COVER, $image->type);
    }

    public function test_can_create_gallery_image(): void
    {
        // Act: Create a gallery image
        $image = $this->factory->gallery()->create();

        // Assert: Verify the type
        $this->assertEquals(ImageType::GALLERY, $image->type);
    }

    public function test_can_create_banner_image(): void
    {
        // Act: Create a banner image
        $image = $this->factory->banner()->create();

        // Assert: Verify the type
        $this->assertEquals(ImageType::BANNER, $image->type);
    }

    public function test_can_create_logo_image(): void
    {
        // Act: Create a logo image
        $image = $this->factory->logo()->create();

        // Assert: Verify the type
        $this->assertEquals(ImageType::LOGO, $image->type);
    }

    public function test_can_create_icon_image(): void
    {
        // Act: Create an icon image
        $image = $this->factory->icon()->create();

        // Assert: Verify the type
        $this->assertEquals(ImageType::ICON, $image->type);
    }

    public function test_can_create_product_image(): void
    {
        // Act: Create a product image
        $image = $this->factory->product()->create();

        // Assert: Verify the type
        $this->assertEquals(ImageType::PRODUCT, $image->type);
    }

    public function test_can_create_attachment_image(): void
    {
        // Act: Create an attachment image
        $image = $this->factory->attachment()->create();

        // Assert: Verify the type
        $this->assertEquals(ImageType::ATTACHMENT, $image->type);
    }

    public function test_can_create_thumbnail_image(): void
    {
        // Act: Create a thumbnail image
        $image = $this->factory->thumbnail()->create();

        // Assert: Verify the type
        $this->assertEquals(ImageType::THUMBNAIL, $image->type);
    }

    // ============================================================
    // PROPERTY STATE TESTS
    // ============================================================

    public function test_can_create_primary_image(): void
    {
        // Act: Create a primary image
        $image = $this->factory->primary()->create();

        // Assert: Verify the primary flag
        $this->assertTrue($image->is_primary);
    }

    public function test_can_create_processed_image(): void
    {
        // Act: Create a processed image
        $image = $this->factory->processed()->create();

        // Assert: Verify the processed flag
        $this->assertTrue($image->is_processed);
    }

    public function test_can_create_unprocessed_image(): void
    {
        // Act: Create an unprocessed image
        $image = $this->factory->unprocessed()->create();

        // Assert: Verify the unprocessed flag
        $this->assertFalse($image->is_processed);
    }

    public function test_can_create_compressed_image(): void
    {
        // Act: Create a compressed image
        $image = $this->factory->compressed()->create();

        // Assert: Verify the compressed state
        $this->assertStringContainsString('storage/app/compressed/', (string) $image->path);
        $this->assertLessThanOrEqual(300 * 1024, $image->size);
        $this->assertTrue($image->is_processed);
    }

    // ============================================================
    // METADATA TESTS
    // ============================================================

    public function test_can_create_image_with_custom_metadata(): void
    {
        // Arrange: Define custom metadata
        $metadata = [
            'alt_text' => 'Description personnalisée',
            'caption' => 'Légende personnalisée',
            'source' => 'camera',
            'tags' => ['nature', 'voyage'],
            'location' => 'Kinshasa',
        ];

        // Act: Create an image with custom metadata
        $image = $this->factory->withMetadata($metadata)->create();

        // Assert: Verify the metadata was set
        $this->assertEquals($metadata, $image->metadata->toArray());
    }

    public function test_can_create_image_with_alt_text(): void
    {
        // Arrange: Define alt text
        $altText = 'Alternative text for SEO';

        // Act: Create an image with alt text
        $image = $this->factory->withAltText($altText)->create();

        // Assert: Verify the alt text was set
        $this->assertEquals($altText, $image->metadata->getAltText());
    }

    public function test_can_create_image_with_caption(): void
    {
        // Arrange: Define a caption
        $caption = 'Image caption description';

        // Act: Create an image with a caption
        $image = $this->factory->withCaption($caption)->create();

        // Assert: Verify the caption was set
        $this->assertEquals($caption, $image->metadata->getCaption());
    }

    // ============================================================
    // POLYMORPHIC RELATION TESTS
    // ============================================================

    public function test_can_create_image_with_imageable(): void
    {
        // Arrange: Create a user
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 30,
        ]);

        // Act: Create an image with the user as imageable
        $image = $this->factory
            ->for($user, 'imageable')
            ->create();

        // Assert: Verify the polymorphic relationship
        $this->assertEquals($user->getMorphClass(), $image->imageable_type);
        $this->assertEquals($user->getKey(), $image->imageable_id);
        $this->assertInstanceOf(TestUser::class, $image->imageable);
        $this->assertEquals($user->id, $image->imageable->id);
    }

    public function test_can_create_image_with_uploaded_by(): void
    {
        // Arrange: Create an admin user
        $admin = TestUser::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 35,
        ]);

        // Act: Create an image uploaded by the admin
        $image = $this->factory->withUploadedBy($admin)->create();

        // Assert: Verify the uploaded_by relationship
        $this->assertEquals($admin->getMorphClass(), $image->uploaded_by_type);
        $this->assertEquals($admin->getKey(), $image->uploaded_by_id);
    }

    public function test_can_create_image_with_both_imageable_and_uploaded_by(): void
    {
        // Arrange: Create a user and an admin
        $user = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 28,
        ]);

        $admin = TestUser::create([
            'name' => 'Admin User',
            'email' => 'admin2@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 40,
        ]);

        // Act: Create an image with both relationships
        $image = $this->factory
            ->for($user, 'imageable')
            ->withUploadedBy($admin)
            ->create();

        // Assert: Verify both relationships
        $this->assertEquals($user->getMorphClass(), $image->imageable_type);
        $this->assertEquals($user->getKey(), $image->imageable_id);
        $this->assertEquals($admin->getMorphClass(), $image->uploaded_by_type);
        $this->assertEquals($admin->getKey(), $image->uploaded_by_id);
        $this->assertInstanceOf(TestUser::class, $image->imageable);
        $this->assertEquals($user->id, $image->imageable->id);
    }

    // ============================================================
    // CHAINING TESTS
    // ============================================================

    public function test_can_chain_states(): void
    {
        // Act: Create an image with multiple states
        $image = $this->factory
            ->avatar()
            ->primary()
            ->processed()
            ->withDimensions(300, 300)
            ->withSize(2048 * 1024)
            ->create();

        // Assert: Verify all states were applied
        $this->assertEquals(ImageType::AVATAR, $image->type);
        $this->assertTrue($image->is_primary);
        $this->assertTrue($image->is_processed);
        $this->assertEquals(300, $image->width);
        $this->assertEquals(300, $image->height);
        $this->assertEquals(2048 * 1024, $image->size);
    }

    public function test_can_chain_imageable_and_uploaded_by(): void
    {
        // Arrange: Create a user and an admin
        $user = TestUser::create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 32,
        ]);

        $admin = TestUser::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 45,
        ]);

        // Act: Create an image with all options
        $image = $this->factory
            ->banner()
            ->primary()
            ->for($user, 'imageable')
            ->withUploadedBy($admin)
            ->withDimensions(1920, 1080)
            ->withAltText('Banner image alt text')
            ->create();

        // Assert: Verify all relationships and attributes
        $this->assertEquals(ImageType::BANNER, $image->type);
        $this->assertTrue($image->is_primary);
        $this->assertEquals($user->getMorphClass(), $image->imageable_type);
        $this->assertEquals($user->getKey(), $image->imageable_id);
        $this->assertEquals($admin->getMorphClass(), $image->uploaded_by_type);
        $this->assertEquals($admin->getKey(), $image->uploaded_by_id);
        $this->assertEquals(1920, $image->width);
        $this->assertEquals(1080, $image->height);
        $this->assertEquals('Banner image alt text', $image->metadata->getAltText());
    }

    // ============================================================
    // VOLUME TESTS
    // ============================================================

    public function test_can_create_multiple_images(): void
    {
        // Act: Create 5 images
        $images = $this->factory->count(5)->create();

        // Assert: Verify all images were created
        $this->assertCount(5, $images);
        $this->assertDatabaseCount('images', 5);

        foreach ($images as $image) {
            $this->assertInstanceOf(Image::class, $image);
            $this->assertNotNull($image->id);
        }
    }

    public function test_can_create_multiple_images_of_different_types(): void
    {
        // Act: Create images of different types using sequence
        $images = $this->factory
            ->count(4)
            ->sequence(
                ['type' => ImageType::AVATAR],
                ['type' => ImageType::COVER],
                ['type' => ImageType::BANNER],
                ['type' => ImageType::LOGO],
            )
            ->create();

        // Assert: Verify each image has the expected type
        $this->assertCount(4, $images);
        $this->assertEquals(ImageType::AVATAR, $images[0]->type);
        $this->assertEquals(ImageType::COVER, $images[1]->type);
        $this->assertEquals(ImageType::BANNER, $images[2]->type);
        $this->assertEquals(ImageType::LOGO, $images[3]->type);
    }

    // ============================================================
    // FILE PATH TESTS
    // ============================================================

    public function test_image_path_generation_follows_pattern(): void
    {
        // Act: Create an avatar image
        $image = $this->factory->avatar()->create();

        // Assert: Verify the path follows the expected pattern
        $this->assertMatchesRegularExpression(
            '/^images\/avatar\/\d{4}\/\d{2}\/\d{2}\/[a-f0-9-]+\.('.implode('|', array_map('preg_quote', ImageExtension::values())).')$/',
            (string) $image->path
        );
    }

    public function test_image_path_uses_correct_type_folder(): void
    {
        // Act: Create images of different types
        $avatar = $this->factory->avatar()->create();
        $cover = $this->factory->cover()->create();
        $banner = $this->factory->banner()->create();

        // Assert: Verify each image type uses the correct folder
        $this->assertStringContainsString('images/avatar/', (string) $avatar->path);
        $this->assertStringContainsString('images/cover/', (string) $cover->path);
        $this->assertStringContainsString('images/banner/', (string) $banner->path);
    }

    // ============================================================
    // DATA VALIDATION TESTS
    // ============================================================

    public function test_image_has_timestamps(): void
    {
        // Act: Create an image
        $image = $this->factory->create();

        // Assert: Verify timestamps are set
        $this->assertNotNull($image->created_at);
        $this->assertNotNull($image->updated_at);
    }

    public function test_image_uses_soft_deletes(): void
    {
        // Arrange: Create an image
        $image = $this->factory->create();

        // Act: Delete the image
        $image->delete();

        // Assert: Verify the image is soft deleted
        $this->assertSoftDeleted('images', ['id' => $image->id]);
        $this->assertDatabaseHas('images', ['id' => $image->id]);
        $this->assertDatabaseMissing('images', ['id' => $image->id, 'deleted_at' => null]);
    }

    public function test_image_has_mime_type_matching_extension(): void
    {
        // Act & Assert: Test each extension and its MIME type
        $image = $this->factory->withExtension('jpg')->create();
        $this->assertEquals('image/jpeg', $image->mime_type);

        $image = $this->factory->withExtension('png')->create();
        $this->assertEquals('image/png', $image->mime_type);

        $image = $this->factory->withExtension('webp')->create();
        $this->assertEquals('image/webp', $image->mime_type);

        $image = $this->factory->withExtension('gif')->create();
        $this->assertEquals('image/gif', $image->mime_type);
    }

    public function test_image_has_valid_file_size(): void
    {
        // Act & Assert: Test different file sizes
        $image = $this->factory->withSize(1024)->create();
        $this->assertEquals(1024, $image->size);

        $image = $this->factory->withSize(5 * 1024 * 1024)->create();
        $this->assertEquals(5 * 1024 * 1024, $image->size);
    }

    // ============================================================
    // COMPUTED ATTRIBUTE TESTS
    // ============================================================

    public function test_image_has_full_url_attribute(): void
    {
        // Arrange: Create an image with a specific path
        $image = $this->factory->withPath('images/test/image.jpg')->create();

        // Assert: Verify the full URL attribute
        $this->assertStringContainsString('storage/images/test/image.jpg', $image->full_url);
    }

    public function test_image_has_file_size_for_humans_attribute(): void
    {
        // Act & Assert: Test different file sizes
        $image = $this->factory->withSize(1024)->create();
        $this->assertEquals('1 KB', $image->file_size_for_humans);

        $image = $this->factory->withSize(1024 * 1024)->create();
        $this->assertEquals('1 MB', $image->file_size_for_humans);

        $image = $this->factory->withSize(512)->create();
        $this->assertEquals('512 B', $image->file_size_for_humans);
    }

    public function test_image_has_dimensions_attribute(): void
    {
        // Act: Create an image with specific dimensions
        $image = $this->factory->withDimensions(800, 600)->create();

        // Assert: Verify the dimensions attribute
        $this->assertEquals('800x600', $image->dimensions);
    }

    // ============================================================
    // ALBUM RELATIONSHIP TESTS
    // ============================================================

    public function test_image_can_be_attached_to_album(): void
    {
        // Arrange: Create an album
        $album = Album::factory()->create();

        // Act: Create an image and attach it to the album
        $image = $this->factory
            ->for($album, 'imageable')
            ->create();

        $album->images()->attach($image->id, [
            'order' => 1,
            'created_at' => now(),
        ]);

        $album->refresh();

        // Assert: Verify the image is attached to the album
        $this->assertCount(1, $album->images);
        $this->assertEquals($image->id, $album->images->first()->id);
        $this->assertDatabaseHas('album_image', [
            'album_id' => $album->id,
            'image_id' => $image->id,
            'order' => 1,
        ]);
    }

    public function test_image_can_belong_to_multiple_albums(): void
    {
        // Arrange: Create an image and two albums
        $image = $this->factory->create();
        $album1 = Album::factory()->create();
        $album2 = Album::factory()->create();

        // Act: Attach the image to both albums
        $album1->images()->attach($image->id, ['order' => 1, 'created_at' => now()]);
        $album2->images()->attach($image->id, ['order' => 1, 'created_at' => now()]);

        // Assert: Verify the image belongs to both albums
        $this->assertDatabaseCount('album_image', 2);
        $this->assertDatabaseHas('album_image', ['album_id' => $album1->id, 'image_id' => $image->id]);
        $this->assertDatabaseHas('album_image', ['album_id' => $album2->id, 'image_id' => $image->id]);
    }

    // ============================================================
    // COMPLETE WORKFLOW TEST
    // ============================================================

    public function test_complete_image_creation_chain(): void
    {
        // Arrange: Create users
        $user = TestUser::create([
            'name' => 'Charlie Brown',
            'email' => 'charlie@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 29,
        ]);

        $admin = TestUser::create([
            'name' => 'Admin Charlie',
            'email' => 'admin-charlie@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 42,
        ]);

        $metadata = [
            'alt_text' => 'Photo de profil professionnelle',
            'caption' => 'Avatar pour le profil',
            'source' => 'studio',
            'tags' => ['profil', 'professionnel', 'studio'],
            'photographer' => 'Studio Photo Kinshasa',
        ];

        // Act: Create a complete image with all options
        $image = $this->factory
            ->avatar()
            ->primary()
            ->processed()
            ->for($user, 'imageable')
            ->withUploadedBy($admin)
            ->withDimensions(800, 800)
            ->withSize(2 * 1024 * 1024)
            ->withOrder(1)
            ->withMetadata($metadata)
            ->create();

        $image->refresh();

        // Assert: Verify basic attributes
        $this->assertEquals(ImageType::AVATAR, $image->type);
        $this->assertTrue($image->is_primary);
        $this->assertTrue($image->is_processed);
        $this->assertEquals(800, $image->width);
        $this->assertEquals(800, $image->height);
        $this->assertEquals(2 * 1024 * 1024, $image->size);
        $this->assertEquals(1, $image->order);
        $this->assertEquals($metadata, $image->metadata->toArray());

        // Assert: Verify relationships
        $this->assertEquals($user->getMorphClass(), $image->imageable_type);
        $this->assertEquals($user->getKey(), $image->imageable_id);
        $this->assertInstanceOf(TestUser::class, $image->imageable);
        $this->assertEquals($user->id, $image->imageable->id);

        $this->assertEquals($admin->getMorphClass(), $image->uploaded_by_type);
        $this->assertEquals($admin->getKey(), $image->uploaded_by_id);

        // Assert: Verify computed attributes
        $this->assertStringContainsString('storage/', $image->full_url);
        $this->assertEquals('2 MB', $image->file_size_for_humans);
        $this->assertEquals('800x800', $image->dimensions);

        // Assert: Verify database records
        $this->assertDatabaseHas('images', [
            'id' => $image->id,
            'type' => ImageType::AVATAR->value,
            'is_primary' => true,
            'is_processed' => true,
        ]);
    }
}
