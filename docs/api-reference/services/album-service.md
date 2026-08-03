# AlbumService - Référence Technique

## Description

Service de gestion des albums. Fournit une couche métier pour la création, la manipulation et la gestion des albums et de leurs images associées.

## Hiérarchie / Implémentations

```
AlbumServiceInterface
    └── AlbumService
```

## Rôle principal

Encapsule la logique métier liée aux albums. Il orchestre les opérations entre le repository `AlbumRepository` et le `ImageService` pour gérer les albums, leurs images, les couvertures, le réordonnancement et la duplication.

## API / Méthodes publiques

### `createAlbum(Model $albumable, string $name, ?AlbumOptionsRecord $options = null): Album`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$albumable` | `Model` | Modèle parent (relation polymorphique) |
| `$name` | `string` | Nom de l'album |
| `$options` | `AlbumOptionsRecord|null` | Options de création (description, visibilité, etc.) |

**Retourne :** `Album` - Album créé

**Exemple :**
```php
$album = $albumService->createAlbum($user, 'Mes photos', new AlbumOptionsRecord(
    description: 'Photos de vacances',
    is_public: BinaryChoice::YES,
));
```

### `addImagesToAlbum(Album $album, array $imageIds): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$album` | `Album` | Album cible |
| `$imageIds` | `array<string>` | UUIDs des images à ajouter |

**Exemple :**
```php
$albumService->addImagesToAlbum($album, [
    '550e8400-e29b-41d4-a716-446655440000',
    '6ba7b810-9dad-11d1-80b4-00c04fd430c8'
]);
```

### `addImageToAlbum(Album $album, string $imageId, int $order = 0): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$album` | `Album` | Album cible |
| `$imageId` | `string` | UUID de l'image à ajouter |
| `$order` | `int` | Position (0 = ajout à la fin) |

**Exemple :**
```php
$albumService->addImageToAlbum($album, '550e8400-e29b-41d4-a716-446655440000', 3);
```

### `removeImageFromAlbum(Album $album, string $imageId): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$album` | `Album` | Album source |
| `$imageId` | `string` | UUID de l'image à retirer |

**Exemple :**
```php
$albumService->removeImageFromAlbum($album, '550e8400-e29b-41d4-a716-446655440000');
```

### `removeAllImagesFromAlbum(Album $album): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$album` | `Album` | Album à vider |

**Exemple :**
```php
$albumService->removeAllImagesFromAlbum($album);
```

### `setCoverImage(Album $album, string $imageId): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$album` | `Album` | Album à mettre à jour |
| `$imageId` | `string` | UUID de l'image de couverture |

**Exemple :**
```php
$albumService->setCoverImage($album, '550e8400-e29b-41d4-a716-446655440000');
```

### `getAlbumImages(Album $album): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$album` | `Album` | Album source |

**Retourne :** `Collection<Image>` - Images de l'album

**Exemple :**
```php
$images = $albumService->getAlbumImages($album);
```

### `getAlbumsForModel(Model $model, bool $onlyPublic = true): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | Modèle parent |
| `$onlyPublic` | `bool` | Filtrer les albums publics |

**Retourne :** `Collection<Album>` - Albums du modèle

**Exemple :**
```php
$albums = $albumService->getAlbumsForModel($user);
```

### `getAlbumBySlug(string|SlugVO $slug): ?Album`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$slug` | `string|SlugVO` | Slug de l'album |

**Retourne :** `Album|null` - Album trouvé

**Exemple :**
```php
$album = $albumService->getAlbumBySlug('mes-photos-2024');
```

### `updateAlbum(string $id, AlbumOptionsRecord $options): Album`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$id` | `string` | UUID de l'album |
| `$options` | `AlbumOptionsRecord` | Nouvelles options |

**Retourne :** `Album` - Album mis à jour

**Exceptions :** `RuntimeException` - Album non trouvé

**Exemple :**
```php
$album = $albumService->updateAlbum(
    '550e8400-e29b-41d4-a716-446655440000',
    new AlbumOptionsRecord(
        name: 'Nouveau nom',
        is_public: BinaryChoice::NO,
    )
);
```

