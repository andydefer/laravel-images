# ImageService - Référence Technique

## Description

Orchestre les opérations de gestion des images, incluant l'upload, le stockage, la génération de vignettes et la gestion des relations inverses entre variantes claires/sombres.

## Hiérarchie / Implémentations

```
ImageServiceInterface
    └── ImageService
```

## Rôle principal

Point d'entrée unique pour toutes les opérations liées aux images. Coordonne les interactions entre :
- Le **repository** (`ImageRepository`) pour les opérations base de données
- Le **processeur** (`ImageProcessorInterface`) pour le traitement des images
- Le **stockage** (`ImageStorageInterface`) pour la gestion des fichiers

## API / Méthodes publiques

### `findImage(int $id): ?Image`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$id` | `int` | Identifiant unique de l'image |

**Retourne :** `?Image` - L'instance de l'image trouvée, ou `null` si inexistante

**Exemple :**
```php
$image = $imageService->findImage(42);
if ($image !== null) {
    echo $image->original_filename;
}
```

---

### `upload(UploadedFile $file, Model $imageable, ?Model $uploadedBy = null, ImageType $type = ImageType::GALLERY, ?ImageOptionsRecord $options = null): Image`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$file` | `UploadedFile` | Fichier téléchargé depuis une requête HTTP |
| `$imageable` | `Model` | Modèle parent auquel l'image est attachée |
| `$uploadedBy` | `?Model` | Utilisateur ayant effectué l'upload (optionnel) |
| `$type` | `ImageType` | Type d'image (GALLERY, AVATAR, COVER, BANNER) |
| `$options` | `?ImageOptionsRecord` | Options supplémentaires (alt, caption, ordre, etc.) |

**Retourne :** `Image` - L'instance de l'image créée

**Exceptions :** `RuntimeException` - Validation échouée

**Exemple :**
```php
$file = UploadedFile::fake()->image('avatar.jpg', 400, 400);
$options = new ImageOptionsRecord(
    alt_text: 'Photo de profil',
    is_primary: true,
    order: 1
);

$image = $imageService->upload(
    file: $file,
    imageable: $user,
    uploadedBy: $user,
    type: ImageType::AVATAR,
    options: $options
);
```

---

### `uploadMultiple(array $files, Model $imageable, ?Model $uploadedBy = null, ImageType $type = ImageType::GALLERY, ?ImageOptionsRecord $options = null): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$files` | `array` | Tableau d'objets `UploadedFile` |
| `$imageable` | `Model` | Modèle parent |
| `$uploadedBy` | `?Model` | Utilisateur ayant effectué l'upload |
| `$type` | `ImageType` | Type d'image commun |
| `$options` | `?ImageOptionsRecord` | Options communes à toutes les images |

**Retourne :** `Collection` - Collection des instances d'images créées

**Exemple :**
```php
$files = [
    UploadedFile::fake()->image('photo1.jpg'),
    UploadedFile::fake()->image('photo2.jpg'),
];

$images = $imageService->uploadMultiple(
    files: $files,
    imageable: $album,
    type: ImageType::GALLERY
);

foreach ($images as $image) {
    echo $image->original_filename;
}
```

---

### `update(ImageRecord $record, int $id): Image`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$record` | `ImageRecord` | Enregistrement contenant les données de mise à jour |
| `$id` | `int` | Identifiant de l'image à mettre à jour |

**Retourne :** `Image` - L'instance de l'image mise à jour

**Exceptions :** `ModelNotFoundException` - Image non trouvée

**Exemple :**
```php
$record = ImageRecord::from([
    'is_primary' => true,
    'order' => 1,
]);

$updatedImage = $imageService->update($record, 42);
```

---

### `delete(int $id, bool $deleteFile = true): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$id` | `int` | Identifiant de l'image |
| `$deleteFile` | `bool` | Supprimer également le fichier physique (défaut: `true`) |

**Exceptions :** `RuntimeException` - Image non trouvée

**Exemple :**
```php
$imageService->delete(42); // Supprime l'image et le fichier
$imageService->delete(42, false); // Supprime uniquement l'enregistrement
```

---

### `deleteMultiple(array $ids, bool $deleteFile = true): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$ids` | `array` | Tableau d'identifiants |
| `$deleteFile` | `bool` | Supprimer les fichiers physiques |

**Exemple :**
```php
$imageService->deleteMultiple([42, 43, 44]);
```

---

### `deleteAllForModel(Model $model, bool $deleteFile = true): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | Modèle dont toutes les images doivent être supprimées |
| `$deleteFile` | `bool` | Supprimer les fichiers physiques |

**Exemple :**
```php
$imageService->deleteAllForModel($user);
```

---

### `getImagesForModel(Model $model, ?ImageType $type = null): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | Modèle parent |
| `$type` | `?ImageType` | Filtrer par type d'image (optionnel) |

