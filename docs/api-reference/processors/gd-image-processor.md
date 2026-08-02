# GdImageProcessor - Référence Technique

## Description

Processeur d'images utilisant l'extension GD de PHP. Il assure le redimensionnement et la manipulation d'images en s'appuyant sur la bibliothèque GD, disponible par défaut dans la plupart des installations PHP.

## Hiérarchie / Implémentations

```
ImageProcessorInterface
    └── GdImageProcessor
```

## Rôle principal

Fournit une implémentation du processeur d'images basée sur l'extension GD. Elle prend en charge les formats courants (JPEG, PNG, WebP, GIF) et permet le redimensionnement avec gestion de la qualité.

**Limitations :**
- GD est moins performant qu'Imagick pour les grandes images
- Support limité des espaces colorimétriques
- Pas de support natif des formats avancés (HEIC, AVIF)

## API

### `resize(ImagePathVO $imagePath, int $width, ?int $height = null, ?int $quality = null): ImagePathVO`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$imagePath` | `ImagePathVO` | Chemin de l'image source |
| `$width` | `int` | Largeur cible |
| `$height` | `int|null` | Hauteur cible (null = conservation du ratio) |
| `$quality` | `int|null` | Qualité JPEG/WebP (1-100) |

**Retourne :** `ImagePathVO` - Chemin de l'image redimensionnée

**Exceptions :** `RuntimeException` - Image source introuvable

**Exemple :**
```php
$processor = new GdImageProcessor($storage, $fileSystem);
$resized = $processor->resize($imagePath, 800, 600, 85);
```

### `read(string $path): mixed`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$path` | `string` | Chemin complet du fichier |

**Retourne :** `mixed` - Instance de l'image Intervention

**Exemple :**
```php
$image = $processor->read('/path/to/image.jpg');
```

### `save(mixed $image, string $path): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$image` | `mixed` | Instance de l'image Intervention |
| `$path` | `string` | Chemin de destination |

**Exemple :**
```php
$processor->save($image, '/path/to/output.jpg');
```

### `getDriverName(): string`

**Retourne :** `string` - 'gd'

**Exemple :**
```php
$name = $processor->getDriverName(); // 'gd'
```

## Cas d'utilisation

### Cas 1 : Redimensionnement simple

Redimensionne une image en conservant le ratio d'aspect (hauteur automatique).

```php
use AndyDefer\LaravelImages\Processors\GdImageProcessor;

$processor = new GdImageProcessor($storage, $fileSystem);

// Redimensionne à 800px de large, hauteur automatique
$resizedPath = $processor->resize($imagePath, 800);
```

### Cas 2 : Redimensionnement avec dimensions exactes

Redimensionne une image avec des dimensions précises.

```php
// Redimensionne à 400x300
$resizedPath = $processor->resize($imagePath, 400, 300);
```

### Cas 3 : Redimensionnement avec qualité contrôlée

Redimensionne une image en spécifiant la qualité de compression.

```php
// Redimensionne à 800x600 avec qualité élevée
$resizedPath = $processor->resize($imagePath, 800, 600, 90);

// Redimensionne avec qualité faible (fichier plus petit)
$resizedPath = $processor->resize($imagePath, 800, 600, 30);
```

### Cas 4 : Génération de thumbnails

Redimensionnement pour créer des miniatures.

```php
$thumbnailSizes = [
    'small' => ['width' => 150, 'height' => 150],
    'medium' => ['width' => 300, 'height' => 300],
    'large' => ['width' => 600, 'height' => 600],
];

foreach ($thumbnailSizes as $name => $dimensions) {
    $thumbnail = $processor->resize(
        $imagePath,
        $dimensions['width'],
        $dimensions['height'],
        85
    );
}
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Image source introuvable | `RuntimeException` | `Image not found: {imagePath}` |

## Intégration

Le `GdImageProcessor` s'intègre avec :

- **ImageStorageInterface** : Fournit l'accès au stockage des fichiers
- **FileSystemInterface** : Fournit les opérations système de fichiers
- **ImagePathVO** : Encapsule les chemins d'images
- **ImageProcessorFactory** : Crée des instances du processeur

**Création via la factory :**

```php
use AndyDefer\LaravelImages\Factories\ImageProcessorFactory;

$processor = ImageProcessorFactory::create('gd', $storage, $fileSystem);
```

## Performance

- GD est basé sur la bibliothèque standard de PHP
- Performant pour les petites et moyennes images
- Moins efficace que Imagick pour les très grandes images
- La qualité de compression est gérée via les paramètres natifs de GD

**Bonnes pratiques :**
- Utiliser GD pour les images de taille modeste (< 5MB)
- Privilégier Imagick pour les images volumineuses ou les traitements complexes
- Limiter le nombre de redimensionnements simultanés

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |
| PHP 7.4 | ✅ Complet |

| Extension | Support |
|-----------|---------|
| GD | ✅ Requise (disponible par défaut) |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelImages\Processors\GdImageProcessor;
use AndyDefer\LaravelImages\Storage\LocalImageStorage;
use AndyDefer\LaravelImages\ValueObjects\ImagePathVO;
use AndyDefer\PhpServices\Services\FileSystemService;

// 1. Préparer les dépendances
$fileSystem = new FileSystemService();
$storage = new LocalImageStorage($fileSystem, 'public');

// 2. Créer le processeur
$processor = new GdImageProcessor($storage, $fileSystem);

// 3. Définir le chemin de l'image
$imagePath = new ImagePathVO('images/photos/vacation.jpg');

// 4. Redimensionner avec différentes tailles
$sizes = [
    ['width' => 1920, 'height' => 1080, 'quality' => 90],  // Large
    ['width' => 800, 'height' => 600, 'quality' => 85],    // Moyen
    ['width' => 400, 'height' => 300, 'quality' => 80],    // Petit
    ['width' => 150, 'height' => 150, 'quality' => 75],    // Miniature
];

foreach ($sizes as $config) {
    $resized = $processor->resize(
        $imagePath,
        $config['width'],
        $config['height'],
        $config['quality']
    );

    echo "Image redimensionnée : " . $resized->getFullPath() . "\n";
}

// 5. Vérifier le driver utilisé
echo "Driver : " . $processor->getDriverName() . "\n";
// Affiche : Driver : gd
```

## Voir aussi

- `ImageProcessorInterface` - Interface du processeur d'images
- `ImagickImageProcessor` - Processeur utilisant ImageMagick
- `ImageProcessorFactory` - Factory pour créer des processeurs
- `ImagePathVO` - Value Object pour les chemins d'images
- [Intervention Image - GD Driver](https://image.intervention.io/v3/basics/configuration-drivers#driver-for-gd-library)
- [Documentation PHP GD](https://www.php.net/manual/fr/book.image.php)