### `deleteAlbum(string $id, bool $deleteImages = false): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$id` | `string` | UUID de l'album |
| `$deleteImages` | `bool` | Supprimer aussi les images |

**Exceptions :** `RuntimeException` - Album non trouvé

**Exemple :**
```php
$albumService->deleteAlbum('550e8400-e29b-41d4-a716-446655440000', deleteImages: true);
```

### `reorderAlbumImages(Album $album, array $imageIds): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$album` | `Album` | Album à réorganiser |
| `$imageIds` | `array<string>` | Nouvel ordre des UUIDs |

**Exemple :**
```php
$albumService->reorderAlbumImages($album, [
    '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
    '550e8400-e29b-41d4-a716-446655440000',
    '6ba7b811-9dad-11d1-80b4-00c04fd430c8'
]);
```

### `duplicateAlbum(Album $album, string $newName): Album`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$album` | `Album` | Album à dupliquer |
| `$newName` | `string` | Nom du nouvel album |

**Retourne :** `Album` - Album dupliqué

**Exemple :**
```php
$duplicate = $albumService->duplicateAlbum($album, 'Copie - Mes photos');
```

### `countAlbumImages(Album $album): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$album` | `Album` | Album à compter |

**Retourne :** `int` - Nombre d'images

**Exemple :**
```php
$count = $albumService->countAlbumImages($album);
```

### `isAlbumEmpty(Album $album): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$album` | `Album` | Album à vérifier |

**Retourne :** `bool` - `true` si l'album est vide

**Exemple :**
```php
if ($albumService->isAlbumEmpty($album)) {
    // L'album est vide
}
```

### `getAlbumCoverImage(Album $album): ?Image`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$album` | `Album` | Album source |

**Retourne :** `Image|null` - Image de couverture

**Exemple :**
```php
$cover = $albumService->getAlbumCoverImage($album);
```

### `getFeaturedAlbums(int $limit = 10): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$limit` | `int` | Nombre max d'albums |

**Retourne :** `Collection<Album>` - Albums mis en avant

**Exemple :**
```php
$featured = $albumService->getFeaturedAlbums(5);
```

## Cas d'utilisation

### Cas 1 : Création d'un album avec options

Création d'un album public avec description.

```php
$album = $albumService->createAlbum(
    $user,
    'Album de vacances',
    AlbumOptionsRecord::from([
        'description' => 'Photos de mon voyage en Italie',
        'is_public' => BinaryChoice::YES,
        'is_featured' => BinaryChoice::NO,
    ])
);
```

### Cas 2 : Gestion des images d'un album

Ajout, retrait et réorganisation des images.

```php
$album = $albumService->getAlbumBySlug('vacances-italie');

// Ajouter plusieurs images
$albumService->addImagesToAlbum($album, [
    '550e8400-e29b-41d4-a716-446655440000',
    '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
    '6ba7b811-9dad-11d1-80b4-00c04fd430c8'
]);

// Ajouter une image avec une position spécifique
$albumService->addImageToAlbum($album, '6ba7b812-9dad-11d1-80b4-00c04fd430c8', 2);

// Réorganiser les images
$albumService->reorderAlbumImages($album, [
    '6ba7b811-9dad-11d1-80b4-00c04fd430c8',
    '550e8400-e29b-41d4-a716-446655440000',
    '6ba7b812-9dad-11d1-80b4-00c04fd430c8',
    '6ba7b810-9dad-11d1-80b4-00c04fd430c8'
]);

// Retirer une image
$albumService->removeImageFromAlbum($album, '6ba7b810-9dad-11d1-80b4-00c04fd430c8');
```

### Cas 3 : Configuration de la couverture

Définition de l'image de couverture d'un album.

```php
$images = $albumService->getAlbumImages($album);
if ($images->isNotEmpty()) {
    // Définir la première image comme couverture
    $albumService->setCoverImage($album, $images->first()->id);
}

$cover = $albumService->getAlbumCoverImage($album);
```

