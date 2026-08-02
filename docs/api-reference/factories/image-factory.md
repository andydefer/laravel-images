# ImageProcessorFactory - Référence Technique

## Description

Fabrique d'instances de processeurs d'images. Elle centralise la création des processeurs en fonction du driver demandé et garantit une configuration cohérente dans toute l'application.

## Hiérarchie / Implémentations

```
ImageProcessorFactory (classe finale)
    ├── Crée GdImageProcessor
    └── Crée ImagickImageProcessor
```

## Rôle principal

Encapsule la logique d'instanciation des processeurs d'images, masquant les détails d'implémentation des drivers sous-jacents (GD et Imagick). La factory permet de changer facilement de driver sans modifier le code client.

## API

### `create(string $driver, ImageStorageInterface $storage, FileSystemInterface $fileSystem): ImageProcessorInterface`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$driver` | `string` | Nom du driver ('gd' ou 'imagick') |
| `$storage` | `ImageStorageInterface` | Implémentation de stockage pour les opérations fichiers |
| `$fileSystem` | `FileSystemInterface` | Implémentation du système de fichiers |

**Retourne :** `ImageProcessorInterface` - Instance du processeur d'images

**Exceptions :** `RuntimeException` - Driver non supporté

**Exemple :**
```php
use AndyDefer\LaravelImages\Factories\ImageProcessorFactory;

$processor = ImageProcessorFactory::create(
    'gd',
    $storage,
    $fileSystem
);
```

### `getSupportedDrivers(): array`

**Retourne :** `array<int, string>` - Liste des noms de drivers supportés

**Exemple :**
```php
$drivers = ImageProcessorFactory::getSupportedDrivers();
// ['gd', 'imagick']
```

### `isSupported(string $driver): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$driver` | `string` | Nom du driver à vérifier |

**Retourne :** `bool` - `true` si le driver est supporté

**Exemple :**
```php
if (ImageProcessorFactory::isSupported('gd')) {
    // Le driver GD est disponible
}
```

## Cas d'utilisation

### Cas 1 : Création d'un processeur GD

Utilisation du driver GD (extension PHP standard, disponible par défaut dans la plupart des installations).

```php
use AndyDefer\LaravelImages\Factories\ImageProcessorFactory;

$gdProcessor = ImageProcessorFactory::create(
    'gd',
    $storage,
    $fileSystem
);

// Le processeur GD est prêt à être utilisé
$resizedPath = $gdProcessor->resize($imagePath, 800, 600);
```

### Cas 2 : Création d'un processeur Imagick

Utilisation du driver Imagick (nécessite l'extension ImageMagick, offre une meilleure qualité).

```php
use AndyDefer\LaravelImages\Factories\ImageProcessorFactory;

if (extension_loaded('imagick')) {
    $imagickProcessor = ImageProcessorFactory::create(
        'imagick',
        $storage,
        $fileSystem
    );
}
```

### Cas 3 : Sélection dynamique du driver

Choix du driver en fonction de la configuration ou de l'environnement.

```php
use AndyDefer\LaravelImages\Factories\ImageProcessorFactory;

// Exemple : choix basé sur une configuration
$driver = config('images.driver', 'gd');

try {
    $processor = ImageProcessorFactory::create(
        $driver,
        $storage,
        $fileSystem
    );
} catch (RuntimeException $e) {
    // Fallback vers GD
    $processor = ImageProcessorFactory::create('gd', $storage, $fileSystem);
}
```

### Cas 4 : Vérification avant création

Validation du driver avant instanciation pour éviter les exceptions.

```php
use AndyDefer\LaravelImages\Factories\ImageProcessorFactory;

$driver = 'imagick';

if (ImageProcessorFactory::isSupported($driver)) {
    $processor = ImageProcessorFactory::create($driver, $storage, $fileSystem);
} else {
    // Gérer le cas du driver non supporté
    $processor = ImageProcessorFactory::create('gd', $storage, $fileSystem);
}
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Driver non supporté | `RuntimeException` | `Unsupported image processor driver: "{driver}". Supported drivers: gd, imagick` |

## Intégration

La `ImageProcessorFactory` s'intègre avec :

- **ImageStorageInterface** : Fournit le stockage pour les opérations fichiers
- **FileSystemInterface** : Fournit les opérations système de fichiers
- **GdImageProcessor** : Processeur utilisant l'extension GD
- **ImagickImageProcessor** : Processeur utilisant l'extension Imagick
- **ImageServiceProvider** : Utilisée pour l'enregistrement dans le conteneur Laravel

Dans le `ImageServiceProvider` :

```php
$this->app->singleton(ImageProcessorInterface::class, function ($app) {
    $config = $app->make(ImagesConfigInterface::class);

    return ImageProcessorFactory::create(
        $config->getDriver(),
        $app->make(ImageStorageInterface::class),
        $app->make(FileSystemInterface::class),
    );
});
```

## Performance

- Instanciation en O(1) - pas de boucle ni d'allocation
- Les processeurs sont généralement créés une seule fois (singleton)
- Le match expression est optimisé pour les deux cas supportés

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |

| Extension | Support |
|-----------|---------|
| GD | ✅ Disponible par défaut |
| Imagick | ⚠️ Requiert l'installation de l'extension |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelImages\Factories\ImageProcessorFactory;
use AndyDefer\LaravelImages\Storage\LocalImageStorage;
use AndyDefer\PhpServices\Services\FileSystemService;

// 1. Préparer les dépendances
$fileSystem = new FileSystemService();
$storage = new LocalImageStorage($fileSystem, 'public');

// 2. Vérifier les drivers disponibles
$availableDrivers = ImageProcessorFactory::getSupportedDrivers();
echo "Drivers supportés : " . implode(', ', $availableDrivers) . "\n";

// 3. Créer un processeur GD
$processor = ImageProcessorFactory::create('gd', $storage, $fileSystem);
echo "Driver utilisé : " . $processor->getDriverName() . "\n";

// 4. Utiliser le processeur
$imagePath = new ImagePathVO('images/photo.jpg');
$resized = $processor->resize($imagePath, 800, 600, 85);

// 5. Vérifier si Imagick est disponible
if (ImageProcessorFactory::isSupported('imagick')) {
    $imagickProcessor = ImageProcessorFactory::create('imagick', $storage, $fileSystem);
}
```

## Voir aussi

- `GdImageProcessor` - Référence technique du processeur GD
- `ImagickImageProcessor` - Référence technique du processeur Imagick
- `ImageStorageInterface` - Interface de stockage
- `FileSystemInterface` - Interface système de fichiers
- [Intervention Image](https://image.intervention.io/) - Bibliothèque sous-jacente