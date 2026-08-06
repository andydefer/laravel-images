# ImageRepository - Référence Technique

## Description

Repository pour la gestion des images dans la base de données. Fournit des méthodes de filtrage avancées et des opérations spécifiques aux images, en s'appuyant sur le pattern Repository.

## Hiérarchie / Implémentations

```
AbstractRepository
    └── ImageRepository
            └── ImageRepositoryInterface
```

## Rôle principal

Encapsule les accès à la base de données pour l'entité `Image`. Il fournit des méthodes de filtrage granulaires (type, taille, extension, ordre, etc.) et des opérations dédiées comme la récupération de l'image principale d'un modèle.

## API / Méthodes publiques

### `applyFilters(Builder $query, AbstractRecord $filters): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$query` | `Builder` | Query Builder Eloquent |
| `$filters` | `AbstractRecord` | Record de filtres (`ImageFilterRecord`) |

**Description :** Applique les filtres à la requête Eloquent. Cette méthode est appelée automatiquement par le repository parent.

### `getPrimaryImageForModel(string $imageableType, string $imageableId): ?Image`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$imageableType` | `string` | Type du modèle parent (morph class) |
| `$imageableId` | `string` | UUID du modèle parent |

**Retourne :** `Image|null` - L'image principale ou `null` si aucune

**Exemple :**
```php
$primaryImage = $repository->getPrimaryImageForModel(
    User::class,
    '550e8400-e29b-41d4-a716-446655440000'
);
```

## Filtres disponibles

| Filtre | Type | Description |
|--------|------|-------------|
| `id` | `string` | UUID spécifique de l'image |
| `ids` | `StringTypedCollection` | Liste d'UUIDs |
| `imageable_type` | `string` | Type du modèle parent |
| `imageable_id` | `string` | UUID du modèle parent |
| `type` | `ImageType` | Type d'image spécifique |
| `types` | `ImageTypeCollection` | Liste de types |
| `min_size` / `max_size` | `int` | Taille du fichier en bytes |
| `extension` | `ImageExtension` | Extension du fichier |
| `mime_type` | `ImageMimeType` | MIME type |
| `updated_at` | `DateTimeVO` | Date de mise à jour |
| `is_primary` | `bool` | Image principale |
| `order` | `int` | Ordre exact |
| `min_order` / `max_order` | `int` | Plage d'ordre |
| `search` | `string` | Recherche textuelle |

## Cas d'utilisation

### Cas 1 : Récupération de l'image principale d'un modèle

Obtient l'image principale associée à un modèle parent.

```php
$primaryImage = $repository->getPrimaryImageForModel(
    'App\Models\Post',
    '550e8400-e29b-41d4-a716-446655440000'
);

if ($primaryImage) {
    echo $primaryImage->filename;
}
```

### Cas 2 : Recherche d'images par type

Récupère toutes les images d'un type spécifique pour un modèle.

```php
use AndyDefer\LaravelImages\Enums\ImageType;

$filter = ImageFilterRecord::from([
    'imageable_type' => 'App\Models\Product',
    'imageable_id' => '550e8400-e29b-41d4-a716-446655440000',
    'type' => ImageType::GALLERY,
]);

$findBy = new FindByRecord(filters: $filter);
$galleryImages = $repository->findBy($findBy);
```

### Cas 3 : Filtrage par taille et extension

Recherche des images avec des critères de taille et d'extension.

```php
use AndyDefer\LaravelUtils\Enums\ImageExtension;

$filter = ImageFilterRecord::from([
    'extension' => ImageExtension::WEBP,
    'min_size' => 1024 * 100, // 100 KB
    'max_size' => 1024 * 1024 * 5, // 5 MB
]);

$findBy = new FindByRecord(filters: $filter);
$images = $repository->findBy($findBy);
```

### Cas 4 : Récupération des images principales de plusieurs modèles

Utilisation pour récupérer l'image principale pour chaque modèle d'une collection.

```php
$userIds = [
    '550e8400-e29b-41d4-a716-446655440000',
    '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
    '6ba7b811-9dad-11d1-80b4-00c04fd430c8'
];

$primaryImages = [];

foreach ($userIds as $userId) {
    $primaryImages[$userId] = $repository->getPrimaryImageForModel(
        User::class,
        $userId
    );
}
```

### Cas 5 : Recherche textuelle sur les noms de fichiers

Recherche des images par nom de fichier ou nom original.

```php
$filter = ImageFilterRecord::from([
    'search' => 'vacation-2024',
    'is_primary' => true,
]);

$findBy = new FindByRecord(filters: $filter);
$results = $repository->findBy($findBy);
```

### Cas 6 : Filtrage par ordre

