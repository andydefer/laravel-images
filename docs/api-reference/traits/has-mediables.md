# HasMediables - Référence Technique

## Description

Le trait `HasMediables` fournit des attributs calculés pour les modèles Eloquent qui peuvent avoir des images et des albums. Il utilise des requêtes directes sans avoir besoin de définir des relations dans le modèle.

## Rôle principal

Permet à n'importe quel modèle Eloquent d'accéder à ses images et albums via des attributs simples (ex: `$user->avatar`, `$user->public_albums`) sans avoir à définir des relations `morphMany`. Le trait exécute des requêtes directes sur les tables `images` et `albums` en utilisant les colonnes polymorphiques (`imageable_type` / `imageable_id` et `albumable_type` / `albumable_id`).

## API / Attributs

### Attributs d'images

#### `has_images: bool`

Indique si le modèle possède au moins une image.

```php
if ($user->has_images) {
    echo "L'utilisateur a des images";
}
```

#### `images_count: int`

Retourne le nombre total d'images associées au modèle.

```php
echo "L'utilisateur a {$user->images_count} images";
```

#### `primary_image: Image|null`

Retourne l'image marquée comme principale (`is_primary = true`).

```php
$primary = $user->primary_image;
if ($primary) {
    echo $primary->full_url;
}
```

#### `avatar: Image|null`

Retourne la première image de type `AVATAR`.

```php
$avatar = $user->avatar;
if ($avatar) {
    echo $avatar->full_url;
}
```

#### `cover: Image|null`

Retourne la première image de type `COVER`.

```php
$cover = $user->cover;
```

#### `banner: Image|null`

Retourne la première image de type `BANNER`.

```php
$banner = $user->banner;
```

#### `logo: Image|null`

Retourne la première image de type `LOGO`.

```php
$logo = $user->logo;
```

#### `icon: Image|null`

Retourne la première image de type `ICON`.

```php
$icon = $user->icon;
```

#### `gallery_images: Collection<int, Image>`

Retourne toutes les images de type `GALLERY`, triées par ordre croissant.

```php
foreach ($user->gallery_images as $image) {
    echo $image->full_url;
}
```

### Attributs d'albums

#### `has_albums: bool`

Indique si le modèle possède au moins un album.

```php
if ($user->has_albums) {
    echo "L'utilisateur a des albums";
}
```

#### `albums_count: int`

Retourne le nombre total d'albums associés au modèle.

```php
echo "L'utilisateur a {$user->albums_count} albums";
```

#### `primary_album: Album|null`

Retourne le premier album créé (par ordre de date).

```php
$album = $user->primary_album;
if ($album) {
    echo $album->name;
}
```

#### `featured_album: Album|null`

Retourne l'album marqué comme mis en avant (`is_featured = BinaryChoice::YES`).

```php
$featured = $user->featured_album;
```

#### `public_albums: Collection<int, Album>`

Retourne tous les albums publics (`is_public = BinaryChoice::YES`), triés par date de création décroissante.

```php
foreach ($user->public_albums as $album) {
    echo $album->name;
}
```

#### `private_albums: Collection<int, Album>`

Retourne tous les albums privés (`is_public = BinaryChoice::NO`), triés par date de création décroissante.

```php
foreach ($user->private_albums as $album) {
    echo $album->name;
}
```

## Cas d'utilisation

### Cas 1 : Affichage du profil utilisateur

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;

class ProfileController extends Controller
{
    public function show(User $user)
    {
        return view('profile', [
            'user' => $user,
            'avatar' => $user->avatar,
            'banner' => $user->banner,
            'gallery' => $user->gallery_images,
            'albums' => $user->public_albums,
        ]);
    }
}
```

### Cas 2 : Dashboard d'administration

```php
<?php

namespace App\Services;

use App\Models\Hospital;