**Retourne :** `Collection` - Collection des images associées

**Exemple :**
```php
$avatars = $imageService->getImagesForModel($user, ImageType::AVATAR);
$allImages = $imageService->getImagesForModel($user);
```

---

### `getPrimaryImage(Model $model): ?Image`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | Modèle parent |

**Retourne :** `?Image` - L'image principale ou `null`

**Exemple :**
```php
$primary = $imageService->getPrimaryImage($user);
if ($primary !== null) {
    echo $primary->getThumbnailUrl();
}
```

---

### `setAsPrimary(int $id, Model $model): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$id` | `int` | Identifiant de l'image à définir comme principale |
| `$model` | `Model` | Modèle parent |

**Exemple :**
```php
$imageService->setAsPrimary(42, $user);
```

---

### `countImages(Model $model, ?ImageType $type = null): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | Modèle parent |
| `$type` | `?ImageType` | Filtrer par type |

**Retourne :** `int` - Nombre d'images

**Exemple :**
```php
$count = $imageService->countImages($user, ImageType::AVATAR);
```

---

### `getImagesUpdatedAfter(DateTimeVO $date): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$date` | `DateTimeVO` | Date limite |

**Retourne :** `Collection` - Images mises à jour après la date

**Exemple :**
```php
$date = DateTimeVO::from(now()->subDay());
$recentImages = $imageService->getImagesUpdatedAfter($date);
```

---

### `reorder(array $ids): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$ids` | `array` | Tableau d'identifiants dans le nouvel ordre |

**Exemple :**
```php
$imageService->reorder([42, 43, 44]); // 42 devient order=1, 43=2, 44=3
```

---

### `getThumbnailUrl(int $imageId, string $size = 'small'): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$imageId` | `int` | Identifiant de l'image |
| `$size` | `string` | Taille de la vignette ('small', 'medium', 'large') |

**Retourne :** `string` - URL publique de la vignette

**Exceptions :** `RuntimeException` - Image non trouvée

**Exemple :**
```php
$url = $imageService->getThumbnailUrl(42, 'large');
echo $url; // https://example.com/storage/.../image_large.jpg
```

---

### `syncInverseRelation(Image $image): void`

Synchronise la relation inverse entre les variantes claires/sombres.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$image` | `Image` | L'image à synchroniser |

**Exemple :**
```php
// Généralement appelé automatiquement par l'ImageObserver
$imageService->syncInverseRelation($image);
```

---

## Cas d'utilisation

### Cas 1 : Upload d'un avatar utilisateur avec options

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Records\ImageOptionsRecord;

$file = UploadedFile::fake()->image('profile.jpg', 400, 400);
$options = new ImageOptionsRecord(
    alt_text: 'Photo de profil de Jean',
    caption: 'Avatar 2024',
    is_primary: true,
    order: 1,
    generate_thumbnails: true
);

$image = $imageService->upload(
    file: $file,
    imageable: $user,
    uploadedBy: $user,
    type: ImageType::AVATAR,
    options: $options
);

echo "Image uploadée avec l'ID : " . $image->id;
echo "URL de la vignette : " . $imageService->getThumbnailUrl($image->id);
```

### Cas 2 : Upload multiple pour une galerie photo

```php
<?php

declare(strict_types=1);

$files = [
    UploadedFile::fake()->image('vacation1.jpg'),
    UploadedFile::fake()->image('vacation2.jpg'),
    UploadedFile::fake()->image('vacation3.jpg'),
];

$images = $imageService->uploadMultiple(
    files: $files,
    imageable: $album,
    type: ImageType::GALLERY
);

// Les images sont automatiquement ordonnées (1, 2, 3)
foreach ($images as $index => $image) {
    echo "Image " . ($index + 1) . " : " . $image->original_filename;
    echo "Ordre : " . $image->order;
}

// Définir la première image comme principale
$imageService->setAsPrimary($images->first()->id, $album);
```

### Cas 3 : Gestion des variantes claires/sombres (Banner)

```php
<?php

declare(strict_types=1);

// Upload des deux variantes
$darkFile = UploadedFile::fake()->image('banner-dark.jpg', 1200, 400);
$lightFile = UploadedFile::fake()->image('banner-light.jpg', 1200, 400);

$darkImage = $imageService->upload($darkFile, $page, null, ImageType::BANNER);
$lightImage = $imageService->upload($lightFile, $page, null, ImageType::BANNER);

// L'ImageObserver synchronise automatiquement la relation
$darkImage->refresh();
$lightImage->refresh();

echo "Image dark liée à : " . $darkImage->inverse_image_id; // ID de l'image light
echo "Image light liée à : " . $lightImage->inverse_image_id; // ID de l'image dark
```

