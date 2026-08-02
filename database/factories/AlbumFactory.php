<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Database\Factories;

use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Models\Album;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Factory for creating Album model instances.
 *
 * Provides default state generation and reusable state methods for
 * common album configurations (public/private, featured, with relations).
 *
 * @extends Factory<Album>
 *
 * @author Andy Defer
 * @license MIT
 */
final class AlbumFactory extends Factory
{
    /**
     * The model class associated with this factory.
     *
     * @var class-string<Album>
     */
    protected $model = Album::class;

    /**
     * Define the default model attributes.
     *
     * Creates an album with realistic fake data including a unique slug
     * and default metadata structure.
     *
     * @return array<string, mixed> The default attributes
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);
        $slug = $this->generateUniqueSlug($name);

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $this->faker->paragraph(),
            'cover_image_id' => null,
            'is_public' => BinaryChoice::YES,
            'is_featured' => BinaryChoice::NO,
            'metadata' => $this->generateDefaultMetadata(),
            'albumable_type' => null,
            'albumable_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Set the album as public.
     */
    public function public(): self
    {
        return $this->state(['is_public' => BinaryChoice::YES]);
    }

    /**
     * Set the album as private.
     */
    public function private(): self
    {
        return $this->state(['is_public' => BinaryChoice::NO]);
    }

    /**
     * Mark the album as featured.
     */
    public function featured(): self
    {
        return $this->state(['is_featured' => BinaryChoice::YES]);
    }

    /**
     * Mark the album as not featured.
     */
    public function notFeatured(): self
    {
        return $this->state(['is_featured' => BinaryChoice::NO]);
    }

    /**
     * Associate a cover image with the album.
     *
     * @param  int  $imageId  The ID of the image to use as cover
     */
    public function withCoverImage(int $imageId): self
    {
        return $this->state(['cover_image_id' => $imageId]);
    }

    /**
     * Associate the album with an owner model.
     *
     * Sets up the polymorphic relationship with the given model.
     *
     * @param  Model  $model  The owning model instance
     */
    public function withAlbumable(Model $model): self
    {
        return $this->state([
            'albumable_type' => $model->getMorphClass(),
            'albumable_id' => $model->getKey(),
        ]);
    }

    /**
     * Set custom metadata for the album.
     *
     * @param  array<string, mixed>  $metadata  The metadata array to store
     */
    public function withMetadata(array $metadata): self
    {
        return $this->state(['metadata' => $metadata]);
    }

    /**
     * Set the album name.
     *
     * Automatically generates a unique slug based on the provided name.
     *
     * @param  string  $name  The album name
     */
    public function withName(string $name): self
    {
        $slug = $this->generateUniqueSlug($name);

        return $this->state([
            'name' => $name,
            'slug' => $slug,
        ]);
    }

    /**
     * Set a specific slug for the album.
     *
     * @param  string  $slug  The slug to use
     */
    public function withSlug(string $slug): self
    {
        return $this->state(['slug' => $slug]);
    }

    /**
     * Set the album description.
     *
     * @param  string|null  $description  The description text or null to clear
     */
    public function withDescription(?string $description): self
    {
        return $this->state(['description' => $description]);
    }

    /**
     * Generate a unique slug from a given name.
     *
     * Appends a random string to ensure uniqueness.
     *
     * @param  string  $name  The base name for slug generation
     * @return string The generated unique slug
     */
    private function generateUniqueSlug(string $name): string
    {
        return Str::slug($name).'-'.Str::random(6);
    }

    /**
     * Generate default metadata structure.
     *
     * Creates a consistent metadata array with category, tags, and source info.
     *
     * @return array<string, mixed> The default metadata array
     */
    private function generateDefaultMetadata(): array
    {
        return [
            'category' => $this->faker->word(),
            'tags' => $this->faker->words(3),
            'created_by' => 'factory',
        ];
    }
}
