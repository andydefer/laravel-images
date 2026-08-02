<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Models;

use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\ValueObjects\ImageMetadataVO;
use AndyDefer\LaravelImages\ValueObjects\ImagePathVO;
use AndyDefer\LaravelUtils\Proxies\AttributeProxy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Image model representing uploaded images with polymorphic relations.
 *
 * @property int $id
 * @property ImagePathVO $path
 * @property string $filename
 * @property string $original_filename
 * @property string $extension
 * @property string $mime_type
 * @property int $size
 * @property ImageType $type
 * @property ImageMetadataVO $metadata
 * @property string $imageable_type
 * @property int $imageable_id
 * @property int|null $width
 * @property int|null $height
 * @property int $order
 * @property bool $is_primary
 * @property bool $is_processed
 * @property string|null $uploaded_by_type
 * @property int|null $uploaded_by_id
 * @property-read string $full_url
 * @property-read string $file_size_for_humans
 * @property-read string $dimensions
 */
final class Image extends Model
{
    use SoftDeletes;

    protected $table = 'images';

    protected $fillable = [
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

    // ============================================================
    // RELATIONS
    // ============================================================

    public function imageable()
    {
        return $this->morphTo();
    }

    // ============================================================
    // ATTRIBUTE ACCESSORS - Transformable avec AttributeProxy
    // ============================================================

    protected function path(): Attribute
    {
        return AttributeProxy::make(
            class: ImagePathVO::class,
            column: 'path'
        );
    }

    protected function metadata(): Attribute
    {
        return AttributeProxy::nullable(ImageMetadataVO::class, 'metadata');
    }

    // ============================================================
    // ATTRIBUTE ACCESSORS - NON TRANSFORMABLE
    // ============================================================

    protected function fullUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => asset('storage/'.$this->path),
        );
    }

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

    protected function dimensions(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->width.'x'.$this->height,
        );
    }
}
