# Laravel Images - Documentation

## Table des matières

1. [Installation](#1-installation)
2. [Configuration](#2-configuration)
3. [Concepts de base](#3-concepts-de-base)
4. [Gestion des images](#4-gestion-des-images)
5. [Gestion des albums](#5-gestion-des-albums)
6. [Processeurs d'images](#6-processeurs-dimages)
7. [Stockage](#7-stockage)
8. [Directives CLI](#8-directives-cli)
9. [Exemples complets](#9-exemples-complets)
10. [API Référence](#10-api-référence)

---

## 1. Installation

```bash
composer require andydefer/laravel-images
```

### Prérequis

- PHP 8.1 ou supérieur
- Laravel 10.x, 11.x, 12.x, 13.x, 14.x ou 15.x
- Extension GD (par défaut) ou Imagick
- `pngquant` et `jpegoptim` pour la compression CLI (optionnel)

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
$image->id;                // ID unique
$image->path;              // Chemin relatif (ImagePathVO)
$image->filename;          // Nom du fichier
$image->original_filename; // Nom original
$image->extension;         // Extension (jpg, png, etc.)
$image->mime_type;         // Type MIME
$image->size;              // Taille en bytes
$image->type;              // Type d'image (avatar, cover, gallery, etc.)
$image->metadata;          // Métadonnées (ImageMetadataVO)
$image->is_primary;        // Image principale
$image->is_processed;      // Traitée ou non
$image->order;             // Ordre d'affichage
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

Les images et albums utilisent des relations polymorphiques (`morphTo`, `morphToMany`) pour s'attacher à n'importe quel modèle Eloquent.

```php
use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Models\Album;

class User extends Model
{
    // Une image s'attache à n'importe quel modèle via imageable()
    // Un album s'attache à n'importe quel modèle via albumable()
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
    $user,                    // Modèle parent (polymorphique)
    auth()->user(),           // Uploadé par
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

## 8. Directives CLI

### 8.1 Installation de la CLI

Le package fournit deux directives CLI pour la gestion des images :

| Directive | Alias | Description |
|-----------|-------|-------------|
| `images:compress` | `imc` | Compresse les images avec pngquant et jpegoptim |
| `images:scan` | `ims` | Scanne un dossier et génère un inventaire JSON ou PHP |

### 8.2 Commande de compression

```bash
./bin/images images:compress {source} {destination?} {--options}
```

**Paramètres :**

| Paramètre | Description |
|-----------|-------------|
| `{source}` | Dossier source contenant les images à compresser (relatif au disque de stockage) |
| `{destination?}` | Dossier de destination (source si omis) |
| `{png-quality=45-50}` | Plage de qualité PNG (min-max, ex: 30-40) |
| `{jpg-quality=50}` | Qualité JPEG (0-100) |
| `{max-size=0}` | Ignorer les images plus petites que N KB (0 = désactivé) |
| `{--strip-meta}` | Supprime les métadonnées (Exif, commentaires, etc.) |
| `{--recursive}` | Traite les sous-dossiers récursivement |
| `{--dry-run}` | Simule la compression sans modifier les fichiers |
| `{--force}` | Force l'écrasement des fichiers existants |
| `{--skip-compressed}` | Ignore les images déjà compressées |

**Exemples :**

```bash
# Compression simple
./bin/images images:compress storage/app/public/images

# Compression avec destination personnalisée
./bin/images images:compress storage/app/public/images storage/app/public/compressed

# Compression récursive avec paramètres avancés
./bin/images images:compress storage/app/public/images --recursive --strip-meta --png-quality=30-40 --jpg-quality=40

# Ignorer les images déjà compressées
./bin/images images:compress storage/app/public/images --skip-compressed

# Ignorer les images plus petites que 50KB
./bin/images images:compress storage/app/public/images max-size=50

# Simulation (dry-run)
./bin/images images:compress storage/app/public/images --dry-run

# Utilisation de l'alias
./bin/images imc storage/app/public/images --recursive
```

### 8.3 Commande de scan

```bash
./bin/images images:scan {source} {depth=0} {output=json} {extensions*} {excludes*} {--options}
```

**Paramètres :**

| Paramètre | Description |
|-----------|-------------|
| `{source}` | Dossier source à scanner (relatif au disque de stockage) |
| `{depth=0}` | Profondeur maximale de scan (0 = illimitée) |
| `{::output->[json,array]=json}` | Format de sortie : `json` ou `array` (PHP) |
| `{extensions*}` | Extensions d'images à inclure (ex: `png jpg webp` ou `[png,jpg]`) |
| `{excludes*}` | Dossiers à exclure du scan |
| `{--hash}` | Inclut le hash MD5 de chaque image |
| `{--exclude-compressed}` | Exclut les images déjà compressées |

**Exemples :**

```bash
# Scan simple avec sortie JSON
./bin/images images:scan images

# Scan avec profondeur limitée et sortie PHP array
./bin/images images:scan images 2 array

# Scan avec filtrage d'extensions et exclusion de dossiers
./bin/images images:scan images 0 json [png,jpg] [compressed,thumbnails]

# Scan avec génération de hash MD5
./bin/images images:scan images --hash

# Scan avec exclusion des images compressées
./bin/images images:scan images --exclude-compressed

# Utilisation de l'alias
./bin/images ims images

# Combinaison de toutes les options
./bin/images images:scan images 2 json [png,jpg] [compressed,thumbnails] --hash --exclude-compressed
```

**Exemple de sortie JSON :**

```json
[
  {
    "path": "images/avatars/user1.png",
    "filename": "user1.png",
    "original_filename": "user1.png",
    "extension": "png",
    "mime_type": "image/png",
    "size": 12345,
    "width": 800,
    "height": 600,
    "hash": "5d41402abc4b2a76b9719d911017c592"
  }
]
```

**Exemple de sortie PHP (array) :**

```php
<?php

return [
    [
        'path' => 'images/avatars/user1.png',
        'filename' => 'user1.png',
        'original_filename' => 'user1.png',
        'extension' => 'png',
        'mime_type' => 'image/png',
        'size' => 12345,
        'width' => 800,
        'height' => 600,
        'hash' => '5d41402abc4b2a76b9719d911017c592',
    ],
];
```

### 8.4 Prérequis système pour la compression

Les outils suivants doivent être installés sur le système pour la compression :

```bash
# Ubuntu/Debian
sudo apt install pngquant jpegoptim

# macOS
brew install pngquant jpegoptim
```

### 8.5 Détection des images déjà compressées

La directive de compression détecte automatiquement les images déjà compressées via :

- **Taille** : Images < 10KB considérées comme compressées
- **JPEG** : Absence de métadonnées Exif
- **PNG** : Ratio de métadonnées faible (chunks standards)

### 8.6 Exemple de sortie

#### Compression

```bash
$ ./bin/images images:compress images --skip-compressed

📷 Starting image compression...

✅ Source directory: images
📁 Found 42 images to process

   ✅ images/photo1.jpg - saved 120.5 KB (65.2%)
   ⏭️  images/photo2.png - already compressed, skipping
   ✅ images/photo3.jpg - saved 45.2 KB (52.8%)
   ⏭️  images/photo4.png - already compressed, skipping

⏭️  Skipped 12 already compressed images

📊 Summary:
   📁 Files processed: 30
   ⏭️  Files skipped: 12
   📦 Size before: 15.24 MB
   📦 Size after: 5.67 MB
   💾 Space saved: 9.57 MB (62.8%)

✅ Compression completed
```

#### Scan

```bash
$ ./bin/images images:scan images 2 json [png,jpg] [compressed,thumbnails] --hash

🔍 Scanning images...

✅ Source directory: images
📁 Scanning: images

📊 Found: 42 images

💾 Output saved to: /storage/app/public/scan_result_2024-01-01_12-00-00.json

✅ Scan completed
```

---

## 9. Exemples complets

### 9.1 Upload d'avatar avec options

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

### 9.2 Galerie d'images

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

### 9.3 Export d'images avec filtrage

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

### 9.4 Audit d'images avec scan CLI

```bash
# 1. Générer un inventaire complet des images
./bin/images images:scan storage/app/public/images

# 2. Générer un inventaire avec hash MD5
./bin/images images:scan storage/app/public/images --hash

# 3. Générer un inventaire PHP pour traitement programmatique
./bin/images images:scan storage/app/public/images 0 array

# 4. Analyser uniquement les JPEG/PNG en excluant les dossiers compressés
./bin/images images:scan storage/app/public/images 0 json [png,jpg] [compressed,thumbnails] --hash

# 5. Compresser les images après audit
./bin/images images:compress storage/app/public/images --recursive --skip-compressed
```

### 9.5 Compression via CLI

```bash
# Compression optimisée pour le web
./bin/images images:compress storage/app/public/images storage/app/public/optimized \
    --recursive \
    --strip-meta \
    --png-quality=30-40 \
    --jpg-quality=40 \
    --skip-compressed
```

---

## 10. API Référence

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