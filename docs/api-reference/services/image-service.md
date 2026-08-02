# ImageService - Référence Technique

## Description

Service central de gestion des images. Fournit l'ensemble des opérations nécessaires à la manipulation des images : upload, suppression, récupération, réorganisation et génération de miniatures.

## Hiérarchie / Implémentations

```
ImageServiceInterface
    └── ImageService
```

## Rôle principal

Orchestre toutes les opérations liées aux images. Il coordonne les interactions entre le repository `ImageRepository`, le processeur d'images `ImageProcessorInterface` et le stockage `ImageStorageInterface` pour offrir une API complète de gestion d'images.

## API / Méthodes publiques

### `findImage(int $id): ?Image`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$id` | `int` | Identifiant de l'image |

**Retourne :** `Image|null` - L'image trouvée ou `null`

**Exemple :**
```php
$image = $imageService->findImage(42);
```

### `upload(UploadedFile $file, Model $imageable, ?Model $uploadedBy = null, ImageType $type = ImageType::GALLERY, ?ImageOptionsRecord $options = null): Image`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$file` | `UploadedFile` | Fichier uploadé |
| `$imageable` | `Model` | Modèle parent (relation polymorphique) |
| `$uploadedBy` | `Model|null` | Utilisateur ayant uploadé |
| `$type` | `ImageType` | Type d'image |
| `$options` | `ImageOptionsRecord|null` | Options d'upload |

**Retourne :** `Image` - Image créée

**Exceptions :** `RuntimeException` - Validation du fichier échouée

**Exemple :**
```php
$image = $imageService->upload(
    $request->file('photo'),
    $user,
    auth()->user(),
    ImageType::AVATAR,
    new ImageOptionsRecord(alt_text: 'Photo de profil')
);
```

### `uploadMultiple(array $files, Model $imageable, ?Model $uploadedBy = null, ImageType $type = ImageType::GALLERY, ?ImageOptionsRecord $options = null): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$files` | `array<UploadedFile>` | Tableau de fichiers |
| `$imageable` | `Model` | Modèle parent |
| `$uploadedBy` | `Model|null` | Utilisateur ayant uploadé |
| `$type` | `ImageType` | Type d'image |
| `$options` | `ImageOptionsRecord|null` | Options d'upload |

**Retourne :** `Collection<Image>` - Images créées

**Exemple :**
```php
$images = $imageService->uploadMultiple(
    $request->file('photos'),
    $post,
    auth()->user()
);
```

### `update(ImageRecord $record, int $id): Image`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$record` | `ImageRecord` | Données de mise à jour |
| `$id` | `int` | ID de l'image |

**Retourne :** `Image` - Image mise à jour

**Exemple :**
```php
$image = $imageService->update(
    ImageRecord::from(['alt_text' => 'Nouveau texte']),
    42
);
```

### `delete(int $id, bool $deleteFile = true): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$id` | `int` | ID de l'image |
| `$deleteFile` | `bool` | Supprimer aussi le fichier physique |

**Exceptions :** `RuntimeException` - Image non trouvée

**Exemple :**
```php
$imageService->delete(42);
```

### `deleteMultiple(array $ids, bool $deleteFile = true): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$ids` | `array<int>` | IDs des images |
| `$deleteFile` | `bool` | Supprimer les fichiers physiques |

**Exemple :**
```php
$imageService->deleteMultiple([1, 2, 3]);
```

### `deleteAllForModel(Model $model, bool $deleteFile = true): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | Modèle parent |
| `$deleteFile` | `bool` | Supprimer les fichiers physiques |

**Exemple :**
```php
$imageService->deleteAllForModel($user);
```

### `getImagesForModel(Model $model, ?ImageType $type = null): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | Modèle parent |
| `$type` | `ImageType|null` | Filtrer par type |

**Retourne :** `Collection<Image>` - Images du modèle

**Exemple :**
```php
$avatars = $imageService->getImagesForModel($user, ImageType::AVATAR);
```

### `getPrimaryImage(Model $model): ?Image`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | Modèle parent |

**Retourne :** `Image|null` - Image principale

**Exemple :**
```php
$primary = $imageService->getPrimaryImage($user);
```

### `setAsPrimary(int $id, Model $model): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$id` | `int` | ID de l'image à définir comme principale |
| `$model` | `Model` | Modèle parent |

**Exemple :**
```php
$imageService->setAsPrimary(42, $user);
```

### `countImages(Model $model, ?ImageType $type = null): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | Modèle parent |
| `$type` | `ImageType|null` | Filtrer par type |

**Retourne :** `int` - Nombre d'images

**Exemple :**
```php
$count = $imageService->countImages($user, ImageType::AVATAR);
```

### `getImagesUpdatedAfter(DateTimeVO $date): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$date` | `DateTimeVO` | Date seuil |

**Retourne :** `Collection<Image>` - Images mises à jour après la date

**Exemple :**
```php
$recentImages = $imageService->getImagesUpdatedAfter(
    DateTimeVO::from(now()->subDay())
);
```

### `reorder(array $ids): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$ids` | `array<int>` | IDs des images dans le nouvel ordre |

