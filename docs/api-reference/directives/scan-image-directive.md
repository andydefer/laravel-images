# ScanImagesDirective - Référence Technique

## Description

La directive `ScanImagesDirective` scanne récursivement un répertoire pour y trouver des images, extrait leurs métadonnées (chemin, nom, taille, dimensions, type MIME, etc.) et exporte les résultats dans un fichier JSON ou PHP.

## Hiérarchie / Implémentations

```
AbstractDirective
    └── ScanImagesDirective
```

**Interfaces :** `DirectiveInterface` (via `AbstractDirective`)

## Rôle principal

Cette directive sert à générer un inventaire structuré des images présentes dans un dossier, en conservant la hiérarchie des répertoires. Elle est utilisée notamment pour préparer les données avant un seeding d'images en base de données.

## DETAILS

[Voir la classe ScanImagesDirective](https://github.com/andydefer/laravel-images/blob/main/src/Directives/ScanImagesDirective.php)

## API / Méthodes publiques

### `getSignature(): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `string` - La signature de la commande CLI

**Exemple :**
```php
$directive = new ScanImagesDirective();
echo $directive->getSignature();
// images:scan {source} {output} {depth=0} {extensions*} {excludes*} {--hash} {--exclude-compressed}
```

---

### `getAliases(): StringTypedCollection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `StringTypedCollection` - Collection contenant les alias de la commande

**Exemple :**
```php
$aliases = $directive->getAliases(); // ['ims']
```

---

### `getDescription(): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `string` - Description de la directive

**Exemple :**
```php
echo $directive->getDescription();
// "Scan images in a directory and generate JSON/Array output with metadata"
```

---

### `beforeExecute(): void`

Méthode d'initialisation appelée avant l'exécution principale.

- Initialise les services (`FileSystemInterface`)
- Vérifie l'existence du répertoire source

**Exceptions :** `RuntimeException` - Si le répertoire source n'existe pas

---

### `execute(): ExitCode`

Point d'entrée principal de la directive.

1. Construit la configuration à partir des arguments CLI
2. Scanne le répertoire source pour trouver les images
3. Applique les filtres (extensions, exclusion, profondeur)
4. Formate la sortie (JSON ou PHP array)
5. Sauvegarde le fichier de sortie

**Retourne :** `ExitCode::SUCCESS` ou `ExitCode::FAILURE`

**Exceptions :** `RuntimeException` - En cas d'erreur lors du scan ou de la sauvegarde

---

### `afterExecute(ExitCode $exitCode): void`

Méthode appelée après l'exécution, affiche un message de confirmation.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$exitCode` | `ExitCode` | Code de sortie de l'exécution |

---

## Cas d'utilisation

### Cas 1 : Scan simple avec sortie JSON

```bash
./bin/afya images:scan storage/app/public/images scan-result.json
```

**Résultat :** Crée `scan-result.json` avec la liste des images trouvées

---

### Cas 2 : Scan avec profondeur limitée

```bash
./bin/afya images:scan storage/app/public/images scan-depth.json 2
```

Seulement les images dans les 2 premiers niveaux de sous-dossiers sont incluses.

---

### Cas 3 : Filtrer par extensions

```bash
./bin/afya images:scan storage/app/public/images scan-png.json 0 png
```

Seulement les images PNG sont incluses.

---

### Cas 4 : Exclure des dossiers

```bash
./bin/afya images:scan storage/app/public/images scan-exclude.json 0 [] [compressed,thumbnails]
```

Exclut les dossiers `compressed` et `thumbnails` du scan.

---

### Cas 5 : Générer un fichier PHP array

```bash
./bin/afya images:scan storage/app/public/images scan-result.php
```

**Résultat :** Crée `scan-result.php` contenant un tableau PHP.

---

### Cas 6 : Scan avec hash MD5

```bash
./bin/afya images:scan storage/app/public/images scan-hash.json --hash
```

Ajoute le hash MD5 de chaque image dans les métadonnées.

---

## Structure du fichier de sortie

### JSON
```json
[
    {
        "path": "storage/app/public/images/avatars/patient.jpg",
        "filename": "patient.jpg",
        "original_filename": "patient.jpg",
        "extension": "jpg",
        "mime_type": "image/jpeg",
        "size": 48597,
        "width": 800,
        "height": 600,
        "hash": "a1b2c3d4e5f6..."
    }
]
```

### PHP
```php
<?php

return [
    [
        'path' => 'storage/app/public/images/avatars/patient.jpg',
        'filename' => 'patient.jpg',
        'original_filename' => 'patient.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'size' => 48597,
        'width' => 800,
        'height' => 600,
        'hash' => 'a1b2c3d4e5f6...'
    ]
];
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Répertoire source inexistant | `RuntimeException` | `Source directory not found: {$source}` |
| Extension de fichier non supportée | `RuntimeException` | `Unsupported file format: .{$extension}. Please use .json or .php` |
| Erreur lors du traitement d'une image | Exception capturée | `⚠️ Error processing: {$file} - {$message}` |

## Intégration

La directive utilise :
- `FileSystemInterface` pour toutes les opérations sur le système de fichiers
- `ImageExtension` enum pour les extensions supportées
- `AbstractDirective` pour l'infrastructure CLI

## Performance

- **Complexité** : O(n) où n est le nombre d'images
- **Mémoire** : Stocke toutes les métadonnées en mémoire avant l'écriture
- **Optimisation** : Pour de très grands volumes (> 10000 images), envisager un streaming

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |

## Exemple complet

```bash
# Scan complet avec toutes les options
./bin/afya images:scan storage/app/public/images scan-full.json 1 jpg png [compressed] --hash

# Utilisation avec l'alias
./bin/afya ims storage/app/public/images scan-alias.json
```

```php
// Exemple de code PHP pour charger le résultat
$images = json_decode(file_get_contents('scan-result.json'), true);
foreach ($images as $image) {
    echo "Image: {$image['filename']} ({$image['width']}x{$image['height']})\n";
}
```

## Voir aussi

- `CompressImagesDirective` - Compression des images
- `SeedImagesDirective` - Seeding des images en base de données
- `ImageExtension` - Types d'extensions supportées
- `FileSystemInterface` - Interface pour les opérations système

---