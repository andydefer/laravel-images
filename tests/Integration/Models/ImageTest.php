<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Models;

use AndyDefer\LaravelImages\Database\Factories\AlbumFactory;
use AndyDefer\LaravelImages\Database\Factories\ImageFactory;
use AndyDefer\LaravelImages\Datas\ImageData;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Tests\Fixtures\Models\TestUser;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use AndyDefer\LaravelImages\ValueObjects\ImageMetadataVO;
use AndyDefer\LaravelImages\ValueObjects\ImagePathVO;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for the Image model.
 *
 * Covers attribute accessors, inverse relationships, computed properties,
 * polymorphic relations, factory states, and data transformation to ImageData DTO.
 *
 * @group integration
 * @group models
 * @group image-model
 */
final class ImageTest extends IntegrationTestCase
{
    use RefreshDatabase;

    // ============================================================
    // ATTRIBUTE TESTS
    // ============================================================

    /**
     * Tests that the path attribute returns an ImagePathVO instance.
     */
    public function test_path_attribute_returns_image_path_vo(): void
    {
        $image = ImageFactory::new()
            ->withPath('images/gallery/photo.jpg')
            ->create();

        $this->assertInstanceOf(ImagePathVO::class, $image->path);
        $this->assertSame('images/gallery/photo.jpg', (string) $image->path);
    }

    /**
     * Tests that the metadata attribute returns an ImageMetadataVO instance.
     */
    public function test_metadata_attribute_returns_image_metadata_vo(): void
    {
        $metadata = [
            'alt_text' => 'Test image',
            'caption' => 'Test caption',
            'tags' => ['test', 'photo'],
        ];

        $image = ImageFactory::new()
            ->withMetadata($metadata)
            ->create();

        $this->assertInstanceOf(ImageMetadataVO::class, $image->metadata);
        $this->assertSame('Test image', $image->metadata->getAltText());
        $this->assertSame('Test caption', $image->metadata->getCaption());
    }

    /**
     * Tests that the metadata attribute returns an empty object when no metadata is set.
     */
    public function test_metadata_attribute_returns_empty_object_when_empty(): void
    {
        $image = ImageFactory::new()
            ->withMetadata([])
            ->create();

        $this->assertInstanceOf(ImageMetadataVO::class, $image->metadata);
        $this->assertTrue($image->metadata->isEmpty());
    }

    // ============================================================
    // INVERSE IMAGE TESTS
    // ============================================================

    /**
     * Tests that the inversedImagePath attribute returns the path of the inverse image.
     */
    public function test_inversed_image_path_returns_path_of_inverse_image(): void
    {
        $lightImage = ImageFactory::new()
            ->logo()
            ->withPath('images/logo-light.png')
            ->withFilename('logo-light.png')
            ->create();

        $darkImage = ImageFactory::new()
            ->logo()
            ->withPath('images/logo-dark.png')
            ->withFilename('logo-dark.png')
            ->create();

        $lightImage->update(['inverse_image_id' => $darkImage->id]);
        $darkImage->update(['inverse_image_id' => $lightImage->id]);

        $lightImage->refresh();
        $darkImage->refresh();

        $this->assertInstanceOf(ImagePathVO::class, $darkImage->inversedImagePath);
        $this->assertSame('images/logo-light.png', (string) $darkImage->inversedImagePath);

        $this->assertInstanceOf(ImagePathVO::class, $lightImage->inversedImagePath);
        $this->assertSame('images/logo-dark.png', (string) $lightImage->inversedImagePath);
    }

    /**
     * Tests that the inversedImagePath attribute returns null when no inverse exists.
     */
    public function test_inversed_image_path_returns_null_when_no_inverse(): void
    {
        $image = ImageFactory::new()
            ->withPath('images/logo-light.png')
            ->create();

        $this->assertNull($image->inversedImagePath);
    }

    /**
     * Tests that the has_inverse attribute returns true when an inverse exists.
     */
    public function test_has_inverse_returns_true_when_inverse_exists(): void
    {
        $lightImage = ImageFactory::new()->create();
        $darkImage = ImageFactory::new()
            ->create(['inverse_image_id' => $lightImage->id]);

        $darkImage->refresh();

        $this->assertTrue($darkImage->has_inverse);
    }

    /**
     * Tests that the has_inverse attribute returns false when no inverse exists.
     */
    public function test_has_inverse_returns_false_when_no_inverse(): void
    {
        $image = ImageFactory::new()->create();

        $this->assertFalse($image->has_inverse);
    }

    // ============================================================
    // COMPUTED ATTRIBUTE TESTS
    // ============================================================

