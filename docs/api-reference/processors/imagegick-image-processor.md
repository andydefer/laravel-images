# ImagickImageProcessor - Référence Technique

## Description

Processeur d'images utilisant l'extension Imagick (ImageMagick) de PHP. Il offre des capacités de manipulation d'images supérieures à GD, avec un meilleur support des formats, des performances optimisées et une qualité de rendu exceptionnelle.

## Hiérarchie / Implémentations

```
ImageProcessorInterface
    └── ImagickImageProcessor
```

## Rôle principal

Fournit une implémentation du processeur d'images basée sur ImageMagick via l'extension PHP Imagick. Ce processeur est recommandé pour les applications nécessitant des traitements d'images avancés, une haute qualité et un large support de formats.

**Avantages :**
- Support de nombreux formats (HEIC, AVIF, TIFF, etc.)
- Meilleure qualité de rendu que GD
- Performances supérieures pour les grandes images
- Support avancé des espaces colorimétriques
- Traitement en mémoire plus efficace

**Prérequis :**
- Extension PHP Imagick installée
- Bibliothèque ImageMagick installée sur le système

## API

### `resize(ImagePathVO $imagePath, int $width, ?int $height = null, ?int $quality = null): ImagePathVO`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$imagePath` | `ImagePathVO` | Chemin de l'image source |
| `$width` | `int` | Largeur cible |
| `$height` | `int|null` | Hauteur cible (null = conservation du ratio) |
| `$quality` | `int|null` | Qualité JPEG/WebP/AVIF (1-100) |

**Retourne :** `ImagePathVO` - Chemin de l'image redimensionnée

**Exceptions :** `RuntimeException` - Image source introuvable

**Exemple :**
```php
$processor = new ImagickImageProcessor($storage, $fileSystem);
$resized = $processor->resize($imagePath, 1920, 1080, 90);
```

### `read(string $path): mixed`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$path` | `string` | Chemin complet du fichier |

**Retourne :** `mixed` - Instance de l'image Intervention

**Exemple :**
```php
$image = $processor->read('/path/to/image.heic');
```

### `save(mixed $image, string $path): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$image` | `mixed` | Instance de l'image Intervention |
| `$path` | `string` | Chemin de destination |

**Exemple :**
```php
$processor->save($image, '/path/to/output.avif');
```

### `getDriverName(): string`

**Retourne :** `string` - 'imagick'

**Exemple :**
```php
$name = $processor->getDriverName(); // 'imagick'
```

## Cas d'utilisation

### Cas 1 : Redimensionnement d'images de grande taille

Imagick est particulièrement performant pour les images volumineuses.

```php
use AndyDefer\LaravelImages\Processors\ImagickImageProcessor;

$processor = new ImagickImageProcessor($storage, $fileSystem);

// Redimensionne une image 4K vers 1080p
$resizedPath = $processor->resize(
    $imagePath,
    1920,
    1080,
    90
);
```

### Cas 2 : Redimensionnement avec qualité optimale

Imagick offre une meilleure gestion de la qualité que GD.

```php
// Redimensionnement avec qualité "photographique"
$resizedPath = $processor->resize($imagePath, 800, 600, 95);

// Redimensionnement avec qualité "web" (bon compromis)
$resizedPath = $processor->resize($imagePath, 800, 600, 75);
```

### Cas 3 : Génération de thumbnails pour e-commerce

Imagick est idéal pour les applications e-commerce nécessitant de nombreuses miniatures.

```php
$thumbnailSizes = [
    'catalog' => ['width' => 400, 'height' => 400, 'quality' => 85],
    'cart' => ['width' => 150, 'height' => 150, 'quality' => 80],
    'zoom' => ['width' => 1200, 'height' => 1200, 'quality' => 92],
];

foreach ($thumbnailSizes as $name => $config) {
    $thumbnail = $processor->resize(
        $imagePath,
        $config['width'],
        $config['height'],
        $config['quality']
    );
    // Stocker le thumbnail
}
```

### Cas 4 : Traitement d'images en haute résolution

Imagick gère efficacement les images avec des métadonnées Exif.

