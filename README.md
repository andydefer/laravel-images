# Laravel Likes

> Système de réactions polymorphiques pour applications Laravel

Un package Laravel complet pour gérer des réactions polymorphiques (likes, loves, haha, wow, sad, angry) avec le pattern Repository, des DTOs, des Value Objects et un système de toggle intelligent.

---

## 📋 Table des matières

- [Fonctionnalités](#fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
  - [Toggle une réaction](#toggle-une-réaction)
  - [Ajouter un like](#ajouter-un-like)
  - [Supprimer un like](#supprimer-un-like)
  - [Vérifier une réaction](#vérifier-une-réaction)
  - [Compter les réactions](#compter-les-réactions)
  - [Récupérer les réactions](#récupérer-les-réactions)
  - [Filtrer par date](#filtrer-par-date)
- [Types de réactions](#types-de-réactions)
- [Référence de l'API](#référence-de-lapi)
- [Value Objects](#value-objects)
- [Structure de la base de données](#structure-de-la-base-de-données)
- [Tests](#tests)
- [Contribuer](#contribuer)
- [Licence](#licence)

---

## ✨ Fonctionnalités

- ✅ **Double polymorphisme** - Réagissez à n'importe quel modèle avec n'importe quel utilisateur
- ✅ **6 types de réactions** - LIKE, LOVE, HAHA, WOW, SAD, ANGRY avec emojis
- ✅ **Toggle intelligent** - Changez de réaction en un seul appel
- ✅ **Filtrage temporel** - Récupérez les réactions après une date donnée
- ✅ **Pattern Repository** - Séparation propre de la logique d'accès aux données
- ✅ **Support des DTOs** - Objets de transfert de données typés
- ✅ **Value Objects** - DateTime, Métadonnées
- ✅ **Support des métadonnées** - Stockez des données supplémentaires au format JSON
- ✅ **Suppression douce** - Suppression sécurisée avec possibilité de restauration
- ✅ **Filtrage avancé** - Filtrez par type, par auteur, par objet
- ✅ **Tests complets** - Couverture complète des tests d'intégration

---

## 🚀 Prérequis

- PHP 8.2 ou supérieur
- Laravel 12.0, 13.0, 14.0 ou 15.0

---

## 📦 Installation

Installez le package via Composer :

```bash
composer require andydefer/laravel-likes
```

### Publier les migrations

```bash
php artisan vendor:publish --tag=Likes-migrations
```

### Exécuter les migrations

```bash
php artisan migrate
```

---

## ⚙️ Configuration

Le package est automatiquement découvert par Laravel. Aucune configuration supplémentaire n'est requise.

Si vous devez personnaliser le Service Provider, ajoutez-le manuellement dans `config/app.php` :

```php
'providers' => [
    // ...
    AndyDefer\LaravelImages\LikesServiceProvider::class,
],
```

---

## 📖 Utilisation

### Toggle une réaction

La méthode `toggle()` est la plus polyvalente. Elle permet de :
- Ajouter une réaction si elle n'existe pas
- Changer de type de réaction si elle existe déjà
- Supprimer la réaction si le même type est utilisé

```php
use AndyDefer\LaravelImages\Services\LikeService;
use AndyDefer\LaravelImages\Enums\LikeType;

class PostController extends Controller
{
    public function react(LikeService $likeService, Post $post)
    {
        $user = auth()->user();

        // Toggle un like (👍)
        $liked = $likeService->toggle($user, $post, LikeType::LIKE);

        // Toggle un love (❤️)
        $loved = $likeService->toggle($user, $post, LikeType::LOVE);

        // Toggle un haha (😂)
        $haha = $likeService->toggle($user, $post, LikeType::HAHA);

        return response()->json([
            'reacted' => $liked,
            'type' => $liked ? LikeType::LIKE->value : null,
            'emoji' => $liked ? LikeType::LIKE->getEmoji() : null,
        ]);
    }
}
```

### Ajouter un like

```php
// Ajoute un like (👍) - Lève une exception si déjà liké
$likeService->like($user, $post);
```

### Supprimer un like

```php
// Supprime un like - Lève une exception si non liké
$likeService->unlike($user, $post);
```

### Vérifier une réaction

```php
// Vérifier si l'utilisateur a réagi
$hasLiked = $likeService->hasLiked($user, $post);
```

### Compter les réactions

```php
// Compter toutes les réactions d'un objet
$total = $likeService->countLikes($post);

// Compter par type
$likes = $likeService->countLikesByType($post, LikeType::LIKE);
$loves = $likeService->countLikesByType($post, LikeType::LOVE);
$hahas = $likeService->countLikesByType($post, LikeType::HAHA);
```

### Récupérer les réactions

```php
// Récupérer tous les likeurs d'un objet
$likers = $likeService->getLikers($post);

// Récupérer les likeurs par type
$likersByType = $likeService->getLikersByType($post, LikeType::LOVE);

// Récupérer toutes les réactions d'un utilisateur
$userLikes = $likeService->getLikerLikes($user);

// Récupérer les réactions d'un utilisateur par type
$userLoves = $likeService->getLikerLikesByType($user, LikeType::LOVE);
```

### Filtrer par date

```php
use AndyDefer\PhpVo\ValueObjects\DateTimeVO;

$date = DateTimeVO::from('2024-01-01 00:00:00');

// Récupérer toutes les réactions mises à jour après une date
$recentLikes = $likeService->getLikesUpdatedAfter($date);

// Récupérer les réactions d'un utilisateur après une date
$userRecentLikes = $likeService->getLikerLikesUpdatedAfter($user, $date);

// Récupérer les réactions d'un objet après une date
$postRecentLikes = $likeService->getLikesForLikeableUpdatedAfter($post, $date);
```

---

## 🏷️ Types de réactions

| Type | Valeur | Emoji | Label |
|------|--------|-------|-------|
| `LikeType::LIKE` | `'like'` | 👍 | J'aime |
| `LikeType::LOVE` | `'love'` | ❤️ | J'adore |
| `LikeType::HAHA` | `'haha'` | 😂 | Haha |
| `LikeType::WOW` | `'wow'` | 😮 | Wow |
| `LikeType::SAD` | `'sad'` | 😢 | Triste |
| `LikeType::ANGRY` | `'angry'` | 😡 | En colère |

### Utilisation des émojis

```php
use AndyDefer\LaravelImages\Enums\LikeType;

$type = LikeType::LOVE;
echo $type->getEmoji();  // ❤️
echo $type->getLabel();  // J'adore
```

---

## 📚 Référence de l'API

### LikeService

| Méthode | Description | Retourne |
|---------|-------------|----------|
| `toggle(Model $liker, Model $likeable, LikeType $type = LikeType::LIKE)` | Toggle une réaction (ajoute/change/supprime) | `bool` |
| `like(Model $liker, Model $likeable)` | Ajoute un like (👍) | `void` |
| `unlike(Model $liker, Model $likeable)` | Supprime un like | `void` |
| `hasLiked(Model $liker, Model $likeable)` | Vérifie si l'utilisateur a réagi | `bool` |
| `countLikes(Model $likeable)` | Compte toutes les réactions | `int` |
| `countLikesByType(Model $likeable, LikeType $type)` | Compte les réactions par type | `int` |
| `getLikers(Model $likeable)` | Récupère tous les likeurs | `Collection` |
| `getLikersByType(Model $likeable, LikeType $type)` | Récupère les likeurs par type | `Collection` |
| `getLikerLikes(Model $liker)` | Récupère les réactions d'un utilisateur | `Collection` |
| `getLikerLikesByType(Model $liker, LikeType $type)` | Récupère les réactions d'un utilisateur par type | `Collection` |
| `getLikesUpdatedAfter(DateTimeVO $date)` | Récupère les réactions après une date | `Collection` |
| `getLikerLikesUpdatedAfter(Model $liker, DateTimeVO $date)` | Récupère les réactions d'un utilisateur après une date | `Collection` |
| `getLikesForLikeableUpdatedAfter(Model $likeable, DateTimeVO $date)` | Récupère les réactions d'un objet après une date | `Collection` |

---

## 🎯 Value Objects

Le package supporte les Value Objects suivants :

| Value Object | Description | Exemple |
|--------------|-------------|---------|
| `DateTimeVO` | Date/heure | `DateTimeVO::from('2024-01-01 12:00:00')` |
| `StrictDataObject` | Métadonnées typées | `StrictDataObject::from(['key' => 'value'])` |

### Accesseurs dans le modèle Like

```php
$like = Like::find(1);

// Accès sous forme de Value Objects
$createdAt = $like->getCreatedAt();    // DateTimeVO
$updatedAt = $like->getUpdatedAt();    // DateTimeVO
$deletedAt = $like->getDeletedAt();    // DateTimeVO
$metadata = $like->getMetadata();      // StrictDataObject
$type = $like->getType();              // LikeType

// Relations
$liker = $like->liker;          // Auteur (User, Admin, etc.)
$likeable = $like->likeable;    // Objet liké (Post, Article, etc.)
```

---

## 📝 Structure de la base de données

```sql
CREATE TABLE likes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    liker_type VARCHAR(255) NOT NULL,    -- Type de l'auteur
    liker_id BIGINT UNSIGNED NOT NULL,   -- ID de l'auteur
    likeable_type VARCHAR(255) NOT NULL, -- Type de l'objet liké
    likeable_id BIGINT UNSIGNED NOT NULL,-- ID de l'objet liké
    type VARCHAR(20) DEFAULT 'like',     -- like, love, haha, wow, sad, angry
    metadata JSON NULL,                  -- Métadonnées
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE INDEX idx_unique_like (liker_type, liker_id, likeable_type, likeable_id),
    INDEX idx_liker (liker_type, liker_id),
    INDEX idx_likeable (likeable_type, likeable_id),
    INDEX idx_type (type),
    INDEX idx_updated_at (updated_at)
);
```

---

## 🔍 Exemple complet

```php
use AndyDefer\LaravelImages\Services\LikeService;
use AndyDefer\LaravelImages\Enums\LikeType;

class PostController extends Controller
{
    public function __construct(
        private readonly LikeService $likeService
    ) {}

    public function react(Request $request, Post $post)
    {
        $user = $request->user();
        $type = LikeType::tryFrom($request->input('type', 'like'));

        if (!$type) {
            return response()->json(['error' => 'Type de réaction invalide'], 400);
        }

        $reacted = $this->likeService->toggle($user, $post, $type);

        return response()->json([
            'reacted' => $reacted,
            'type' => $reacted ? $type->value : null,
            'emoji' => $reacted ? $type->getEmoji() : null,
            'label' => $reacted ? $type->getLabel() : null,
        ]);
    }

    public function stats(Post $post)
    {
        $types = LikeType::cases();
        $reactions = [];

        foreach ($types as $type) {
            $reactions[$type->value] = [
                'count' => $this->likeService->countLikesByType($post, $type),
                'emoji' => $type->getEmoji(),
                'label' => $type->getLabel(),
            ];
        }

        return response()->json([
            'total' => $this->likeService->countLikes($post),
            'reactions' => $reactions,
        ]);
    }

    public function likers(Post $post)
    {
        $likers = $this->likeService->getLikers($post);

        return response()->json($likers);
    }

    public function myReactions(Request $request)
    {
        $user = $request->user();
        $type = LikeType::tryFrom($request->input('type'));

        if ($type) {
            $reactions = $this->likeService->getLikerLikesByType($user, $type);
        } else {
            $reactions = $this->likeService->getLikerLikes($user);
        }

        return response()->json($reactions);
    }
}
```

---

## 🧪 Tests

### Exécuter les tests

```bash
composer test
```

### Exécuter uniquement les tests unitaires

```bash
composer test-unit
```

### Exécuter uniquement les tests d'intégration

```bash
composer test-integration
```

### Configuration des tests

Le package utilise `orchestra/testbench` pour les tests d'intégration avec une base de données SQLite en mémoire.

---

## 🔧 Développement

### Style de code

```bash
./vendor/bin/pint
```

### Analyse statique

```bash
./vendor/bin/phpstan analyse
./vendor/bin/psalm
```

---

## 📄 Journal des modifications

Veuillez consulter le [CHANGELOG](CHANGELOG.md) pour plus d'informations sur les modifications récentes.

---

## 🤝 Contribuer

Veuillez consulter [CONTRIBUTING](CONTRIBUTING.md) pour plus de détails.

### Flux de développement

1. Forkez le dépôt
2. Créez une branche de fonctionnalité (`git checkout -b feature/amazing-feature`)
3. Apportez vos modifications
4. Exécutez les tests (`composer test`)
5. Committez vos modifications (`git commit -m 'Ajouter une fonctionnalité géniale'`)
6. Poussez vers la branche (`git push origin feature/amazing-feature`)
7. Ouvrez une Pull Request

---

## 📦 Dépendances

- [`andydefer/php-vo`](https://github.com/andydefer/php-vo) - Value Objects
- [`andydefer/laravel-repository`](https://github.com/andydefer/laravel-repository) - Implémentation du pattern Repository
- [`andydefer/domain-structures`](https://github.com/andydefer/domain-structures) - Structures de domaine (AbstractRecord, AbstractData)

---

## 👨‍💻 Auteur

**Andy Kani**
- GitHub: [@andydefer](https://github.com/andydefer)
- Email: andykanidimbu@gmail.com

---



## ⭐ Support

Si vous trouvez ce package utile, n'hésitez pas à lui donner une ⭐ sur GitHub !

---

## 🙏 Remerciements

- Framework Laravel
- Tous les contributeurs et utilisateurs de ce package

---

**Construit avec ❤️ pour la communauté Laravel**