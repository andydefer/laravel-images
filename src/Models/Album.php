<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Models;

use AndyDefer\LaravelCluster\Casts\ClusterCast;
use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelCluster\ValueObjects\ClusterVO;
use AndyDefer\LaravelUtils\Proxies\AttributeProxy;
use AndyDefer\PhpVo\ValueObjects\SlugVO;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Album model for grouping images.
 *
 * @property int $id
 * @property string $name
 * @property SlugVO $slug
 * @property string|null $description
 * @property int|null $cover_image_id
 * @property BinaryChoice $is_public
 * @property BinaryChoice $is_featured
 * @property ClusterVO|null $metadata
 * @property string|null $albumable_type
 * @property int|null $albumable_id
 * @property-read int $image_count
 */
final class Album extends Model
{
    use SoftDeletes;

    protected $table = 'albums';

    protected $fillable = [
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

    // ============================================================
    // RELATIONS
    // ============================================================

    public function albumable()
    {
        return $this->morphTo();
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(
            Image::class,
            'album_image',
            'album_id',
            'image_id'
        )->withPivot('order', 'created_at')->orderBy('order');
    }

    public function coverImage()
    {
        return $this->belongsTo(Image::class, 'cover_image_id');
    }

    // ============================================================
    // ATTRIBUTE ACCESSORS - Transformable avec AttributeProxy
    // ============================================================

    protected function slug(): Attribute
    {
        return AttributeProxy::nullable(SlugVO::class);
    }

    // ============================================================
    // ATTRIBUTE ACCESSORS - NON TRANSFORMABLE
    // ============================================================

    protected function imageCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->images()->count(),
        );
    }
}
