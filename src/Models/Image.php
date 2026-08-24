<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Models;

use AndyDefer\LaravelImages\Database\Factories\ImageFactory;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\ValueObjects\ImageMetadataVO;
use AndyDefer\LaravelImages\ValueObjects\ImagePathVO;
use AndyDefer\Repository\Proxies\AttributeProxy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Image model representing uploaded images with polymorphic relations.
 *
 * @property string $id
 * @property ImagePathVO $path
 * @property string $filename
 * @property string $original_filename
 * @property string $extension
 * @property string $mime_type
 * @property int $size
 * @property ImageType $type
 * @property ImageMetadataVO $metadata
 * @property string $imageable_type
 * @property string $imageable_id
 * @property int|null $width
 * @property int|null $height
 * @property int $order
 * @property bool $is_primary
 * @property bool $is_processed
 * @property string|null $inverse_image_id
 * @property string|null $uploaded_by_type
 * @property string|null $uploaded_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * // Computed attributes
 * @property-read string $full_url
 * @property-read string $file_size_for_humans
 * @property-read string $dimensions
 * @property-read bool $has_inverse
 *
 * // Relations
 * @property-read Model|null $imageable
 * @property-read Image|null $inverseImage
 * @property-read HasMany<Image> $inverseImages
 */
final class Image extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'images';

    protected $fillable = [
        'id',
        'path',
        'filename',
        'original_filename',
        'extension',
        'mime_type',
        'size',
        'type',
        'width',
        'height',
        'metadata',
        'order',
        'is_primary',
        'is_processed',
        'inverse_image_id',
        'uploaded_by_type',
        'uploaded_by_id',
        'imageable_type',
        'imageable_id',
    ];

    protected $casts = [
        'type' => ImageType::class,
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'order' => 'integer',
        'is_primary' => 'boolean',
        'is_processed' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function newFactory(): ImageFactory
    {
        return ImageFactory::new();
    }

    // ============================================================
    // RELATIONS
    // ============================================================

    /**
     * Get the parent imageable model (polymorphic relation).
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the inverse image (dark/light variant).
     *
     * @return BelongsTo<Image, $this>
     */
    public function inverseImage(): BelongsTo
    {
        return $this->belongsTo(self::class, 'inverse_image_id');
    }

    /**
     * Get the images that have this image as inverse.
     *
     * @return HasMany<Image>
     */
    public function inverseImages(): HasMany
    {
        return $this->hasMany(self::class, 'inverse_image_id');
    }

    // ============================================================
    // ATTRIBUTE ACCESSORS - Transformable avec AttributeProxy
    // ============================================================

    /**
     * Get the path attribute as an ImagePathVO.
     *
     * @return Attribute<ImagePathVO, never>
     */
    protected function path(): Attribute
    {
        return AttributeProxy::make(
            class: ImagePathVO::class,
            column: 'path'
        );
    }

    /**
     * Get the path of the inverse image (dark/light variant).
     * If no inverse image exists, returns null.
     *
     * @return Attribute<ImagePathVO|null, never>
     */
    protected function inversedImagePath(): Attribute
    {
        return Attribute::make(
            get: fn (): ?ImagePathVO => $this->inverseImage?->path
        );
    }

    /**
     * Get the metadata attribute as an ImageMetadataVO.
     *
     * @return Attribute<ImageMetadataVO, never>
     */
    protected function metadata(): Attribute
    {
        return AttributeProxy::nullable(ImageMetadataVO::class, 'metadata');
    }

    // ============================================================
    // ATTRIBUTE ACCESSORS - NON TRANSFORMABLE
    // ============================================================

    /**
     * Get the full URL of the image.
     *
     * @return Attribute<string, never>
     */
    protected function fullUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => asset('storage/'.$this->path),
        );
    }

    /**
     * Get the human-readable file size.
     *
     * @return Attribute<string, never>
     */
    protected function fileSizeForHumans(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $bytes = $this->size;
                $units = ['B', 'KB', 'MB', 'GB', 'TB'];

                $i = 0;
                while ($bytes >= 1024 && $i < count($units) - 1) {
                    $bytes /= 1024;
                    $i++;
                }

                return round($bytes, 2).' '.$units[$i];
            }
        );
    }

    /**
     * Get the image dimensions (width x height).
     *
     * @return Attribute<string, never>
     */
    protected function dimensions(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->width.'x'.$this->height,
        );
    }

    /**
     * Check if the image has an inverse variant (dark/light mode).
     *
     * @return Attribute<bool, never>
     */
    protected function hasInverse(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->inverse_image_id !== null
        );
    }
}
