<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Tests\Integration\Services;

use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Records\ImageOptionsRecord;
use AndyDefer\LaravelImages\Records\ImageRecord;
use AndyDefer\LaravelImages\Services\ImageService;
use AndyDefer\LaravelImages\Tests\Fixtures\Models\TestUser;
use AndyDefer\LaravelImages\Tests\IntegrationTestCase;
use AndyDefer\LaravelImages\ValueObjects\ImageMetadataVO;
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;

final class ImageServiceTest extends IntegrationTestCase
{
    use DatabaseMigrations;

    private ImageService $imageService;

    private TestUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->imageService = app(ImageService::class);

        $this->user = TestUser::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'status' => 'active',
            'role' => 'admin',
        ]);
    }

    private function createTestImage(array $options = []): int
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        $imageOptions = new ImageOptionsRecord(
            alt_text: $options['alt_text'] ?? null,
            caption: $options['caption'] ?? null,
            order: $options['order'] ?? null,
            is_primary: $options['is_primary'] ?? null,
        );
        $image = $this->imageService->upload($file, $this->user, null, ImageType::GALLERY, $imageOptions);

        return $image->id;
    }

    public function test_find_image_returns_image_when_exists(): void
    {
        $imageId = $this->createTestImage();
        $found = $this->imageService->findImage($imageId);

        $this->assertNotNull($found);
        $this->assertEquals($imageId, $found->id);
    }

    public function test_find_image_returns_null_when_not_exists(): void
    {
        $found = $this->imageService->findImage(99999);
        $this->assertNull($found);
    }

    public function test_upload_image_with_all_options(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $options = new ImageOptionsRecord(
            alt_text: 'Test alt text',
            caption: 'Test caption',
            order: 5,
            is_primary: true,
            metadata: new ImageMetadataVO(['source' => 'test']),
        );

        $image = $this->imageService->upload($file, $this->user, null, ImageType::AVATAR, $options);

        $this->assertNotNull($image);
        $this->assertEquals(ImageType::AVATAR, $image->type);
        $this->assertEquals(5, $image->order);
        $this->assertTrue($image->is_primary);

        $metadata = $image->metadata;
        $this->assertNotNull($metadata);
        $this->assertEquals('Test alt text', $metadata->getAltText());
        $this->assertEquals('Test caption', $metadata->getCaption());

        $this->assertEquals(TestUser::class, $image->imageable_type);
        $this->assertEquals($this->user->getKey(), $image->imageable_id);
    }

    public function test_upload_image_with_default_options(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $image = $this->imageService->upload($file, $this->user);

        $this->assertNotNull($image);
        $this->assertEquals(ImageType::GALLERY, $image->type);
        $this->assertEquals(0, $image->order);
        $this->assertFalse($image->is_primary);
        $this->assertTrue($image->is_processed);
    }

    public function test_upload_image_validates_file_size(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File size exceeds limit');

        $file = UploadedFile::fake()->image('test.jpg', 800, 600)->size(3000);
        $this->imageService->upload($file, $this->user, null, ImageType::AVATAR);
    }

    public function test_upload_image_validates_mime_type(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MIME type');

        $file = UploadedFile::fake()->create('test.txt', 100);
        $this->imageService->upload($file, $this->user);
    }

    public function test_upload_multiple_images(): void
    {
        $files = [
            UploadedFile::fake()->image('test1.jpg', 800, 600),
            UploadedFile::fake()->image('test2.jpg', 800, 600),
            UploadedFile::fake()->image('test3.jpg', 800, 600),
        ];

        $images = $this->imageService->uploadMultiple($files, $this->user);

        $this->assertCount(3, $images);

        foreach ($images as $index => $image) {
            $this->assertEquals($index + 1, $image->order);
        }
    }

    public function test_upload_multiple_with_options(): void
    {
        $files = [
            UploadedFile::fake()->image('test1.jpg', 800, 600),
            UploadedFile::fake()->image('test2.jpg', 800, 600),
        ];

        $options = new ImageOptionsRecord(
            alt_text: 'Multiple alt text',
            is_primary: true,
        );

        $images = $this->imageService->uploadMultiple($files, $this->user, null, ImageType::GALLERY, $options);

        $this->assertCount(2, $images);

        foreach ($images as $image) {
            $this->assertTrue($image->is_primary);
            $this->assertNotNull($image->metadata);
            $this->assertEquals('Multiple alt text', $image->metadata->getAltText());
        }
    }

    public function test_update_image_metadata(): void
    {
        $imageId = $this->createTestImage();

        $newMetadata = new ImageMetadataVO([
            'alt_text' => 'Updated alt text',
            'caption' => 'Updated caption',
        ]);

        $updateRecord = ImageRecord::from([
            'metadata' => $newMetadata,
            'order' => 10,
            'is_primary' => true,
        ]);

        $updated = $this->imageService->update($updateRecord, $imageId);

        $this->assertEquals(10, $updated->order);
        $this->assertTrue($updated->is_primary);
        $this->assertNotNull($updated->metadata);
        $this->assertEquals('Updated alt text', $updated->metadata->getAltText());
        $this->assertEquals('Updated caption', $updated->metadata->getCaption());
    }

    public function test_delete_image(): void
    {
        $imageId = $this->createTestImage();

        $this->assertNotNull($this->imageService->findImage($imageId));

        $this->imageService->delete($imageId);

        $this->assertNull($this->imageService->findImage($imageId));
    }

    public function test_delete_image_throws_exception_when_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Image not found: 99999');

        $this->imageService->delete(99999);
    }

    public function test_delete_multiple_images(): void
    {
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $ids[] = $this->createTestImage();
        }

        $this->assertCount(3, $this->imageService->getImagesForModel($this->user));

        $this->imageService->deleteMultiple($ids);

        $this->assertCount(0, $this->imageService->getImagesForModel($this->user));
    }

    public function test_delete_all_for_model(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->createTestImage();
        }

        $this->assertCount(2, $this->imageService->getImagesForModel($this->user));

        $this->imageService->deleteAllForModel($this->user);

        $this->assertCount(0, $this->imageService->getImagesForModel($this->user));
    }

    public function test_get_images_for_model(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->createTestImage();
        }

        $images = $this->imageService->getImagesForModel($this->user);
        $this->assertCount(2, $images);
    }

    public function test_get_images_for_model_with_type_filter(): void
    {
        $file1 = UploadedFile::fake()->image('test1.jpg', 800, 600);
        $this->imageService->upload($file1, $this->user, null, ImageType::AVATAR);

        $file2 = UploadedFile::fake()->image('test2.jpg', 800, 600);
        $this->imageService->upload($file2, $this->user, null, ImageType::COVER);

        $avatars = $this->imageService->getImagesForModel($this->user, ImageType::AVATAR);
        $covers = $this->imageService->getImagesForModel($this->user, ImageType::COVER);

        $this->assertCount(1, $avatars);
        $this->assertCount(1, $covers);
        $this->assertEquals(ImageType::AVATAR, $avatars->first()->type);
        $this->assertEquals(ImageType::COVER, $covers->first()->type);
    }

    public function test_get_primary_image(): void
    {
        $primaryId = $this->createTestImage(['is_primary' => true]);
        $this->createTestImage(['is_primary' => false]);

        $primary = $this->imageService->getPrimaryImage($this->user);

        $this->assertNotNull($primary);
        $this->assertEquals($primaryId, $primary->id);
        $this->assertTrue($primary->is_primary);
    }

    public function test_get_primary_image_returns_null_when_none(): void
    {
        $this->createTestImage(['is_primary' => false]);
        $this->createTestImage(['is_primary' => false]);

        $primary = $this->imageService->getPrimaryImage($this->user);
        $this->assertNull($primary);
    }

    public function test_set_as_primary(): void
    {
        $image1Id = $this->createTestImage(['is_primary' => false]);
        $image2Id = $this->createTestImage(['is_primary' => false]);

        $this->imageService->setAsPrimary($image2Id, $this->user);

        $primary = $this->imageService->getPrimaryImage($this->user);

        $this->assertNotNull($primary);
        $this->assertEquals($image2Id, $primary->id);
        $this->assertTrue($primary->is_primary);

        $image1 = $this->imageService->findImage($image1Id);
        $this->assertFalse($image1->is_primary);
    }

    public function test_count_images(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->createTestImage();
        }

        $count = $this->imageService->countImages($this->user);
        $this->assertEquals(2, $count);
    }

    public function test_count_images_with_type_filter(): void
    {
        $file1 = UploadedFile::fake()->image('test1.jpg', 800, 600);
        $this->imageService->upload($file1, $this->user, null, ImageType::AVATAR);

        $file2 = UploadedFile::fake()->image('test2.jpg', 800, 600);
        $this->imageService->upload($file2, $this->user, null, ImageType::COVER);

        $avatarCount = $this->imageService->countImages($this->user, ImageType::AVATAR);
        $coverCount = $this->imageService->countImages($this->user, ImageType::COVER);

        $this->assertEquals(1, $avatarCount);
        $this->assertEquals(1, $coverCount);
    }

    public function test_get_images_updated_after(): void
    {
        $this->createTestImage();

        $date = DateTimeVO::from(now()->subDay());
        $images = $this->imageService->getImagesUpdatedAfter($date);

        $this->assertGreaterThan(0, $images->count());
    }

    public function test_reorder_images(): void
    {
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $ids[] = $this->createTestImage(['order' => $i + 1]);
        }

        $reversed = array_reverse($ids);

        $this->imageService->reorder($reversed);

        $images = $this->imageService->getImagesForModel($this->user);

        $this->assertEquals($reversed[0], $images[0]->id);
        $this->assertEquals($reversed[1], $images[1]->id);
        $this->assertEquals($reversed[2], $images[2]->id);

        $this->assertEquals(1, $images[0]->order);
        $this->assertEquals(2, $images[1]->order);
        $this->assertEquals(3, $images[2]->order);
    }

    public function test_get_thumbnail_url_returns_correct_url(): void
    {
        $imageId = $this->createTestImage();

        $thumbnailUrl = $this->imageService->getThumbnailUrl($imageId);

        $this->assertStringContainsString('storage/', $thumbnailUrl);
        $this->assertStringContainsString('_small.jpg', $thumbnailUrl);
    }

    public function test_get_thumbnail_url_with_custom_size(): void
    {
        $imageId = $this->createTestImage();

        $thumbnailUrl = $this->imageService->getThumbnailUrl($imageId, 'large');

        $this->assertStringContainsString('_large.jpg', $thumbnailUrl);
    }

    public function test_get_thumbnail_url_throws_exception_when_image_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Image not found: 99999');

        $this->imageService->getThumbnailUrl(99999);
    }
}
