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
11. [Événements automatiques](#11-événements-automatiques)
12. [Relations inverses (Light/Dark)](#12-relations-inverses-lightdark)

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
$image->id;                // UUID unique
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
$image->inverse_image_id;  // UUID de l'image inverse (light/dark)
```

### 3.2 Album

Un album regroupe plusieurs images avec un ordre défini.

```php
use AndyDefer\LaravelImages\Models\Album;

$album->id;            // UUID unique
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

### 3.4 Trait HasMediables

Le package fournit un trait `HasMediables` pour ajouter des attributs calculés à vos modèles.

#### Installation dans un modèle

```php
<?php

namespace App\Models;

use AndyDefer\LaravelImages\Traits\HasMediables;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasMediables;

    // Aucune relation nécessaire !
    // Les attributs sont disponibles directement
}
```

#### Attributs disponibles

| Attribut | Type | Description |
|----------|------|-------------|
| `has_images` | `bool` | Vérifie si le modèle a des images |
| `images_count` | `int` | Nombre total d'images |
| `primary_image` | `Image|null` | Image principale |
| `avatar` | `Image|null` | Image de type AVATAR |
| `cover` | `Image|null` | Image de type COVER |
| `banner` | `Image|null` | Image de type BANNER |
| `logo` | `Image|null` | Image de type LOGO |
| `icon` | `Image|null` | Image de type ICON |
| `gallery_images` | `Collection<Image>` | Images de type GALLERY |
| `has_albums` | `bool` | Vérifie si le modèle a des albums |
| `albums_count` | `int` | Nombre total d'albums |
| `primary_album` | `Album|null` | Premier album créé |
| `featured_album` | `Album|null` | Album mis en avant |
| `public_albums` | `Collection<Album>` | Albums publics |
| `private_albums` | `Collection<Album>` | Albums privés |

#### Exemple d'utilisation

```php
$user = User::find(1);

// Vérifier si l'utilisateur a des images
if ($user->has_images) {
    echo "L'utilisateur a {$user->images_count} images";
}

// Récupérer l'avatar
$avatar = $user->avatar;
if ($avatar) {
    echo $avatar->full_url;
}

// Récupérer les albums publics
foreach ($user->public_albums as $album) {
    echo $album->name;
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

// Une image par UUID
$image = $imageService->findImage('550e8400-e29b-41d4-a716-446655440000');

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
$imageService->reorder(['uuid1', 'uuid2', 'uuid3']);
```

### 4.5 Suppression

```php
// Supprimer une image
$imageService->delete($imageId, deleteFile: true);

// Supprimer plusieurs images
$imageService->deleteMultiple(['uuid1', 'uuid2', 'uuid3'], deleteFile: true);

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
$albumService->addImagesToAlbum($album, ['uuid1', 'uuid2', 'uuid3', 'uuid4', 'uuid5']);

// Ajouter une image avec une position spécifique
$albumService->addImageToAlbum($album, $imageId, $order = 3);

// Réorganiser les images
$albumService->reorderAlbumImages($album, ['uuid3', 'uuid1', 'uuid4', 'uuid2', 'uuid5']);

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
$storage = new LocalImageStorage($fileSystem);
```

**Note :** Le stockage utilise des chemins relatifs au dossier courant d'exécution. Aucun préfixe n'est ajouté automatiquement.

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
./bin/images images:compress {source} {destination} {--options}
```

**Paramètres :**

| Paramètre | Description |
|-----------|-------------|
| `{source}` | Dossier source contenant les images à compresser |
| `{destination}` | Dossier de destination (obligatoire) |
| `{png-quality=45-50}` | Plage de qualité PNG (min-max, ex: 30-40) |
| `{jpg-quality=50}` | Qualité JPEG (0-100) |
| `{max-size=0}` | Ignorer les images plus petites que N KB (0 = désactivé) |
| `{--strip-meta}` | Supprime les métadonnées (Exif, commentaires, etc.) |
| `{--recursive}` | Traite les sous-dossiers récursivement et conserve la structure |
| `{--dry-run}` | Simule la compression sans modifier les fichiers |
| `{--force}` | Force l'écrasement des fichiers existants |
| `{--skip-compressed}` | Ignore les images déjà compressées |

**Exemples :**

```bash
# Compression simple
./bin/images images:compress storage/app/public/images storage/app/public/compressed

# Compression récursive avec conservation de la structure
./bin/images images:compress storage/app/public/images storage/app/public/compressed --recursive

# Compression avec paramètres avancés
./bin/images images:compress storage/app/public/images storage/app/public/compressed --recursive --strip-meta --png-quality=30-40 --jpg-quality=40

# Ignorer les images déjà compressées
./bin/images images:compress storage/app/public/images storage/app/public/compressed --skip-compressed

# Ignorer les images plus petites que 50KB
./bin/images images:compress storage/app/public/images storage/app/public/compressed max-size=50

# Simulation (dry-run)
./bin/images images:compress storage/app/public/images storage/app/public/compressed --dry-run --recursive

# Utilisation de l'alias
./bin/images imc storage/app/public/images storage/app/public/compressed --recursive
```

**Conservation de la structure :**

Avec l'option `--recursive`, la structure des dossiers est préservée :

```
Source :                          Destination :
storage/app/public/images/        storage/app/public/compressed/
├── avatars/                      ├── avatars/
│   └── patient.jpg               │   └── patient.jpg
├── banners/                      ├── banners/
│   └── hero.png                  │   └── hero.png
└── gallery/                      └── gallery/
    └── photo.jpg                     └── photo.jpg
```

### 8.3 Commande de scan

```bash
./bin/images images:scan {source} {output} {depth=0} {extensions*} {excludes*} {--options}
```

**Paramètres :**

| Paramètre | Description |
|-----------|-------------|
| `{source}` | Dossier source à scanner |
| `{output}` | Fichier de sortie (.json ou .php) |
| `{depth=0}` | Profondeur maximale de scan (0 = illimitée) |
| `{extensions*}` | Extensions d'images à inclure (ex: `png jpg webp` ou `[png,jpg]`) |
| `{excludes*}` | Dossiers à exclure du scan |
| `{--hash}` | Inclut le hash MD5 de chaque image |
| `{--relative}` | Rend les chemins relatifs au répertoire source |

**Exemples :**

```bash
# Scan simple avec sortie JSON
./bin/images images:scan storage/app/public/images scan-result.json

# Scan avec profondeur limitée
./bin/images images:scan storage/app/public/images scan-depth.json 1

# Scan avec filtrage d'extensions
./bin/images images:scan storage/app/public/images scan-ext.json 0 [png,jpg]

# Scan avec exclusion de dossiers
./bin/images images:scan storage/app/public/images scan-exclude.json 0 [] [compressed,thumbnails]

# Scan avec génération de hash MD5
./bin/images images:scan storage/app/public/images scan-hash.json --hash

# Scan avec chemins relatifs
./bin/images images:scan storage/app/public/images scan-relative.json --relative

# Sortie PHP array
./bin/images images:scan storage/app/public/images scan-result.php

# Utilisation de l'alias
./bin/images ims storage/app/public/images scan-result.json

# Combinaison de toutes les options
./bin/images images:scan storage/app/public/images scan-all.json 1 [png,jpg] [compressed,thumbnails] --hash --relative
```

**Exemple de sortie JSON (sans --relative) :**

```json
[
  {
    "path": "storage/app/public/images/avatars/user1.png",
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

**Exemple de sortie JSON (avec --relative) :**

```json
[
  {
    "path": "avatars/user1.png",
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

**Exemple de sortie PHP :**

```php
<?php

return [
    [
        'path' => 'avatars/user1.png',
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
$ ./bin/images images:compress storage/app/public/images storage/app/public/compressed --skip-compressed --recursive

📷 Starting image compression...

✅ Source directory: storage/app/public/images
📁 Found 42 images to process

   ✅ avatars/photo1.jpg - saved 120.5 KB (65.2%)
   ⏭️  banners/photo2.png - already compressed, skipping
   ✅ gallery/photo3.jpg - saved 45.2 KB (52.8%)
   ⏭️  avatars/photo4.png - already compressed, skipping

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
$ ./bin/images images:scan storage/app/public/images scan-result.json 2 [png,jpg] [compressed,thumbnails] --hash --relative

🔍 Scanning images...

✅ Source directory: storage/app/public/images
📁 Scanning: storage/app/public/images

📊 Found: 42 images

💾 Output saved to: scan-result.json

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
./bin/images images:scan storage/app/public/images scan-result.json

# 2. Générer un inventaire avec hash MD5
./bin/images images:scan storage/app/public/images scan-hash.json --hash

# 3. Générer un inventaire avec chemins relatifs
./bin/images images:scan storage/app/public/images scan-relative.json --relative

# 4. Générer un inventaire PHP pour traitement programmatique
./bin/images images:scan storage/app/public/images scan-result.php

# 5. Analyser uniquement les JPEG/PNG en excluant les dossiers compressés
./bin/images images:scan storage/app/public/images scan-filtered.json 0 [png,jpg] [compressed,thumbnails] --hash --relative

# 6. Compresser les images après audit
./bin/images images:compress storage/app/public/images storage/app/public/optimized --recursive --skip-compressed
```

### 9.5 Compression via CLI

```bash
# Compression optimisée pour le web avec conservation de la structure
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
| `findImage(string $id): ?Image` | Trouve une image par UUID |
| `upload(...): Image` | Upload une image |
| `uploadMultiple(...): Collection` | Upload plusieurs images |
| `update(ImageRecord $record, string $id): Image` | Met à jour une image |
| `delete(string $id, bool $deleteFile): void` | Supprime une image |
| `deleteMultiple(array<string> $ids, bool $deleteFile): void` | Supprime plusieurs images |
| `deleteAllForModel(Model $model, bool $deleteFile): void` | Supprime toutes les images d'un modèle |
| `getImagesForModel(Model $model, ?ImageType $type): Collection` | Récupère les images d'un modèle |
| `getPrimaryImage(Model $model): ?Image` | Récupère l'image principale |
| `setAsPrimary(string $id, Model $model): void` | Définit une image comme principale |
| `countImages(Model $model, ?ImageType $type): int` | Compte les images |
| `getImagesUpdatedAfter(DateTimeVO $date): Collection` | Images mises à jour après une date |
| `reorder(array<string> $ids): void` | Réorganise les images |
| `getThumbnailUrl(string $imageId, string $size): string` | URL de la miniature |
| `syncInverseRelation(Image $image): void` | Synchronise la relation inverse (light/dark) |
| `getImageProcessor(): ImageProcessorInterface` | Retourne le processeur |
| `getStorage(): ImageStorageInterface` | Retourne le stockage |

### AlbumService

| Méthode | Description |
|---------|-------------|
| `createAlbum(...): Album` | Crée un album |
| `addImagesToAlbum(Album $album, array<string> $imageIds): void` | Ajoute des images |
| `addImageToAlbum(Album $album, string $imageId, int $order): void` | Ajoute une image |
| `removeImageFromAlbum(Album $album, string $imageId): void` | Retire une image |
| `removeAllImagesFromAlbum(Album $album): void` | Vide un album |
| `setCoverImage(Album $album, string $imageId): void` | Définit la couverture |
| `getAlbumImages(Album $album): Collection` | Images d'un album |
| `getAlbumsForModel(Model $model, bool $onlyPublic): Collection` | Albums d'un modèle |
| `getAlbumBySlug(string|SlugVO $slug): ?Album` | Album par slug |
| `updateAlbum(string $id, AlbumOptionsRecord $options): Album` | Met à jour un album |
| `deleteAlbum(string $id, bool $deleteImages): void` | Supprime un album |
| `reorderAlbumImages(Album $album, array<string> $imageIds): void` | Réorganise les images |
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

## 11. Événements automatiques

Le package utilise des **observers Eloquent** pour automatiser certaines opérations critiques sans intervention manuelle.

### 11.1 ImageObserver

L'`ImageObserver` gère automatiquement les relations inverses entre les variantes d'images (claires/sombres) et maintient l'intégrité référentielle.

| Événement | Action automatique | Description |
|-----------|-------------------|-------------|
| `created` | `syncInverseRelation()` | Lorsqu'une nouvelle image est créée, le système détecte automatiquement si c'est une variante `-light` ou `-dark` et la lie à son homologue si elle existe dans le même contexte (même modèle parent, même type). |
| `updated` | `syncInverseRelation()` | Lorsque le nom de fichier d'une image est modifié, la relation inverse est réévaluée pour maintenir la cohérence. |
| `deleted` | `inverse_image_id = null` | Lorsqu'une image est supprimée, toutes les images qui la référencent comme `inverse_image_id` voient cette référence automatiquement mise à `null` pour éviter les orphelins. |

**Exemple concret :**

```php
// Upload de deux images variantes
$darkFile = UploadedFile::fake()->image('banner-dark.jpg');
$lightFile = UploadedFile::fake()->image('banner-light.jpg');

$darkImage = $imageService->upload($darkFile, $page, null, ImageType::BANNER);
$lightImage = $imageService->upload($lightFile, $page, null, ImageType::BANNER);

// L'ImageObserver a automatiquement lié les deux images
$darkImage->refresh();
$lightImage->refresh();

// Les relations inverses sont automatiquement synchronisées
echo $darkImage->inverse_image_id; // UUID de l'image light
echo $lightImage->inverse_image_id; // UUID de l'image dark
```

### 11.2 AlbumObserver

L'`AlbumObserver` maintient l'intégrité des relations entre albums et images.

| Événement | Action automatique | Description |
|-----------|-------------------|-------------|
| `deleting` | `images()->detach()` | Avant la suppression (soft delete) d'un album, toutes les relations avec ses images sont automatiquement détachées pour éviter les enregistrements orphelins dans la table pivot. |
| `forceDeleted` | `images()->detach()` | Lors de la suppression définitive d'un album, les relations sont également nettoyées pour maintenir la cohérence. |

**Exemple concret :**

```php
// Création d'un album avec des images
$album = $albumService->createAlbum($user, 'Mes photos');
$albumService->addImagesToAlbum($album, ['uuid1', 'uuid2', 'uuid3']);

// Suppression de l'album
$album->delete();

// L'AlbumObserver a automatiquement détaché toutes les images
// Les images 1, 2, 3 existent toujours mais ne sont plus liées à l'album
```

---

## 12. Relations inverses (Light/Dark)

### 12.1 Principe

Le package supporte nativement la gestion des **paires d'images inverses** (light/dark) grâce à la colonne `inverse_image_id` dans la table `images`. Cette fonctionnalité est particulièrement utile pour :

- **Bannières thématiques** : Afficher une version claire ou sombre selon le thème de l'interface
- **Logos adaptatifs** : Proposer un logo clair sur fond sombre et vice-versa
- **Icônes contextuelles** : Alterner entre variantes selon le contexte d'affichage

### 12.2 Comment ça fonctionne

La détection des paires se base sur la convention de nommage des fichiers :

| Variante | Pattern | Exemple |
|----------|---------|---------|
| Dark | `*-dark.*` | `logo-dark.png`, `banner-dark.jpg` |
| Light | `*-light.*` | `logo-light.png`, `banner-light.jpg` |

### 12.3 Synchronisation automatique

La synchronisation est entièrement **automatique** via l'`ImageObserver` :

1. **Création** : Lorsque vous uploadez une image avec un nom contenant `-light` ou `-dark`, le système recherche automatiquement son homologue dans le même contexte (même `imageable_type`, `imageable_id` et `type`).

2. **Liaison** : Si l'homologue existe, les deux images sont liées bidirectionnellement via `inverse_image_id`.

3. **Nettoyage** : Si l'homologue est supprimé, la référence est automatiquement mise à `null`.

### 12.4 Utilisation

```php
// Upload des deux variantes (l'ordre n'a pas d'importance)
$darkFile = UploadedFile::fake()->image('hero-dark.jpg');
$lightFile = UploadedFile::fake()->image('hero-light.jpg');

// L'Observer lie automatiquement les deux images
$darkImage = $imageService->upload($darkFile, $page, null, ImageType::BANNER);
$lightImage = $imageService->upload($lightFile, $page, null, ImageType::BANNER);

// La liaison est automatique
$darkImage->refresh();
echo $darkImage->inverse_image_id; // UUID de l'image light

// Récupérer l'image inverse via la relation Eloquent
$inverse = $darkImage->inverseImage;
echo $inverse->filename; // 'hero-light.jpg'
```

### 12.5 Synchronisation manuelle

Bien que la synchronisation soit automatique, vous pouvez également la déclencher manuellement :

```php
use AndyDefer\LaravelImages\Models\Image;

$image = Image::find('550e8400-e29b-41d4-a716-446655440000');
$imageService->syncInverseRelation($image);
```

### 12.6 Cas d'usage avancé

```php
// Récupérer la bonne variante selon le thème de l'utilisateur
function getThemeImage(Image $image, string $theme): ?Image
{
    if ($theme === 'dark' && $image->original_filename) {
        // Si l'image actuelle est light, chercher sa version dark
        return $image->inverseImage ?? $image;
    }
    
    if ($theme === 'light' && $image->original_filename) {
        // Si l'image actuelle est dark, chercher sa version light
        return $image->inverseImage ?? $image;
    }
    
    return $image;
}

// Dans un template Blade
@php
    $banner = $page->getPrimaryImage();
    $displayedBanner = getThemeImage($banner, auth()->user()->theme);
@endphp

<img src="{{ $displayedBanner->full_url }}" alt="Bannière" />
```

### 12.7 Limitations

- La détection se base uniquement sur le nom de fichier original (`original_filename`)
- Les deux images doivent être dans le **même contexte** (même `imageable_type`, `imageable_id`, `type`)
- La relation est stockée via une colonne `inverse_image_id` de type UUID dans la table `images`

---

## Licence

MIT © [Andy Defer](https://github.com/andydefer)
```