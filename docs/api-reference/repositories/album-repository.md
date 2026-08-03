# AlbumRepository - Référence Technique

## Description

Repository pour la gestion des albums dans la base de données. Fournit des méthodes de filtrage et de mise à jour spécifiques aux albums, en s'appuyant sur le pattern Repository.

## Hiérarchie / Implémentations

```
AbstractRepository
    └── AlbumRepository
            └── AlbumRepositoryInterface
```

## Rôle principal

Encapsule les accès à la base de données pour l'entité `Album`. Il fournit des méthodes de filtrage avancées et des opérations de mise à jour dédiées, tout en maintenant une séparation claire entre la logique métier et l'accès aux données.

## API / Méthodes publiques

### `applyFilters(Builder $query, AbstractRecord $filters): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$query` | `Builder` | Query Builder Eloquent |
| `$filters` | `AbstractRecord` | Record de filtres (`AlbumFilterRecord`) |

**Description :** Applique les filtres à la requête Eloquent. Cette méthode est appelée automatiquement par le repository parent.

### `setPublic(string $id, BinaryChoice $isPublic): Album`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$id` | `string` | UUID de l'album |
| `$isPublic` | `BinaryChoice` | Statut public (`YES` ou `NO`) |

**Retourne :** `Album` - Album mis à jour

**Exemple :**
```php
use AndyDefer\LaravelCluster\Enums\BinaryChoice;

$album = $repository->setPublic('550e8400-e29b-41d4-a716-446655440000', BinaryChoice::YES);
```

### `setFeatured(string $id, BinaryChoice $isFeatured): Album`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$id` | `string` | UUID de l'album |
| `$isFeatured` | `BinaryChoice` | Statut mis en avant (`YES` ou `NO`) |

**Retourne :** `Album` - Album mis à jour

**Exemple :**
```php
use AndyDefer\LaravelCluster\Enums\BinaryChoice;

$album = $repository->setFeatured('550e8400-e29b-41d4-a716-446655440000', BinaryChoice::YES);
```

## Filtres disponibles

| Filtre | Type | Description |
|--------|------|-------------|
| `albumable_type` | `string` | Type du modèle parent (polymorphique) |
| `albumable_id` | `string` | UUID du modèle parent |
| `is_public` | `BinaryChoice` | Statut public |
| `is_featured` | `BinaryChoice` | Statut mis en avant |
| `ids` | `StringTypedCollection` | Liste d'UUIDs spécifiques |
| `slug` | `SlugVO` | Slug exact de l'album |
| `search` | `string` | Recherche textuelle (nom ou description) |

## Cas d'utilisation

### Cas 1 : Recherche d'albums par modèle parent

Récupère tous les albums associés à un utilisateur.

```php
$userId = '550e8400-e29b-41d4-a716-446655440000';

$filter = AlbumFilterRecord::from([
    'albumable_type' => User::class,
    'albumable_id' => $userId,
]);

$findBy = new FindByRecord(filters: $filter);
$albums = $repository->findBy($findBy);
```

### Cas 2 : Albums publics et mis en avant

Récupère les albums publics et mis en avant.

```php
$filter = AlbumFilterRecord::from([
    'is_public' => BinaryChoice::YES,
    'is_featured' => BinaryChoice::YES,
]);

$findBy = new FindByRecord(
    filters: $filter,
    limit: 10,
    sortBy: new SortColumns('created_at:desc'),
);

$featuredAlbums = $repository->findBy($findBy);
```

### Cas 3 : Recherche textuelle

Recherche des albums par nom ou description.

```php
$filter = AlbumFilterRecord::from([
    'search' => 'vacances',
    'is_public' => BinaryChoice::YES,
]);

$findBy = new FindByRecord(filters: $filter);
$albums = $repository->findBy($findBy);
```

### Cas 4 : Mise à jour du statut public

Active ou désactive la visibilité d'un album.

```php
use AndyDefer\LaravelCluster\Enums\BinaryChoice;

$albumId = '550e8400-e29b-41d4-a716-446655440000';

// Rendre l'album public
$album = $repository->setPublic($albumId, BinaryChoice::YES);

// Rendre l'album privé
$album = $repository->setPublic($albumId, BinaryChoice::NO);
```

### Cas 5 : Mise à jour du statut "mis en avant"