**Exemple :**
```php
$imageService->reorder([3, 1, 4, 2]);
```

### `getThumbnailUrl(int $imageId, string $size = 'small'): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$imageId` | `int` | ID de l'image |
| `$size` | `string` | Taille de la miniature (small, medium, large) |

**Retourne :** `string` - URL de la miniature

**Exceptions :** `RuntimeException` - Image non trouvée

**Exemple :**
```php
$url = $imageService->getThumbnailUrl(42, 'medium');
```

## Cas d'utilisation

### Cas 1 : Upload d'une image avec options

Upload d'une image avec métadonnées et génération de miniatures.

```php
$image = $imageService->upload(
    $request->file('avatar'),
    $user,
    auth()->user(),
    ImageType::AVATAR,
    new ImageOptionsRecord(
        alt_text: 'Avatar de ' . $user->name,
        caption: 'Photo de profil',
        order: 1,
        is_primary: true,
        generate_thumbnails: true,
    )
);
```

### Cas 2 : Gestion des images d'un modèle

Récupération, comptage et suppression des images d'un modèle.

```php
// Compter les images
$count = $imageService->countImages($post);

// Récupérer les images
$images = $imageService->getImagesForModel($post);

// Supprimer toutes les images
$imageService->deleteAllForModel($post);
```

### Cas 3 : Gestion de l'image principale

Définition et récupération de l'image principale.

```php
// Définir une image comme principale
$imageService->setAsPrimary($imageId, $post);

// Récupérer l'image principale
$primary = $imageService->getPrimaryImage($post);

if ($primary) {
    echo $primary->filename;
}
```

### Cas 4 : Réorganisation des images

Modification de l'ordre des images.

```php
$images = $imageService->getImagesForModel($album);
$ids = $images->pluck('id')->toArray();

// Inverser l'ordre
$imageService->reorder(array_reverse($ids));
```

### Cas 5 : Récupération des miniatures

Affichage des miniatures dans une galerie.

```php
$image = $imageService->findImage(42);

$thumbnails = [
    'small' => $imageService->getThumbnailUrl($image->id, 'small'),
    'medium' => $imageService->getThumbnailUrl($image->id, 'medium'),
    'large' => $imageService->getThumbnailUrl($image->id, 'large'),
];
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Image non trouvée | `RuntimeException` | `Image not found: {id}` |
| Taille de fichier excessive | `RuntimeException` | `File size exceeds limit of {maxSize} KB` |
| Type MIME non autorisé | `RuntimeException` | `MIME type {mimeType} not allowed` |

## Intégration

Le `ImageService` s'intègre avec :

- **ImageRepository** : Accès aux données des images
- **ImageProcessorInterface** : Traitement des images (GD/Imagick)
- **ImageStorageInterface** : Stockage des fichiers
- **ImageOptionsRecord** : Options d'upload
- **ImageFilterRecord** : Filtres de recherche

## Performance

- Les miniatures sont générées en arrière-plan lors de l'upload
- Les opérations de suppression sont optimisées par lots
- Les requêtes de récupération utilisent les index de la base de données
- Le stockage est configurable pour optimiser les performances

**Bonnes pratiques :**
- Utiliser `uploadMultiple()` pour les uploads groupés
- Activer `generate_thumbnails` pour éviter la génération à la volée
- Utiliser `getImagesForModel()` avec le paramètre `$type` pour filtrer
- Appeler `reorder()` pour modifier l'ordre des images en une seule opération

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

use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Records\ImageOptionsRecord;
use AndyDefer\LaravelImages\Services\ImageService;

// 1. Upload d'une image
$image = $imageService->upload(
    $request->file('photo'),
    $post,
    auth()->user(),
    ImageType::GALLERY,
    new ImageOptionsRecord(
        alt_text: 'Photo de l\'article',
        order: 1,
    )
);

echo "Image uploadée : " . $image->filename . "\n";

// 2. Ajouter d'autres images
$images = $imageService->uploadMultiple(
    $request->file('photos'),
    $post,
    auth()->user()
);

echo "Images uploadées : " . $images->count() . "\n";

// 3. Définir l'image principale
$imageService->setAsPrimary($image->id, $post);

// 4. Récupérer l'image principale
$primary = $imageService->getPrimaryImage($post);
echo "Image principale : " . $primary->filename . "\n";

// 5. Récupérer toutes les images
$allImages = $imageService->getImagesForModel($post);

foreach ($allImages as $img) {
    $thumbnail = $imageService->getThumbnailUrl($img->id, 'small');
    echo "- " . $img->filename . " (miniature: " . $thumbnail . ")\n";
}

// 6. Réorganiser les images
$ids = $allImages->pluck('id')->reverse()->toArray();
$imageService->reorder($ids);

// 7. Supprimer une image
$imageService->delete($image->id);
```

## Voir aussi

- `ImageServiceInterface` - Interface du service
- `ImageRepository` - Repository des images
- `ImageProcessorInterface` - Interface du processeur
- `ImageStorageInterface` - Interface du stockage
- `ImageOptionsRecord` - Record des options
- `ImageFilterRecord` - Record des filtres
- `ImageType` - Enum des types d'images