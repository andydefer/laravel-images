<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Database\Factories;

use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelUtils\Enums\ImageExtension;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * Factory for creating Image model instances.
 *
 * Provides default state generation and comprehensive state methods for
 * configuring images with different types, dimensions, and relationships.
 *
 * @extends Factory<Image>
 *
 * @author Andy Defer
 * @license MIT
 */
final class ImageFactory extends Factory
{
    /**
     * The model class associated with this factory.
     *
     * @var class-string<Image>
     */
    protected $model = Image::class;

    /**
     * Define the default model attributes.
     *
     * Creates an image with realistic fake data including file information,
     * dimensions, and default metadata structure.
     *
     * @return array<string, mixed> The default attributes
     */
    public function definition(): array
    {
        $extension = $this->faker->randomElement(ImageExtension::values());
        $filename = $this->faker->uuid().'.'.$extension;

        return [
            'path' => $this->generatePath(ImageType::GALLERY, $filename),
            'filename' => $filename,
            'original_filename' => $this->faker->word().'.'.$extension,
            'extension' => $extension,
            'mime_type' => $this->getMimeType($extension),
            'size' => $this->faker->numberBetween(1024, 10 * 1024 * 1024),
            'width' => $this->faker->numberBetween(100, 1920),
            'height' => $this->faker->numberBetween(100, 1080),
            'type' => ImageType::GALLERY,
            'metadata' => $this->generateDefaultMetadata(),
            'order' => 0,
            'is_primary' => false,
            'is_processed' => true,
            'imageable_type' => 'App\Models\DefaultModel',
            'imageable_id' => 1,
            'uploaded_by_type' => null,
            'uploaded_by_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Set the image as an avatar type.
     */
    public function avatar(): self
    {
        return $this->state(function (array $attributes): array {
            $type = ImageType::AVATAR;
            $filename = $attributes['filename'] ?? $this->faker->uuid().'.png';

            return [
                'type' => $type,
                'path' => $this->generatePath($type, $filename),
            ];
        });
    }

    /**
     * Set the image as a cover type.
     */
    public function cover(): self
    {
        return $this->state(function (array $attributes): array {
            $type = ImageType::COVER;
            $filename = $attributes['filename'] ?? $this->faker->uuid().'.jpg';

            return [
                'type' => $type,
                'path' => $this->generatePath($type, $filename),
            ];
        });
    }

    /**
     * Set the image as a gallery type.
     */
    public function gallery(): self
    {
        return $this->state(function (array $attributes): array {
            $type = ImageType::GALLERY;
            $filename = $attributes['filename'] ?? $this->faker->uuid().'.jpg';

            return [
                'type' => $type,
                'path' => $this->generatePath($type, $filename),
            ];
        });
    }

    /**
     * Set the image as a banner type.
     */
    public function banner(): self
    {
        return $this->state(function (array $attributes): array {
            $type = ImageType::BANNER;
            $filename = $attributes['filename'] ?? $this->faker->uuid().'.png';

            return [
                'type' => $type,
                'path' => $this->generatePath($type, $filename),
            ];
        });
    }

    /**
     * Set the image as a logo type.
     */
    public function logo(): self
    {
        return $this->state(function (array $attributes): array {
            $type = ImageType::LOGO;
            $filename = $attributes['filename'] ?? $this->faker->uuid().'.png';

            return [
                'type' => $type,
                'path' => $this->generatePath($type, $filename),
            ];
        });
    }

    /**
     * Set the image as an icon type.
     */
    public function icon(): self
    {
        return $this->state(function (array $attributes): array {
            $type = ImageType::ICON;
            $filename = $attributes['filename'] ?? $this->faker->uuid().'.png';

            return [
                'type' => $type,
                'path' => $this->generatePath($type, $filename),
            ];
        });
    }

    /**
     * Set the image as a product type.
     */
    public function product(): self
    {
        return $this->state(function (array $attributes): array {
            $type = ImageType::PRODUCT;
            $filename = $attributes['filename'] ?? $this->faker->uuid().'.jpg';

            return [
                'type' => $type,
                'path' => $this->generatePath($type, $filename),
            ];
        });
    }

    /**
     * Set the image as an attachment type.
     */
    public function attachment(): self
    {
        return $this->state(function (array $attributes): array {
            $type = ImageType::ATTACHMENT;
            $filename = $attributes['filename'] ?? $this->faker->uuid().'.pdf';

            return [
                'type' => $type,
                'path' => $this->generatePath($type, $filename),
            ];
        });
    }

    /**
     * Set the image as a thumbnail type.
     */
    public function thumbnail(): self
    {
        return $this->state(function (array $attributes): array {
            $type = ImageType::THUMBNAIL;
            $filename = $attributes['filename'] ?? $this->faker->uuid().'.jpg';

            return [
                'type' => $type,
                'path' => $this->generatePath($type, $filename),
            ];
        });
    }

    /**
     * Mark the image as the primary image.
     */
    public function primary(): self
    {
        return $this->state(['is_primary' => true]);
    }

    /**
     * Mark the image as processed.
     */
    public function processed(): self
    {
        return $this->state(['is_processed' => true]);
    }

    /**
     * Mark the image as unprocessed.
     */
    public function unprocessed(): self
    {
        return $this->state(['is_processed' => false]);
    }

    /**
     * Associate the image with an uploader model.
     *
     * @param  Model  $model  The uploader model instance
     */
    public function withUploadedBy(Model $model): self
    {
        return $this->state([
            'uploaded_by_type' => $model->getMorphClass(),
            'uploaded_by_id' => $model->getKey(),
        ]);
    }

    /**
     * Set custom metadata for the image.
     *
     * @param  array<string, mixed>  $metadata  The metadata array to store
     */
    public function withMetadata(array $metadata): self
    {
        return $this->state(['metadata' => $metadata]);
    }

    /**
     * Set the alt text in the image metadata.
     *
     * @param  string  $altText  The alternative text for accessibility
     */
    public function withAltText(string $altText): self
    {
        return $this->state(function (array $attributes) use ($altText): array {
            $metadata = $attributes['metadata'] ?? [];
            $metadata['alt_text'] = $altText;

            return ['metadata' => $metadata];
        });
    }

    /**
     * Set the caption in the image metadata.
     *
     * @param  string  $caption  The image caption
     */
    public function withCaption(string $caption): self
    {
        return $this->state(function (array $attributes) use ($caption): array {
            $metadata = $attributes['metadata'] ?? [];
            $metadata['caption'] = $caption;

            return ['metadata' => $metadata];
        });
    }

    /**
     * Set the display order of the image.
     *
     * @param  int  $order  The order value
     */
    public function withOrder(int $order): self
    {
        return $this->state(['order' => $order]);
    }

    /**
     * Set the image dimensions.
     *
     * @param  int  $width  The image width in pixels
     * @param  int  $height  The image height in pixels
     */
    public function withDimensions(int $width, int $height): self
    {
        return $this->state([
            'width' => $width,
            'height' => $height,
        ]);
    }

    /**
     * Set the image file size.
     *
     * @param  int  $size  The file size in bytes
     */
    public function withSize(int $size): self
    {
        return $this->state(['size' => $size]);
    }

    /**
     * Set the image file extension.
     *
     * Automatically updates the MIME type based on the extension.
     *
     * @param  string  $extension  The file extension (e.g., 'jpg', 'png')
     */
    public function withExtension(string $extension): self
    {
        return $this->state([
            'extension' => $extension,
            'mime_type' => $this->getMimeType($extension),
        ]);
    }

    /**
     * Set a specific file path for the image.
     *
     * @param  string  $path  The file path
     */
    public function withPath(string $path): self
    {
        return $this->state(['path' => $path]);
    }

    /**
     * Set a specific filename for the image.
     *
     * @param  string  $filename  The filename
     */
    public function withFilename(string $filename): self
    {
        return $this->state(['filename' => $filename]);
    }

    /**
     * Set the original filename of the uploaded image.
     *
     * @param  string  $originalFilename  The original uploaded filename
     */
    public function withOriginalFilename(string $originalFilename): self
    {
        return $this->state(['original_filename' => $originalFilename]);
    }

    /**
     * Create a compressed version of the image.
     *
     * Simulates a compressed image with smaller file size and different storage path.
     */
    public function compressed(): self
    {
        return $this->state([
            'path' => 'storage/app/compressed/'.$this->faker->uuid().'.jpg',
            'size' => $this->faker->numberBetween(1024, 300 * 1024),
            'is_processed' => true,
        ]);
    }

    /**
     * Associate the image with an owner model.
     *
     * @param  Model  $model  The owning model instance
     */
    public function withImageable(Model $model): self
    {
        return $this->state([
            'imageable_type' => $model->getMorphClass(),
            'imageable_id' => $model->getKey(),
        ]);
    }

    /**
     * Generate a storage path for the image.
     *
     * Creates a structured path including image type and date-based directories.
     *
     * @param  ImageType  $type  The image type enum
     * @param  string  $filename  The filename
     * @return string The generated storage path
     */
    private function generatePath(ImageType $type, string $filename): string
    {
        $typePath = $type->value;
        $year = now()->format('Y');
        $month = now()->format('m');
        $day = now()->format('d');

        return "images/{$typePath}/{$year}/{$month}/{$day}/{$filename}";
    }

    /**
     * Get the MIME type for a given file extension.
     *
     * @param  string  $extension  The file extension
     * @return string The corresponding MIME type
     */
    private function getMimeType(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'tiff' => 'image/tiff',
            'ico' => 'image/x-icon',
            'pdf' => 'application/pdf',
            'doc', 'docx' => 'application/msword',
            'xls', 'xlsx' => 'application/vnd.ms-excel',
            default => 'application/octet-stream',
        };
    }

    /**
     * Generate default metadata structure.
     *
     * Creates a consistent metadata array with alt text, caption, source, and tags.
     *
     * @return array<string, mixed> The default metadata array
     */
    private function generateDefaultMetadata(): array
    {
        return [
            'alt_text' => $this->faker->sentence(3),
            'caption' => $this->faker->sentence(5),
            'source' => $this->faker->randomElement(['camera', 'mobile', 'web', 'upload']),
            'tags' => $this->faker->words(3),
        ];
    }
}