    /**
     * Tests that the full_url attribute returns the correct storage URL.
     */
    public function test_full_url_returns_correct_url(): void
    {
        $image = ImageFactory::new()
            ->withPath('images/gallery/photo.jpg')
            ->create();

        $this->assertStringContainsString('http://localhost/images/gallery/photo.jpg', $image->full_url);
    }

    /**
     * Tests that the file_size_for_humans attribute formats file sizes correctly.
     */
    public function test_file_size_for_humans_returns_correct_format(): void
    {
        $image1 = ImageFactory::new()->withSize(1024)->create();
        $this->assertSame('1 KB', $image1->file_size_for_humans);

        $image2 = ImageFactory::new()->withSize(1024 * 1024)->create();
        $this->assertSame('1 MB', $image2->file_size_for_humans);

        $image3 = ImageFactory::new()->withSize(512)->create();
        $this->assertSame('512 B', $image3->file_size_for_humans);
    }

    /**
     * Tests that the dimensions attribute returns the correct format.
     */
    public function test_dimensions_returns_correct_format(): void
    {
        $image = ImageFactory::new()
            ->withDimensions(800, 600)
            ->create();

        $this->assertSame('800x600', $image->dimensions);
    }

    // ============================================================
    // RELATIONSHIP TESTS
    // ============================================================

    /**
     * Tests that the inverseImage relationship returns the correct inverse image.
     */
    public function test_inverse_image_relationship_works(): void
    {
        $lightImage = ImageFactory::new()->create();
        $darkImage = ImageFactory::new()->create();

        $lightImage->update(['inverse_image_id' => $darkImage->id]);
        $darkImage->update(['inverse_image_id' => $lightImage->id]);

        $lightImage->refresh();
        $darkImage->refresh();

        $this->assertInstanceOf(Image::class, $darkImage->inverseImage);
        $this->assertSame($lightImage->id, $darkImage->inverseImage->id);

        $this->assertInstanceOf(Image::class, $lightImage->inverseImage);
        $this->assertSame($darkImage->id, $lightImage->inverseImage->id);
    }

    /**
     * Tests that the inverseImages relationship returns all images linked as inverse.
     */
    public function test_inverse_images_relationship_works(): void
    {
        $parentImage = ImageFactory::new()->create();

        $child1 = ImageFactory::new()
            ->create(['inverse_image_id' => $parentImage->id]);

        $child2 = ImageFactory::new()
            ->create(['inverse_image_id' => $parentImage->id]);

        $parentImage->refresh();

        $this->assertSame($child1->id, $parentImage->inverseImages->first()->id);
        $this->assertSame($child2->id, $parentImage->inverseImages->last()->id);
    }

    // ============================================================
    // POLYMORPHIC RELATIONSHIP TESTS
    // ============================================================