Récupère les images dans une plage d'ordre spécifique.

```php
$filter = ImageFilterRecord::from([
    'imageable_type' => 'App\Models\Album',
    'imageable_id' => '550e8400-e29b-41d4-a716-446655440000',
    'min_order' => 1,
    'max_order' => 10,
]);

$findBy = new FindByRecord(
    filters: $filter,
    sortBy: new SortColumns('order:asc'),
);

$orderedImages = $repository->findBy($findBy);
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Image non trouvée | `ModelNotFoundException` | `Image not found for ID {id}` |
| Filtre invalide | `InvalidArgumentException` | `Filter must be instance of ImageFilterRecord` |

## Intégration

Le `ImageRepository` s'intègre avec :

- **ImageRepositoryInterface** : Interface du repository
- **ImageFilterRecord** : Record de filtres
- **ImageRecord** : Record de données
- **Image** : Modèle Eloquent
- **AbstractRepository** : Repository parent

**Utilisation dans un service :**

```php
use AndyDefer\LaravelImages\Repositories\ImageRepository;

final class ImageService
{
    public function __construct(
        private readonly ImageRepository $imageRepository,
    ) {}

    public function getPrimaryImage(string $modelId): ?Image
    {
        return $this->imageRepository->getPrimaryImageForModel(
            'App\Models\Post',
            $modelId
        );
    }
}
```

## Performance

- Les filtres sont appliqués au niveau de la base de données
- Les index sur les colonnes `imageable_type`, `imageable_id`, `type` et `is_primary` optimisent les requêtes
- La recherche par `ids` utilise `WHERE IN` pour une récupération groupée
- La recherche textuelle utilise `LIKE` avec index sur `filename` et `original_filename`

**Bonnes pratiques :**
- Utiliser les index appropriés sur les colonnes fréquemment filtrées
- Limiter les résultats avec `limit` pour `getPrimaryImageForModel`
- Préférer les filtres `ids` pour des recherches précises
- Éviter la recherche textuelle sur de grands jeux de données sans index

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

use AndyDefer\LaravelUtils\Enums\ImageExtension;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Records\ImageFilterRecord;
use AndyDefer\LaravelImages\Repositories\ImageRepository;
use AndyDefer\Repository\Records\FindByRecord;
use AndyDefer\Repository\ValueObjects\SortColumns;
use Illuminate\Support\Str;

// 1. Créer le repository
$repository = new ImageRepository();

// 2. Créer un UUID pour l'utilisateur
$userId = (string) Str::uuid();

// 3. Récupérer l'image principale d'un utilisateur
$primary = $repository->getPrimaryImageForModel(
    'App\Models\User',
    $userId
);

if ($primary) {
    echo "Photo de profil : " . $primary->filename . "\n";
}

// 4. Rechercher les images d'un type spécifique
$productId = (string) Str::uuid();

$filter = ImageFilterRecord::from([
    'imageable_type' => 'App\Models\Product',
    'imageable_id' => $productId,
    'type' => ImageType::COVER,
]);

$findBy = new FindByRecord(
    filters: $filter,
    limit: 5,
    sortBy: new SortColumns('created_at:desc'),
);

$coverImages = $repository->findBy($findBy);

foreach ($coverImages as $image) {
    echo $image->filename . " (" . $image->extension . ")\n";
}

// 5. Rechercher des images WebP récentes
$filter = ImageFilterRecord::from([
    'extension' => ImageExtension::WEBP,
    'min_size' => 1024 * 200, // 200 KB minimum
]);

$findBy = new FindByRecord(
    filters: $filter,
    sortBy: new SortColumns('created_at:desc'),
    limit: 20,
);

$webpImages = $repository->findBy($findBy);

// 6. Recherche textuelle
$filter = ImageFilterRecord::from([
    'search' => 'profile',
    'is_primary' => true,
]);

$findBy = new FindByRecord(filters: $filter);
$profileImages = $repository->findBy($findBy);

// 7. Récupération des images d'un album dans l'ordre
$albumId = (string) Str::uuid();

$filter = ImageFilterRecord::from([
    'imageable_type' => 'App\Models\Album',
    'imageable_id' => $albumId,
    'min_order' => 1,
    'max_order' => 20,
]);

$findBy = new FindByRecord(
    filters: $filter,
    sortBy: new SortColumns('order:asc'),
);

$albumImages = $repository->findBy($findBy);
```

## Voir aussi

- `AbstractRepository` - Repository parent
- `ImageFilterRecord` - Record de filtres
- `ImageRecord` - Record de données
- `Image` - Modèle Eloquent
- `ImageType` - Enum des types d'images
- `ImageExtension` - Enum des extensions
- `FindByRecord` - Record de recherche
---