Active ou désactive la mise en avant d'un album.

```php
use AndyDefer\LaravelCluster\Enums\BinaryChoice;

$albumId = '550e8400-e29b-41d4-a716-446655440000';

// Mettre en avant
$album = $repository->setFeatured($albumId, BinaryChoice::YES);

// Retirer de la mise en avant
$album = $repository->setFeatured($albumId, BinaryChoice::NO);
```

### Cas 6 : Récupération par slug

Trouve un album par son slug unique.

```php
use AndyDefer\PhpVo\ValueObjects\SlugVO;

$slug = new SlugVO('mes-photos-de-vacances-2024');
$filter = AlbumFilterRecord::from(['slug' => $slug]);
$findBy = new FindByRecord(filters: $filter, limit: 1);

$album = $repository->findBy($findBy)->first();
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Album non trouvé | `ModelNotFoundException` | `Album not found for ID {id}` |
| Filtre invalide | `InvalidArgumentException` | `Filter must be instance of AlbumFilterRecord` |

## Intégration

Le `AlbumRepository` s'intègre avec :

- **AlbumRepositoryInterface** : Interface du repository
- **AlbumFilterRecord** : Record de filtres
- **AlbumRecord** : Record de données
- **Album** : Modèle Eloquent
- **AbstractRepository** : Repository parent

**Utilisation dans un service :**

```php
use AndyDefer\LaravelImages\Repositories\AlbumRepository;

final class AlbumService
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
    ) {}

    public function makeAlbumPublic(string $id): Album
    {
        return $this->albumRepository->setPublic($id, BinaryChoice::YES);
    }
}
```

## Performance

- Les filtres sont appliqués au niveau de la base de données (requêtes indexées)
- Les index sur `albumable_type` et `albumable_id` optimisent les requêtes polymorphiques
- Les filtres `ids` utilisent `WHERE IN` pour une recherche efficace
- La recherche textuelle (`search`) utilise `LIKE` avec index sur `name` et `description`

**Bonnes pratiques :**
- Utiliser les index appropriés sur les colonnes fréquemment filtrées
- Limiter les résultats avec `limit` ou `paginate` pour les grands jeux de données
- Préférer les filtres `ids` pour des recherches précises

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
use AndyDefer\LaravelImages\Records\AlbumFilterRecord;
use AndyDefer\LaravelImages\Repositories\AlbumRepository;
use AndyDefer\PhpVo\ValueObjects\SlugVO;
use AndyDefer\Repository\Records\FindByRecord;
use AndyDefer\Repository\ValueObjects\SortColumns;
use Illuminate\Support\Str;

// 1. Créer le repository
$repository = new AlbumRepository();

// 2. Créer un UUID pour l'utilisateur
$userId = (string) Str::uuid();

// 3. Rechercher les albums publics d'un utilisateur
$filter = AlbumFilterRecord::from([
    'albumable_type' => 'App\Models\User',
    'albumable_id' => $userId,
    'is_public' => BinaryChoice::YES,
]);

$findBy = new FindByRecord(
    filters: $filter,
    sortBy: new SortColumns('created_at:desc'),
    limit: 20,
);

$albums = $repository->findBy($findBy);

foreach ($albums as $album) {
    echo $album->name . " (" . $album->slug->getValue() . ")\n";
}

// 4. Mettre à jour le statut d'un album
$albumId = $albums->first()->id;

// Rendre l'album mis en avant
$updatedAlbum = $repository->setFeatured($albumId, BinaryChoice::YES);

// Rendre l'album public
$updatedAlbum = $repository->setPublic($albumId, BinaryChoice::YES);

// 5. Recherche par slug
$slug = new SlugVO('mon-album-special');
$filter = AlbumFilterRecord::from(['slug' => $slug]);
$findBy = new FindByRecord(filters: $filter, limit: 1);

$album = $repository->findBy($findBy)->first();

if ($album) {
    echo "Album trouvé : " . $album->name . "\n";
}
```

## Voir aussi

- `AbstractRepository` - Repository parent
- `AlbumFilterRecord` - Record de filtres
- `AlbumRecord` - Record de données
- `Album` - Modèle Eloquent
- `BinaryChoice` - Enum pour les choix binaires (YES/NO)
- `FindByRecord` - Record de recherche
---