# Laravel Images - Documentation

## Table des matières

1. [Installation](#1-installation)
2. [Configuration](#2-configuration)
3. [Concepts de base](#3-concepts-de-base)
4. [Gestion des images](#4-gestion-des-images)
5. [Gestion des albums](#5-gestion-des-albums)
6. [Processeurs d'images](#6-processeurs-dimages)
7. [Stockage](#7-stockage)
8. [Exemples complets](#8-exemples-complets)
9. [API Référence](#9-api-référence)

---

## 1. Installation

```bash
composer require andydefer/laravel-images
```

### Prérequis

- PHP 8.1 ou supérieur
- Laravel 10.x, 11.x, 12.x, 13.x, 14.x ou 15.x
- Extension GD (par défaut) ou Imagick

### Publier les migrations

```bash
php artisan vendor:publish --tag=images-migrations
php artisan migrate
```

### Publier la configuration

```bash
php artisan vendor:publish --tag=images-config
```

---

## 2. Configuration

### Fichier de configuration

```php
// config/images.php
return [
    // Driver du processeur d'images
    'driver' => env('IMAGE_DRIVER', 'gd'), // ou 'imagick'
    
    // Disque de stockage
    'disk' => env('IMAGE_DISK', 'public'),
];
```

### Variables d'environnement

```env
IMAGE_DRIVER=gd
IMAGE_DISK=public
```

---

## 3. Concepts de base

### 3.1 Image

Une image est un modèle Eloquent qui représente un fichier image stocké sur le disque.

```php
use AndyDefer\LaravelImages\Models\Image;

// Propriétés principales
$image->id;           // ID unique
$image->path;         // Chemin relatif (ImagePathVO)
$image->filename;     // Nom du fichier
$image->original_filename; // Nom original
$image->extension;    // Extension (jpg, png, etc.)
$image->mime_type;    // Type MIME
$image->size;         // Taille en bytes
$image->type;         // Type d'image (avatar, cover, gallery, etc.)
$image->metadata;     // Métadonnées (ImageMetadataVO)
$image->is_primary;   // Image principale
$image->is_processed; // Traitée ou non
$image->order;        // Ordre d'affichage
```

### 3.2 Album

Un album regroupe plusieurs images avec un ordre défini.

```php
use AndyDefer\LaravelImages\Models\Album;

$album->name;          // Nom de l'album
$album->slug;          // Slug unique
$album->description;   // Description
$album->is_public;     // Public ou privé (BinaryChoice)
$album->is_featured;   // Mis en avant (BinaryChoice)
$album->images;        // Images de l'album
$album->coverImage;    // Image de couverture
```

### 3.3 Relations polymorphiques

Les images et albums peuvent être attachés à n'importe quel modèle Eloquent :

```php
use AndyDefer\LaravelImages\Traits\HasImages;
use AndyDefer\LaravelImages\Traits\HasAlbums;

class User extends Model
{
    use HasImages;  // Ajoute des méthodes pour gérer les images
    use HasAlbums;  // Ajoute des méthodes pour gérer les albums
}
```

---

## 4. Gestion des images

### 4.1 Upload d'une image

```php
use AndyDefer\LaravelImages\Services\ImageService;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Records\ImageOptionsRecord;

$imageService = app(ImageService::class);

// Upload simple
$image = $imageService->upload(
    $request->file('avatar'),
    $user,                    // Modèle parent
    auth()->user(),          // Uploadé par
    ImageType::AVATAR,
    new ImageOptionsRecord(
        alt_text: 'Photo de profil',
        is_primary: true,
        generate_thumbnails: true,
    )
);
```

### 4.2 Upload multiple

```php
$images = $imageService->uploadMultiple(
    $request->file('photos'),
    $post,
    auth()->user(),
    ImageType::GALLERY,
    new ImageOptionsRecord(
        generate_thumbnails: true,
    )
);

foreach ($images as $image) {
    echo $image->filename . "\n";
}
```

### 4.3 Récupération des images

```php
// Toutes les images d'un modèle
$images = $imageService->getImagesForModel($post);

// Images d'un type spécifique
$avatars = $imageService->getImagesForModel($user, ImageType::AVATAR);

// Image principale
$primary = $imageService->getPrimaryImage($user);

// Une image par ID
$image = $imageService->findImage(42);

// Images mises à jour récemment
$recent = $imageService->getImagesUpdatedAfter(
    DateTimeVO::from(now()->subDays(7))
);
```

### 4.4 Mise à jour

```php
use AndyDefer\LaravelImages\Records\ImageRecord;

// Mettre à jour les métadonnées
$image = $imageService->update(
    ImageRecord::from(['metadata' => new ImageMetadataVO([
        'alt_text' => 'Nouveau texte alternatif',
        'caption' => 'Nouvelle légende',
    ])]),
    $imageId
);

// Définir comme image principale
$imageService->setAsPrimary($imageId, $post);

// Réorganiser les images
$imageService->reorder([3, 1, 4, 2]);
```

### 4.5 Suppression

```php
// Supprimer une image
$imageService->delete($imageId, deleteFile: true);

// Supprimer plusieurs images
$imageService->deleteMultiple([1, 2, 3], deleteFile: true);

// Supprimer toutes les images d'un modèle
$imageService->deleteAllForModel($post, deleteFile: true);
```

### 4.6 Miniatures

```php
// Récupérer l'URL d'une miniature
$small = $imageService->getThumbnailUrl($imageId, 'small');
$medium = $imageService->getThumbnailUrl($imageId, 'medium');
$large = $imageService->getThumbnailUrl($imageId, 'large');

// Dans un template Blade
<img src="{{ $imageService->getThumbnailUrl($image->id, 'small') }}" />
```

---

## 5. Gestion des albums

### 5.1 Création d'un album

```php
use AndyDefer\LaravelImages\Services\AlbumService;
use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Records\AlbumOptionsRecord;

$albumService = app(AlbumService::class);

$album = $albumService->createAlbum(
    $user,
    'Mes photos de vacances',
    new AlbumOptionsRecord(
        description: 'Photos de mon voyage en Italie',
        is_public: BinaryChoice::YES,
        is_featured: BinaryChoice::NO,
    )
);
```

### 5.2 Gestion des images d'un album

```php
// Ajouter des images à un album
$albumService->addImagesToAlbum($album, [1, 2, 3, 4, 5]);

// Ajouter une image avec une position spécifique
$albumService->addImageToAlbum($album, $imageId, $order = 3);

// Réorganiser les images
$albumService->reorderAlbumImages($album, [3, 1, 4, 2, 5]);

// Retirer une image
$albumService->removeImageFromAlbum($album, $imageId);

// Vider l'album
$albumService->removeAllImagesFromAlbum($album);
```

### 5.3 Récupération des albums

```php
// Albums d'un modèle (publics uniquement)
$albums = $albumService->getAlbumsForModel($user, onlyPublic: true);

// Tous les albums (publics et privés)
$allAlbums = $albumService->getAlbumsForModel($user, onlyPublic: false);

// Album par slug
$album = $albumService->getAlbumBySlug('mes-photos-de-vacances');

// Albums mis en avant
$featured = $albumService->getFeaturedAlbums(10);
```

### 5.4 Gestion de la couverture

```php
// Définir la couverture
$albumService->setCoverImage($album, $imageId);

// Récupérer la couverture
$cover = $albumService->getAlbumCoverImage($album);
```

### 5.5 Mise à jour et suppression

```php
// Mettre à jour un album
$album = $albumService->updateAlbum(
    $albumId,
    new AlbumOptionsRecord(
        name: 'Nouveau nom',
        description: 'Nouvelle description',
        is_public: BinaryChoice::NO,
        is_featured: BinaryChoice::YES,
    )
);

// Dupliquer un album
$duplicate = $albumService->duplicateAlbum($album, 'Copie - Mes photos');

// Supprimer un album
$albumService->deleteAlbum($albumId, deleteImages: true);
```

---

## 6. Processeurs d'images

### 6.1 GD vs Imagick

| Feature | GD | Imagick |
|---------|----|---------|
| Disponibilité | ✅ Par défaut | ⚠️ Installation requise |
| Performance | Bonne | Excellente |
| Qualité | Bonne | Supérieure |
| Formats supportés | JPG, PNG, GIF, WebP | JPG, PNG, GIF, WebP, HEIC, AVIF, TIFF |
| Utilisation | `new GdImageProcessor()` | `new ImagickImageProcessor()` |

### 6.2 Utilisation du processeur

```php
use AndyDefer\LaravelImages\Processors\GdImageProcessor;

$processor = new GdImageProcessor($storage, $fileSystem);

// Redimensionner une image
$resized = $processor->resize(
    $imagePath,  // ImagePathVO
    800,         // Largeur
    600,         // Hauteur (null = ratio conservé)
    85           // Qualité (1-100)
);

echo $resized->getFullPath();
```

### 6.3 Redimensionnement avancé

```php
// Redimensionner avec ratio conservé
$resized = $processor->resize($imagePath, 800);

// Redimensionner avec dimensions exactes
$resized = $processor->resize($imagePath, 400, 300, 90);

// Générer plusieurs tailles
$sizes = [
    ['width' => 1920, 'height' => 1080, 'quality' => 90],
    ['width' => 800, 'height' => 600, 'quality' => 85],
    ['width' => 150, 'height' => 150, 'quality' => 75],
];

foreach ($sizes as $config) {
    $processor->resize($imagePath, $config['width'], $config['height'], $config['quality']);
}
```

---

## 7. Stockage

### 7.1 Configuration du stockage

```php
use AndyDefer\LaravelImages\Storage\LocalImageStorage;
use AndyDefer\PhpServices\Services\FileSystemService;

// Création du stockage
$fileSystem = new FileSystemService();
$storage = new LocalImageStorage($fileSystem, 'public');
```

### 7.2 Opérations de stockage

```php
// Stocker un fichier
$path = $storage->store($uploadedFile, 'users/123/avatars', 'profile.jpg');

// Vérifier l'existence
if ($storage->exists($path)) {
    // Le fichier existe
}

// Obtenir le chemin complet
$fullPath = $storage->getFullPath($path);

// Lister les fichiers d'un dossier
$files = $storage->files('users/123/avatars');

// Supprimer un fichier
$storage->delete($path);

// Supprimer plusieurs fichiers
$storage->deleteMultiple($files);

// Changer le chemin de base
$storage->setBasePath('private');
```

---

## 8. Exemples complets

### 8.1 Upload d'avatar avec options

```php
<?php

namespace App\Http\Controllers;

use AndyDefer\LaravelImages\Services\ImageService;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Records\ImageOptionsRecord;
use Illuminate\Http\Request;

class AvatarController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function upload(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $image = $this->imageService->upload(
            $request->file('avatar'),
            $request->user(),
            $request->user(),
            ImageType::AVATAR,
            new ImageOptionsRecord(
                alt_text: 'Avatar de ' . $request->user()->name,
                is_primary: true,
                generate_thumbnails: true,
                order: 1,
            )
        );

        return response()->json([
            'message' => 'Avatar uploadé avec succès',
            'image' => [
                'id' => $image->id,
                'url' => $image->full_url,
                'thumbnail' => $this->imageService->getThumbnailUrl($image->id, 'small'),
            ],
        ]);
    }
}
```

### 8.2 Galerie d'images

```php
<?php

namespace App\Http\Controllers;

use AndyDefer\LaravelImages\Services\ImageService;
use AndyDefer\LaravelImages\Services\AlbumService;
use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Records\AlbumOptionsRecord;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
        private readonly AlbumService $albumService,
    ) {}

    public function createAlbum(Request $request)
    {
        $album = $this->albumService->createAlbum(
            $request->user(),
            $request->input('name'),
            new AlbumOptionsRecord(
                description: $request->input('description'),
                is_public: BinaryChoice::YES,
            )
        );

        // Upload des images
        $images = $this->imageService->uploadMultiple(
            $request->file('photos'),
            $request->user(),
            $request->user(),
            ImageType::GALLERY,
            new ImageOptionsRecord(generate_thumbnails: true)
        );

        // Ajout à l'album
        $imageIds = $images->pluck('id')->toArray();
        $this->albumService->addImagesToAlbum($album, $imageIds);

        // Définir la couverture
        if (!empty($imageIds)) {
            $this->albumService->setCoverImage($album, $imageIds[0]);
        }

        return response()->json([
            'message' => 'Album créé avec succès',
            'album' => $album,
            'images' => $images,
        ]);
    }
}
```

### 8.3 Export d'images avec filtrage

```php
<?php

namespace App\Services;

use AndyDefer\LaravelImages\Services\ImageService;
use Illuminate\Support\Collection;

class ImageExportService
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function export(Model $model, array $types = null): array
    {
        $images = $this->imageService->getImagesForModel($model);

        if ($types) {
            $images = $images->filter(fn($img) => in_array($img->type, $types));
        }

        return $images->map(function ($image) {
            return [
                'id' => $image->id,
                'filename' => $image->filename,
                'original_filename' => $image->original_filename,
                'url' => $image->full_url,
                'thumbnails' => [
                    'small' => $this->imageService->getThumbnailUrl($image->id, 'small'),
                    'medium' => $this->imageService->getThumbnailUrl($image->id, 'medium'),
                    'large' => $this->imageService->getThumbnailUrl($image->id, 'large'),
                ],
                'metadata' => $image->metadata?->toArray(),
                'type' => $image->type->value,
                'order' => $image->order,
                'is_primary' => $image->is_primary,
            ];
        })->toArray();
    }
}
```

---

## 9. API Référence

### ImageService

| Méthode | Description |
|---------|-------------|
| `findImage(int $id): ?Image` | Trouve une image par ID |
| `upload(...): Image` | Upload une image |
| `uploadMultiple(...): Collection` | Upload plusieurs images |
| `update(ImageRecord $record, int $id): Image` | Met à jour une image |
| `delete(int $id, bool $deleteFile): void` | Supprime une image |
| `deleteMultiple(array $ids, bool $deleteFile): void` | Supprime plusieurs images |
| `deleteAllForModel(Model $model, bool $deleteFile): void` | Supprime toutes les images d'un modèle |
| `getImagesForModel(Model $model, ?ImageType $type): Collection` | Récupère les images d'un modèle |
| `getPrimaryImage(Model $model): ?Image` | Récupère l'image principale |
| `setAsPrimary(int $id, Model $model): void` | Définit une image comme principale |
| `countImages(Model $model, ?ImageType $type): int` | Compte les images |
| `getImagesUpdatedAfter(DateTimeVO $date): Collection` | Images mises à jour après une date |
| `reorder(array $ids): void` | Réorganise les images |
| `getThumbnailUrl(int $imageId, string $size): string` | URL de la miniature |
| `getImageProcessor(): ImageProcessorInterface` | Retourne le processeur |
| `getStorage(): ImageStorageInterface` | Retourne le stockage |

### AlbumService

| Méthode | Description |
|---------|-------------|
| `createAlbum(...): Album` | Crée un album |
| `addImagesToAlbum(Album $album, array $imageIds): void` | Ajoute des images |
| `addImageToAlbum(Album $album, int $imageId, int $order): void` | Ajoute une image |
| `removeImageFromAlbum(Album $album, int $imageId): void` | Retire une image |
| `removeAllImagesFromAlbum(Album $album): void` | Vide un album |
| `setCoverImage(Album $album, int $imageId): void` | Définit la couverture |
| `getAlbumImages(Album $album): Collection` | Images d'un album |
| `getAlbumsForModel(Model $model, bool $onlyPublic): Collection` | Albums d'un modèle |
| `getAlbumBySlug(string|SlugVO $slug): ?Album` | Album par slug |
| `updateAlbum(int $id, AlbumOptionsRecord $options): Album` | Met à jour un album |
| `deleteAlbum(int $id, bool $deleteImages): void` | Supprime un album |
| `reorderAlbumImages(Album $album, array $imageIds): void` | Réorganise les images |
| `duplicateAlbum(Album $album, string $newName): Album` | Duplique un album |
| `countAlbumImages(Album $album): int` | Compte les images |
| `isAlbumEmpty(Album $album): bool` | Vérifie si l'album est vide |
| `getAlbumCoverImage(Album $album): ?Image` | Image de couverture |
| `getFeaturedAlbums(int $limit): Collection` | Albums mis en avant |

### ImageType

| Type | Description |
|------|-------------|
| `AVATAR` | Photo de profil |
| `COVER` | Photo de couverture |
| `GALLERY` | Galerie d'images |
| `THUMBNAIL` | Miniature |
| `ATTACHMENT` | Pièce jointe |
| `LOGO` | Logo |
| `ICON` | Icône |
| `BANNER` | Bannière |
| `PRODUCT` | Image de produit |

### BinaryChoice

| Valeur | Description |
|--------|-------------|
| `YES` | Oui |
| `NO` | Non |

---

## Licence

MIT © [Andy Defer](https://github.com/andydefer)