```php
// Redimensionnement tout en préservant les métadonnées
$resizedPath = $processor->resize($imagePath, 1920, 1080, 90);

// Le résultat conserve les métadonnées Exif
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Image source introuvable | `RuntimeException` | `Image not found: {imagePath}` |
| Extension Imagick non installée | `RuntimeException` | `Imagick extension is not installed` |

## Intégration

Le `ImagickImageProcessor` s'intègre avec :

- **ImageStorageInterface** : Fournit l'accès au stockage des fichiers
- **FileSystemInterface** : Fournit les opérations système de fichiers
- **ImagePathVO** : Encapsule les chemins d'images
- **ImageProcessorFactory** : Crée des instances du processeur

**Création via la factory :**

```php
use AndyDefer\LaravelImages\Factories\ImageProcessorFactory;

$processor = ImageProcessorFactory::create('imagick', $storage, $fileSystem);
```

## Performance

- Imagick est significativement plus performant que GD pour les grandes images
- Utilisation optimisée de la mémoire grâce à ImageMagick
- Support de la compression progressive (JPEG progressif)
- Traitement en parallèle pour les opérations complexes

**Bonnes pratiques :**
- Utiliser Imagick pour les images > 5MB ou > 2000px
- Préférer Imagick pour les applications nécessitant une haute qualité
- Limiter la qualité à 85-90 pour un bon compromis taille/qualité
- Vérifier la disponibilité d'Imagick avant de l'utiliser

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |

| Extension | Support |
|-----------|---------|
| Imagick | ⚠️ Requise (installation séparée) |

**Vérification de disponibilité :**

```php
if (extension_loaded('imagick')) {
    $processor = new ImagickImageProcessor($storage, $fileSystem);
} else {
    // Fallback vers GD
    $processor = new GdImageProcessor($storage, $fileSystem);
}
```

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelImages\Processors\ImagickImageProcessor;
use AndyDefer\LaravelImages\Storage\LocalImageStorage;
use AndyDefer\LaravelImages\ValueObjects\ImagePathVO;
use AndyDefer\PhpServices\Services\FileSystemService;

// 1. Vérifier la disponibilité d'Imagick
if (!extension_loaded('imagick')) {
    throw new RuntimeException('Imagick extension is required.');
}

// 2. Préparer les dépendances
$fileSystem = new FileSystemService();
$storage = new LocalImageStorage($fileSystem, 'public');

// 3. Créer le processeur
$processor = new ImagickImageProcessor($storage, $fileSystem);

// 4. Définir le chemin de l'image
$imagePath = new ImagePathVO('images/photos/vacation.jpg');

// 5. Redimensionner avec différentes tailles et qualités
$variants = [
    [
        'width' => 3840,
        'height' => 2160,
        'quality' => 92,
        'label' => '4K'
    ],
    [
        'width' => 1920,
        'height' => 1080,
        'quality' => 88,
        'label' => 'Full HD'
    ],
    [
        'width' => 800,
        'height' => 600,
        'quality' => 80,
        'label' => 'Web'
    ],
    [
        'width' => 150,
        'height' => 150,
        'quality' => 75,
        'label' => 'Thumbnail'
    ],
];

foreach ($variants as $variant) {
    $resized = $processor->resize(
        $imagePath,
        $variant['width'],
        $variant['height'],
        $variant['quality']
    );
    
    echo $variant['label'] . " : " . $resized->getFullPath() . "\n";
}

// 6. Vérifier le driver utilisé
echo "Driver : " . $processor->getDriverName() . "\n";
// Affiche : Driver : imagick
```

## Voir aussi

- `ImageProcessorInterface` - Interface du processeur d'images
- `GdImageProcessor` - Processeur utilisant GD
- `ImageProcessorFactory` - Factory pour créer des processeurs
- `ImagePathVO` - Value Object pour les chemins d'images
- [Intervention Image - Imagick Driver](https://image.intervention.io/v3/basics/configuration-drivers#driver-for-imagick)
- [Documentation PHP Imagick](https://www.php.net/manual/fr/book.imagick.php)
- [ImageMagick](https://imagemagick.org/)