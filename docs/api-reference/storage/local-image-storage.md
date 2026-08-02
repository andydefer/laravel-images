# LocalImageStorage - Référence Technique

## Description

Implémentation locale du stockage d'images. Cette classe utilise le système de fichiers local pour stocker, récupérer et supprimer des fichiers images, en s'appuyant sur une abstraction `FileSystemInterface`.

## Hiérarchie / Implémentations

```
ImageStorageInterface
    └── LocalImageStorage
```

## Rôle principal

Fournit une implémentation concrète du stockage d'images sur le système de fichiers local. Elle encapsule les opérations de fichiers (écriture, lecture, suppression) via une interface unifiée, permettant de changer facilement de système de stockage sans modifier le code client.

## API / Méthodes publiques

### `store(UploadedFile $file, string $path, string $filename): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$file` | `UploadedFile` | Fichier uploadé à stocker |
| `$path` | `string` | Chemin de stockage (relatif au base path) |
| `$filename` | `string` | Nom du fichier à utiliser |

**Retourne :** `string` - Chemin relatif du fichier stocké

**Exemple :**
```php
$storage = new LocalImageStorage($fileSystem, 'public');
$path = $storage->store($uploadedFile, 'images/avatars', 'user-42.jpg');
// Retourne : 'images/avatars/user-42.jpg'
```

### `delete(string $path): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$path` | `string` | Chemin relatif du fichier à supprimer |

**Retourne :** `bool` - `true` si le fichier a été supprimé ou n'existe pas

**Exemple :**
```php
$deleted = $storage->delete('images/avatars/user-42.jpg');
```

### `deleteMultiple(array $paths): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$paths` | `array<string>`` | Chemins relatifs des fichiers à supprimer |

**Retourne :** `bool` - `true` si tous les fichiers ont été supprimés ou n'existent pas

**Exemple :**
```php
$deleted = $storage->deleteMultiple([
    'images/avatars/user-1.jpg',
    'images/avatars/user-2.jpg',
]);
```

### `exists(string $path): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$path` | `string` | Chemin relatif à vérifier |

**Retourne :** `bool` - `true` si le fichier existe

**Exemple :**
```php
if ($storage->exists('images/avatars/user-42.jpg')) {
    // Le fichier existe
}
```

### `files(string $directory): array`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$directory` | `string` | Chemin du dossier (relatif au base path) |

**Retourne :** `array<string>` - Chemins complets des fichiers dans le dossier

**Exemple :**
```php
$files = $storage->files('images/avatars');
// Retourne : ['/full/path/to/images/avatars/user-1.jpg', ...]
```

### `getFullPath(string $path): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$path` | `string` | Chemin relatif |

**Retourne :** `string` - Chemin complet du système de fichiers

**Exemple :**
```php
$fullPath = $storage->getFullPath('images/avatars/user-42.jpg');
// Retourne : '/var/www/storage/app/public/images/avatars/user-42.jpg'
```

### `getBasePath(): string`

**Retourne :** `string` - Chemin de base actuel

**Exemple :**
```php
$basePath = $storage->getBasePath(); // 'public'
```

### `setBasePath(string $basePath): self`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$basePath` | `string` | Nouveau chemin de base |

**Retourne :** `self` - L'instance pour le chaînage

**Exemple :**
```php
$storage->setBasePath('private');
// Tous les chemins ultérieurs utiliseront 'private' comme base
```

## Cas d'utilisation

### Cas 1 : Stockage d'une image avec vérification

Upload et stockage d'une image dans une structure organisée.

```php
$storage = new LocalImageStorage($fileSystem, 'public');

// Organiser les images par modèle et type
$path = $storage->store(
    $uploadedFile,
    'users/123/avatars',
    'profile.jpg'
);

echo "Image stockée dans : " . $path . "\n";
```

### Cas 2 : Suppression en masse des images d'un dossier

Suppression de toutes les images d'un répertoire.

```php
$storage = new LocalImageStorage($fileSystem, 'public');

// Récupérer toutes les images d'un dossier
$files = $storage->files('users/123/gallery');

// Supprimer toutes les images
$deleted = $storage->deleteMultiple($files);

if ($deleted) {
    echo "Toutes les images ont été supprimées\n";
}
```