### Cas 4 : Nettoyage complet des images d'un utilisateur

```php
<?php

declare(strict_types=1);

// Supprimer toutes les images d'un utilisateur
$imageService->deleteAllForModel($user);

// Vérifier le nettoyage
$count = $imageService->countImages($user);
echo "Il reste $count images"; // 0
```

### Cas 5 : Réorganisation d'une galerie

```php
<?php

declare(strict_types=1);

$images = $imageService->getImagesForModel($album);

// Récupérer les IDs dans l'ordre souhaité
$ids = $images->pluck('id')->toArray();
$reorderedIds = array_reverse($ids);

// Appliquer le nouvel ordre
$imageService->reorder($reorderedIds);

// Vérifier
$reorderedImages = $imageService->getImagesForModel($album);
foreach ($reorderedImages as $index => $image) {
    echo "Position " . ($index + 1) . " : " . $image->original_filename;
}
```

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Image non trouvée (delete) | `RuntimeException` | `Image not found: {id}` |
| Image non trouvée (thumbnail) | `RuntimeException` | `Image not found: {imageId}` |
| Taille du fichier trop grande | `RuntimeException` | `File size exceeds limit of {maxSize} KB` |
| MIME type non autorisé | `RuntimeException` | `MIME type {mimeType} not allowed` |

## Intégration

Le `ImageService` travaille en étroite collaboration avec :

- **`ImageRepository`** : Toutes les opérations CRUD utilisent le repository
- **`ImageProcessorInterface`** : Génération des vignettes et transformations
- **`ImageStorageInterface`** : Stockage et suppression des fichiers
- **`ImageObserver`** : Synchronisation automatique des relations inverses
- **`ImageType`** : Enum définissant les types et leurs configurations

## Performance

- **Upload** : O(n) avec n = nombre d'images pour les uploads multiples
- **Recherche** : Indexée sur les colonnes `imageable_type`, `imageable_id`, `type`
- **Génération de vignettes** : Asynchrone par défaut (pas de blocage)
- **Relations inverses** : Une seule requête par synchronisation avec `search` sur `original_filename`

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |
| PHP 7.4 | ❌ Non supporté (nécessite PHP 8.1 pour les types) |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Records\ImageOptionsRecord;
use AndyDefer\LaravelImages\Services\ImageService;
use Illuminate\Http\UploadedFile;

class UserProfileController
{
    public function __construct(
        private readonly ImageService $imageService
    ) {}

    public function updateAvatar(Request $request, User $user): JsonResponse
    {
        $file = $request->file('avatar');

        // Valider le fichier
        if (!$file instanceof UploadedFile) {
            return response()->json(['error' => 'Invalid file'], 422);
        }

        // Supprimer l'ancien avatar
        $oldAvatar = $this->imageService->getPrimaryImage($user);
        if ($oldAvatar !== null) {
            $this->imageService->delete($oldAvatar->id);
        }

        // Upload du nouvel avatar
        $options = new ImageOptionsRecord(
            alt_text: 'Avatar de ' . $user->name,
            is_primary: true,
            order: 1
        );

        $avatar = $this->imageService->upload(
            file: $file,
            imageable: $user,
            uploadedBy: $user,
            type: ImageType::AVATAR,
            options: $options
        );

        return response()->json([
            'message' => 'Avatar mis à jour',
            'id' => $avatar->id,
            'thumbnail' => $this->imageService->getThumbnailUrl($avatar->id, 'small'),
        ]);
    }

    public function uploadGallery(Request $request, Album $album): JsonResponse
    {
        $files = $request->file('images');

        if (!is_array($files)) {
            return response()->json(['error' => 'Invalid files'], 422);
        }

        $options = new ImageOptionsRecord(
            generate_thumbnails: true,
            caption: 'Galerie ajoutée le ' . now()->format('d/m/Y')
        );

        $images = $this->imageService->uploadMultiple(
            files: $files,
            imageable: $album,
            uploadedBy: $request->user(),
            type: ImageType::GALLERY,
            options: $options
        );

        // Marquer la première image comme principale
        if ($images->isNotEmpty()) {
            $this->imageService->setAsPrimary($images->first()->id, $album);
        }

        return response()->json([
            'message' => count($images) . ' images uploadées',
            'images' => $images->map(fn ($img) => [
                'id' => $img->id,
                'filename' => $img->original_filename,
                'url' => $this->imageService->getThumbnailUrl($img->id),
            ]),
        ]);
    }
}
```

## Voir aussi

- `ImageRepository` - Documentation du repository et de ses méthodes
- `ImageType` - Énumération des types d'images disponibles
- `ImageObserver` - Documentation sur la synchronisation automatique
- `ImageOptionsRecord` - Options de configuration pour l'upload
- `ImageProcessorInterface` - Interface pour le traitement d'images