### Cas 4 : Récupération des albums d'un modèle

Obtention des albums associés à un utilisateur.

```php
// Albums publics
$publicAlbums = $albumService->getAlbumsForModel($user, onlyPublic: true);

// Tous les albums (y compris privés)
$allAlbums = $albumService->getAlbumsForModel($user, onlyPublic: false);
```

### Cas 5 : Duplication d'un album

Création d'une copie d'un album existant.

```php
$original = $albumService->getAlbumBySlug('album-special');
$copy = $albumService->duplicateAlbum($original, 'Nouvel album spécial');

echo "Album original: " . $original->name . "\n";
echo "Album copié: " . $copy->name . "\n";
```

### Cas 6 : Albums mis en avant

Récupération des albums publics mis en avant.

```php
$featuredAlbums = $albumService->getFeaturedAlbums(5);

foreach ($featuredAlbums as $album) {
    echo $album->name . " (Créé le " . $album->created_at->format('d/m/Y') . ")\n";
}
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Album non trouvé | `RuntimeException` | `Album not found: {id}` |

## Intégration

Le `AlbumService` s'intègre avec :

- **AlbumRepository** : Accès aux données des albums
- **ImageService** : Gestion des images
- **AlbumOptionsRecord** : Options de création/mise à jour
- **AlbumFilterRecord** : Filtres de recherche

## Performance

- Les opérations utilisent les relations Eloquent avec chargement différé
- `load('images')` recharge les relations après les modifications
- Les méthodes de réorganisation utilisent des requêtes SQL groupées
- La duplication utilise des transactions implicites

**Bonnes pratiques :**
- Utiliser `$album->load('images')` pour éviter les requêtes N+1
- Grouper les ajouts d'images avec `addImagesToAlbum()`
- Préférer `getAlbumCoverImage()` à un accès direct aux relations

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

use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use AndyDefer\LaravelImages\Records\AlbumOptionsRecord;
use AndyDefer\LaravelImages\Services\AlbumService;
use Illuminate\Support\Str;

// 1. Créer un album
$album = $albumService->createAlbum(
    $user,
    'Mon album de vacances',
    AlbumOptionsRecord::from([
        'description' => 'Photos de mon voyage à la plage',
        'is_public' => BinaryChoice::YES,
        'metadata' => [
            'year' => 2024,
            'season' => 'summer',
        ],
    ])
);

echo "Album créé : " . $album->name . "\n";
echo "Slug : " . $album->slug->getValue() . "\n";

// 2. Ajouter des images
$imageIds = [
    (string) Str::uuid(),
    (string) Str::uuid(),
    (string) Str::uuid(),
    (string) Str::uuid(),
    (string) Str::uuid(),
    (string) Str::uuid(),
];
$albumService->addImagesToAlbum($album, $imageIds);

// 3. Définir la couverture
$albumService->setCoverImage($album, $imageIds[0]);

// 4. Vérifier le contenu
$count = $albumService->countAlbumImages($album);
echo "Nombre d'images : " . $count . "\n";

if (!$albumService->isAlbumEmpty($album)) {
    $cover = $albumService->getAlbumCoverImage($album);
    echo "Couverture : " . $cover->filename . "\n";
}

// 5. Mettre à jour l'album
$updated = $albumService->updateAlbum(
    $album->id,
    AlbumOptionsRecord::from([
        'name' => 'Nouveau nom pour l\'album',
        'is_featured' => BinaryChoice::YES,
    ])
);

// 6. Récupérer les albums mis en avant
$featured = $albumService->getFeaturedAlbums(5);

foreach ($featured as $item) {
    echo "- " . $item->name . "\n";
}
```

## Voir aussi

- `AlbumServiceInterface` - Interface du service
- `AlbumRepository` - Repository des albums
- `ImageService` - Service des images
- `AlbumOptionsRecord` - Record des options
- `AlbumFilterRecord` - Record des filtres
- `BinaryChoice` - Enum pour les choix binaires
---