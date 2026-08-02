<?php

declare(strict_types=1);

namespace AndyDefer\LaravelImages\Enums;

enum ImageType: string
{
    case AVATAR = 'avatar';
    case COVER = 'cover';
    case GALLERY = 'gallery';
    case THUMBNAIL = 'thumbnail';
    case ATTACHMENT = 'attachment';
    case LOGO = 'logo';
    case ICON = 'icon';
    case BANNER = 'banner';
    case PRODUCT = 'product';

    public function getLabel(): string
    {
        return match ($this) {
            self::AVATAR => 'Avatar',
            self::COVER => 'Photo de couverture',
            self::GALLERY => 'Galerie',
            self::THUMBNAIL => 'Miniature',
            self::ATTACHMENT => 'Pièce jointe',
            self::LOGO => 'Logo',
            self::ICON => 'Icône',
            self::BANNER => 'Bannière',
            self::PRODUCT => 'Produit',
        };
    }

    public function getMaxSize(): int
    {
        return match ($this) {
            self::AVATAR => 2048,
            self::COVER => 5120,
            self::GALLERY => 10240,
            self::THUMBNAIL => 1024,
            self::ATTACHMENT => 20480,
            self::LOGO => 2048,
            self::ICON => 1024,
            self::BANNER => 5120,
            self::PRODUCT => 5120,
        };
    }

    public function getAllowedMimeTypes(): array
    {
        return match ($this) {
            self::AVATAR, self::THUMBNAIL, self::ICON => [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/svg+xml',
            ],
            default => [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
                'image/svg+xml',
                'image/bmp',
                'image/tiff',
            ],
        };
    }

    public function getDimensions(): ?array
    {
        return match ($this) {
            self::AVATAR => ['width' => 300, 'height' => 300],
            self::COVER => ['width' => 1200, 'height' => 400],
            self::THUMBNAIL => ['width' => 150, 'height' => 150],
            self::LOGO => ['width' => 200, 'height' => 200],
            self::ICON => ['width' => 64, 'height' => 64],
            self::BANNER => ['width' => 1920, 'height' => 600],
            default => null,
        };
    }

    public function isSquare(): bool
    {
        $dimensions = $this->getDimensions();
        if ($dimensions === null) {
            return false;
        }

        return $dimensions['width'] === $dimensions['height'];
    }

    public function getThumbnailSizes(): array
    {
        return match ($this) {
            self::COVER => [
                'small' => ['width' => 400, 'height' => 150],
                'medium' => ['width' => 800, 'height' => 300],
                'large' => ['width' => 1200, 'height' => 400],
            ],
            self::PRODUCT => [
                'small' => ['width' => 200, 'height' => 200],
                'medium' => ['width' => 400, 'height' => 400],
                'large' => ['width' => 800, 'height' => 800],
            ],
            self::AVATAR => [
                'small' => ['width' => 100, 'height' => 100],
                'medium' => ['width' => 200, 'height' => 200],
                'large' => ['width' => 300, 'height' => 300],
            ],
            self::LOGO => [
                'small' => ['width' => 50, 'height' => 50],
                'medium' => ['width' => 100, 'height' => 100],
                'large' => ['width' => 200, 'height' => 200],
            ],
            self::ICON => [
                'small' => ['width' => 16, 'height' => 16],
                'medium' => ['width' => 32, 'height' => 32],
                'large' => ['width' => 64, 'height' => 64],
            ],
            self::BANNER => [
                'small' => ['width' => 640, 'height' => 200],
                'medium' => ['width' => 1280, 'height' => 400],
                'large' => ['width' => 1920, 'height' => 600],
            ],
            self::GALLERY, self::ATTACHMENT, self::THUMBNAIL => [
                'small' => ['width' => 150, 'height' => 150],
                'medium' => ['width' => 300, 'height' => 300],
                'large' => ['width' => 600, 'height' => 600],
            ],
        };
    }

    public function getThumbnailSize(string $size): ?array
    {
        return $this->getThumbnailSizes()[$size] ?? null;
    }
}