### Cas 3 : Changement dynamique du chemin de base

Basculement entre différents environnements de stockage.

```php
$storage = new LocalImageStorage($fileSystem);

// Stockage dans 'public' (fichiers publics)
$storage->setBasePath('public');
$publicPath = $storage->store($file, 'uploads', 'image.jpg');

// Stockage dans 'private' (fichiers privés)
$storage->setBasePath('private');
$privatePath = $storage->store($file, 'documents', 'image.jpg');
```

### Cas 4 : Vérification et nettoyage

Vérification de l'existence et nettoyage des fichiers orphelins.

```php
$storage = new LocalImageStorage($fileSystem, 'public');

$orphaned = [];
$files = $storage->files('temp');

foreach ($files as $file) {
    // Récupérer le chemin relatif
    $relative = str_replace(storage_path('app/public/'), '', $file);
    
    if (!$storage->exists($relative)) {
        $orphaned[] = $file;
    }
}

if (!empty($orphaned)) {
    $storage->deleteMultiple($orphaned);
}
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Répertoire impossible à créer | `RuntimeException` | `Cannot create directory: {directory}` |
| Fichier impossible à écrire | `RuntimeException` | `Cannot write file: {file}` |

## Intégration

Le `LocalImageStorage` s'intègre avec :

- **ImageStorageInterface** : Interface du stockage
- **FileSystemInterface** : Opérations système de fichiers
- **ImageService** : Service utilisant le stockage
- **ImagePathVO** : Manipulation des chemins

**Utilisation dans le service :**

```php
// Dans ImageService
private function storeFile(UploadedFile $file, Model $imageable, ImageType $type): string
{
    $path = $this->buildStoragePath($imageable, $type);
    $filename = $file->hashName();
    
    return $this->storage->store($file, $path, $filename);
}
```

## Performance

- Les opérations sont basées sur les fonctions natives PHP (`file_exists`, `rename`, etc.)
- Le stockage est direct sans couche réseau (performant pour les fichiers locaux)
- Les appels à `files()` utilisent `glob` qui est optimisé

**Bonnes pratiques :**
- Organiser les fichiers par modèle/type pour éviter les conflits de noms
- Utiliser `hashName()` pour les noms de fichiers uniques
- Supprimer les fichiers physiques en même temps que les enregistrements en base
- Utiliser `deleteMultiple()` pour les suppressions groupées

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |

| Framework | Support |
|-----------|---------|
| Laravel 10+ | ✅ Complet |
| Laravel 9 | ✅ Complet |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelImages\Storage\LocalImageStorage;
use AndyDefer\PhpServices\Services\FileSystemService;
use Illuminate\Http\UploadedFile;

// 1. Créer le système de fichiers et le stockage
$fileSystem = new FileSystemService();
$storage = new LocalImageStorage($fileSystem, 'public');

// 2. Préparer le fichier (simulé)
$file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

// 3. Stocker l'image
$path = $storage->store($file, 'users/123/avatars', 'profile.jpg');
echo "Image stockée : " . $path . "\n";

// 4. Vérifier l'existence
if ($storage->exists($path)) {
    echo "Le fichier existe\n";
}

// 5. Obtenir le chemin complet
$fullPath = $storage->getFullPath($path);
echo "Chemin complet : " . $fullPath . "\n";

// 6. Lister les fichiers du dossier
$files = $storage->files('users/123/avatars');
echo "Fichiers : " . count($files) . "\n";

// 7. Supprimer l'image
$deleted = $storage->delete($path);
echo "Supprimée : " . ($deleted ? 'oui' : 'non') . "\n";

// 8. Changer le chemin de base
$storage->setBasePath('private');
$privatePath = $storage->store($file, 'users/123/avatars', 'private.jpg');
echo "Stockage privé : " . $privatePath . "\n";
```

## Voir aussi

- `ImageStorageInterface` - Interface du stockage
- `FileSystemInterface` - Interface du système de fichiers
- `ImageService` - Service utilisant le stockage
- `ImagePathVO` - Value Object des chemins
- `LocalImageStorageTest` - Tests du stockage local