    /**
     * Tests that the imageable relationship works with a parent model.
     */
    public function test_imageable_relationship_works(): void
    {
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 30,
        ]);

        $image = ImageFactory::new()
            ->withImageable($user)
            ->create();

        $image->refresh();

        $this->assertInstanceOf(TestUser::class, $image->imageable);
        $this->assertSame($user->id, $image->imageable->id);
    }

    /**
     * Tests that the imageable relationship works with an uploaded_by user.
     */
    public function test_imageable_relationship_with_uploaded_by(): void
    {
        $uploader = TestUser::create([
            'name' => 'Uploader User',
            'email' => 'uploader@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 30,
        ]);

        $parent = TestUser::create([
            'name' => 'Parent User',
            'email' => 'parent@example.com',
            'status' => 'active',
            'role' => 'user',
            'age' => 25,
        ]);

        $image = ImageFactory::new()
            ->withImageable($parent)
            ->withUploadedBy($uploader)
            ->create();

        $image->refresh();

        $this->assertInstanceOf(TestUser::class, $image->imageable);
        $this->assertSame($parent->id, $image->imageable->id);
        $this->assertSame($uploader->id, (int) $image->uploaded_by_id);
    }

    // ============================================================
    // SOFT DELETE TESTS
    // ============================================================

    /**
     * Tests that the image model uses soft deletes.
     */
    public function test_image_uses_soft_deletes(): void
    {
        $image = ImageFactory::new()->create();
        $imageId = $image->id;

        $image->delete();

        $this->assertSoftDeleted('images', ['id' => $imageId]);
        $this->assertNull(Image::find($imageId));
        $this->assertNotNull(Image::withTrashed()->find($imageId));
    }

    // ============================================================
    // FACTORY TESTS
    // ============================================================

    /**
     * Tests that the factory creates a valid image with default values.
     */
    public function test_factory_creates_valid_image(): void
    {
        $image = ImageFactory::new()->create();

        $this->assertNotNull($image->id);
        $this->assertNotNull($image->path);
        $this->assertNotNull($image->filename);
        $this->assertNotNull($image->type);
        $this->assertIsInt($image->size);
        $this->assertIsInt($image->width);
        $this->assertIsInt($image->height);
        $this->assertFalse($image->is_primary);
        $this->assertTrue($image->is_processed);
        $this->assertInstanceOf(ImagePathVO::class, $image->path);
    }

    /**
     * Tests that the factory can create an avatar image.
     */
    public function test_factory_creates_avatar_image(): void
    {
        $image = ImageFactory::new()->avatar()->create();

        $this->assertSame(ImageType::AVATAR, $image->type);
    }

    /**
     * Tests that the factory can create a gallery image.
     */
    public function test_factory_creates_gallery_image(): void
    {
        $image = ImageFactory::new()->gallery()->create();

        $this->assertSame(ImageType::GALLERY, $image->type);
    }

    /**
     * Tests that the factory can create a logo image.
     */
    public function test_factory_creates_logo_image(): void
    {
        $image = ImageFactory::new()->logo()->create();

        $this->assertSame(ImageType::LOGO, $image->type);
    }

    /**
     * Tests that the factory can create a primary image.
     */
    public function test_factory_creates_primary_image(): void
    {
        $image = ImageFactory::new()->primary()->create();

        $this->assertTrue($image->is_primary);
    }

    // ============================================================
    // DATA CREATION TEST
    // ============================================================

    /**
     * Tests that ImageData can be created from a normalized Image model
     * with all its relationships loaded.
     */
    public function test_can_create_data_from_model_with_all_relations(): void
    {
        // Arrange: Create an uploader and parent models
        $uploader = TestUser::create([
            'name' => 'Uploader User',
            'email' => 'uploader@example.com',
            'status' => 'active',
            'role' => 'admin',
            'age' => 30,
        ]);

        $parent = TestUser::create([
            'name' => 'Parent User',
            'email' => 'parent@example.com',
            'status' => 'active',
            'role' => 'user',
            'age' => 25,
        ]);

        // Arrange: Create an album
        $album = AlbumFactory::new()
            ->withAlbumable($parent)
            ->create();

        // Arrange: Create light and dark images
        $lightImage = ImageFactory::new()
            ->logo()
            ->primary()
            ->processed()
            ->withPath('images/logo-light.png')
            ->withFilename('logo-light.png')
            ->withOriginalFilename('logo-light.png')
            ->withExtension('png')
            ->withDimensions(100, 100)
            ->withSize(1024)
            ->withImageable($parent)
            ->withUploadedBy($uploader)
            ->withAltText('Light logo')
            ->withCaption('Light version of logo')
            ->create();

        $darkImage = ImageFactory::new()
            ->logo()
            ->processed()
            ->withPath('images/logo-dark.png')
            ->withFilename('logo-dark.png')
            ->withOriginalFilename('logo-dark.png')
            ->withExtension('png')
            ->withDimensions(100, 100)
            ->withSize(1024)
            ->withImageable($parent)
            ->withUploadedBy($uploader)
            ->withAltText('Dark logo')
            ->withCaption('Dark version of logo')
            ->create();

        // Arrange: Link inverse images and attach to album
        $lightImage->update(['inverse_image_id' => $darkImage->id]);
        $darkImage->update(['inverse_image_id' => $lightImage->id]);

        $album->images()->attach($lightImage->id, ['order' => 1]);
        $album->images()->attach($darkImage->id, ['order' => 2]);

        $lightImage->refresh();
        $darkImage->refresh();

        // Act: Normalize and create ImageData
        $normalized = action_normalizer_chain(true)->normalize($lightImage);
        $imageData = ImageData::from($normalized);

        // Assert: Verify essential fields
        $this->assertInstanceOf(ImageData::class, $imageData);
        $this->assertSame($lightImage->id, $imageData->id);
        $this->assertSame('images/logo-light.png', (string) $imageData->path);
        $this->assertSame('logo-light.png', $imageData->filename);
        $this->assertSame('Light logo', $imageData->metadata?->getAltText());
        $this->assertSame(ImageType::LOGO, $imageData->type);
        $this->assertNotNull($imageData->inversedImagePath);
        $this->assertSame('images/logo-dark.png', (string) $imageData->inversedImagePath);
        $this->assertSame($parent->id, $imageData->imageableId);
        $this->assertSame((string) $uploader->id, $imageData->uploadedById);
        $this->assertInstanceOf(DateTimeVO::class, $imageData->createdAt);
        $this->assertInstanceOf(DateTimeVO::class, $imageData->updatedAt);
        $this->assertStringContainsString('http://localhost/images/logo-light.png', $imageData->fullUrl);
    }
}
