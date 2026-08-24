<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Models;

use AndyDefer\LaravelCluster\Casts\ClusterCast;
use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelCluster\ValueObjects\ClusterVO;
use AndyDefer\LaravelImages\Database\Factories\AlbumFactory;
use AndyDefer\PhpVo\ValueObjects\SlugVO;
use AndyDefer\Repository\Proxies\AttributeProxy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Album model for grouping images.
 *
 * @property string $id
 * @property string $name
 * @property SlugVO $slug
 * @property string|null $description
 * @property string|null $cover_image_id
 * @property BinaryChoice $is_public
 * @property BinaryChoice $is_featured
 * @property ClusterVO|null $metadata
 * @property string|null $albumable_type
 * @property string|null $albumable_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * // Computed attributes
 * @property-read int $image_count
 *
 * // Relations
 * @property-read Model|null $albumable
 * @property-read Collection<int, Image> $images
 * @property-read Image|null $coverImage
 */
final class Album extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'albums';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'cover_image_id',
        'is_public',
        'is_featured',
        'metadata',
        'albumable_type',
        'albumable_id',
    ];

    protected $casts = [
        'metadata' => ClusterCast::class,
        'is_public' => BinaryChoice::class,
        'is_featured' => BinaryChoice::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function newFactory(): AlbumFactory
    {
        return AlbumFactory::new();
    }

    // ============================================================
    // RELATIONS
    // ============================================================

    /**
     * Get the parent albumable model (polymorphic relation).
     *
     * @return MorphTo
     */
    public function albumable()
    {
        return $this->morphTo();
    }

    /**
     * Get the images belonging to the album.
     *
     * @return BelongsToMany<Image>
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(
            Image::class,
            'album_image',
            'album_id',
            'image_id'
        )->withPivot('order', 'created_at')->orderBy('order');
    }

    /**
     * Get the cover image of the album.
     *
     * @return BelongsTo<Image, $this>
     */
    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'cover_image_id');
    }

    // ============================================================
    // ATTRIBUTE ACCESSORS - Transformable avec AttributeProxy
    // ============================================================

    /**
     * Get the slug attribute as a SlugVO.
     *
     * @return Attribute<SlugVO, never>
     */
    protected function slug(): Attribute
    {
        return AttributeProxy::nullable(SlugVO::class);
    }

    // ============================================================
    // ATTRIBUTE ACCESSORS - NON TRANSFORMABLE
    // ============================================================

    /**
     * Get the number of images in the album.
     *
     * @return Attribute<int, never>
     */
    protected function imageCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->images()->count(),
        );
    }
}