class HospitalDashboardService
{
    public function getStats(Hospital $hospital): array
    {
        return [
            'total_images' => $hospital->images_count,
            'has_logo' => $hospital->logo !== null,
            'has_banner' => $hospital->banner !== null,
            'albums_count' => $hospital->albums_count,
            'featured_album' => $hospital->featured_album,
            'gallery' => $hospital->gallery_images,
        ];
    }
}
```

### Cas 3 : API REST

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function show(Post $post): JsonResponse
    {
        return response()->json([
            'id' => $post->id,
            'title' => $post->title,
            'cover' => $post->cover?->full_url,
            'images' => $post->gallery_images->map(fn($img) => $img->full_url),
            'albums' => $post->public_albums->map(fn($album) => [
                'id' => $album->id,
                'name' => $album->name,
                'cover' => $album->coverImage?->full_url,
            ]),
        ]);
    }
}
```

## Intégration

### Installation dans un modèle

```php
<?php

namespace App\Models;

use AndyDefer\LaravelImages\Traits\HasMediables;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasMediables;

    // Aucune relation nécessaire !
    // Les attributs sont disponibles directement :
    // $user->has_images, $user->avatar, $user->public_albums, etc.
}
```

### Avec des relations existantes

```php
<?php

namespace App\Models;

use AndyDefer\LaravelImages\Models\Image;
use AndyDefer\LaravelImages\Traits\HasMediables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Post extends Model
{
    use HasMediables;

    // Vous pouvez aussi définir les relations normalement
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
```

## Performance

| Attribut | Requêtes SQL | Complexité |
|----------|--------------|------------|
| `has_images` | 1 COUNT | O(1) |
| `images_count` | 1 COUNT | O(1) |
| `primary_image` | 1 SELECT | O(1) |
| `avatar`, `cover`, etc. | 1 SELECT chacun | O(1) |
| `gallery_images` | 1 SELECT | O(n) |
| `has_albums` | 1 COUNT | O(1) |
| `albums_count` | 1 COUNT | O(1) |
| `primary_album` | 1 SELECT | O(1) |
| `featured_album` | 1 SELECT | O(1) |
| `public_albums` / `private_albums` | 1 SELECT chacun | O(n) |

> **Note :** Chaque attribut exécute sa propre requête. Pour les collections de modèles, utilisez le chargement eager avec les relations définies.

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.2+ | ✅ Complet |
| PHP 8.3+ | ✅ Complet |
| PHP 8.4+ | ✅ Complet |
| Laravel 10.x | ✅ Complet |
| Laravel 11.x | ✅ Complet |
| Laravel 12.x | ✅ Complet |

## Exemple complet

```php
<?php

declare(strict_types=1);

use App\Models\User;
use AndyDefer\LaravelImages\Enums\ImageType;
use AndyDefer\LaravelImages\Models\Image;

// Créer un utilisateur
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Ajouter des images
Image::factory()
    ->avatar()
    ->for($user, 'imageable')
    ->create();

Image::factory()
    ->banner()
    ->for($user, 'imageable')
    ->create();

Image::factory()
    ->count(3)
    ->gallery()
    ->for($user, 'imageable')
    ->create();

// Utiliser les attributs
echo "L'utilisateur a des images ? " . ($user->has_images ? 'Oui' : 'Non') . "\n";
echo "Nombre d'images : " . $user->images_count . "\n";

$avatar = $user->avatar;
if ($avatar) {
    echo "Avatar : " . $avatar->full_url . "\n";
}

$banner = $user->banner;
if ($banner) {
    echo "Bannière : " . $banner->full_url . "\n";
}

echo "Images de la galerie :\n";
foreach ($user->gallery_images as $image) {
    echo "  - " . $image->full_url . "\n";
}

// Créer un album
$album = Album::factory()
    ->withAlbumable($user)
    ->withName('Mes photos')
    ->create();

echo "L'utilisateur a des albums ? " . ($user->has_albums ? 'Oui' : 'Non') . "\n";
echo "Nombre d'albums : " . $user->albums_count . "\n";

$firstAlbum = $user->primary_album;
if ($firstAlbum) {
    echo "Premier album : " . $firstAlbum->name . "\n";
}
```

## Voir aussi

- `Image` - Modèle d'image
- `Album` - Modèle d'album
- `ImageType` - Enum des types d'images
- `BinaryChoice` - Enum pour les choix binaires (YES/NO)