# ScanImagesDirective - Référence Technique

## Description

La directive `ScanImagesDirective` permet de scanner récursivement un répertoire pour y trouver des images, d'extraire leurs métadonnées (chemin, nom, taille, dimensions, type MIME, etc.), et d'exporter les résultats sous forme de fichier JSON ou PHP array.

## Hiérarchie

```
AbstractDirective
    └── ScanImagesDirective
```

## Rôle principal

Fournir une interface CLI pour l'analyse et l'inventaire d'images dans un projet Laravel, facilitant ainsi la gestion des assets, l'audit de contenu, et la génération de rapports sur les images présentes dans l'application.

## API / Méthodes publiques

### `getSignature(): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| - | - | Aucun paramètre |

**Retourne :** `string` - La signature de la commande CLI

**Description :** Définit les arguments et options acceptés par la directive.

**Exemple :**
```php
$signature = $directive->getSignature();
// 'images:scan {source}#"Source directory..." ...'
```

---

### `getAliases(): StringTypedCollection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| - | - | Aucun paramètre |

**Retourne :** `StringTypedCollection` - Collection des alias de la commande

**Description :** Retourne les alias disponibles pour la commande (`ims`).

**Exemple :**
```php
$aliases = $directive->getAliases();
// ['ims']
```

---

### `getDescription(): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| - | - | Aucun paramètre |

**Retourne :** `string` - Description de la directive

**Description :** Retourne une brève description de ce que fait la directive.

**Exemple :**
```php
echo $directive->getDescription();
// 'Scan images in a directory and generate JSON/Array output with metadata'
```

---

### `beforeExecute(): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| - | - | Aucun paramètre |

**Retourne :** `void`

**Exceptions :** `RuntimeException` si le répertoire source n'existe pas

**Description :** Méthode appelée avant l'exécution principale. Initialise les services et vérifie que le répertoire source existe.

---

### `execute(): ExitCode`

| Paramètre | Type | Description |
|-----------|------|-------------|
| - | - | Aucun paramètre |

**Retourne :** `ExitCode` - `SUCCESS` ou `RUNTIME_ERROR`

**Description :** Méthode principale d'exécution qui orchestre le scan, la collecte des métadonnées et la génération du fichier de sortie.

**Exemple :**
```bash
./bin/app images:scan images
```

---

### `afterExecute(ExitCode $exitCode): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$exitCode` | `ExitCode` | Code de sortie de l'exécution |

**Retourne :** `void`

**Description :** Méthode appelée après l'exécution principale pour afficher le message de finalisation.

---

## Cas d'utilisation

### Cas 1 : Scan simple avec sortie JSON

```bash
./bin/app images:scan images
```

### Cas 2 : Scan avec profondeur limitée et sortie PHP array

```bash
./bin/app images:scan images 2 array
```

### Cas 3 : Scan avec filtrage d'extensions et exclusion de dossiers

```bash
./bin/app images:scan images 0 json [png,jpg] [compressed,thumbnails]
```

### Cas 4 : Scan avec génération de hash MD5

```bash
./bin/app images:scan images --hash
```

### Cas 5 : Scan en utilisant l'alias

```bash
./bin/app ims images
```

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Répertoire source introuvable | `RuntimeException` | `Source directory not found: {source}` |
| Fichier image illisible | `Exception` | `Error processing: {file} - {message}` |
| Format de sortie invalide | (fallback) | Utilise `json` par défaut |

---

## Intégration

### Dépendances

| Service | Rôle |
|---------|------|
| `FileSystemInterface` | Opérations sur le système de fichiers (lecture, écriture, test d'existence) |
| `ImageStorageInterface` | Résolution des chemins de stockage |
| `ImageExtension` | Enum des extensions d'images supportées |
| `AbstractDirective` | Fonctionnalités de base des directives CLI |

### Workflow

```
1. Utilisateur exécute la commande
   ↓
2. beforeExecute() vérifie le répertoire source
   ↓
3. execute() construit la configuration
   ↓
4. scanImages() collecte les images
   ↓
5. formatOutput() génère le contenu
   ↓
6. saveOutput() écrit le fichier
   ↓
7. afterExecute() affiche la confirmation
```

---

## Performance

- **Complexité :** O(n) où n est le nombre de fichiers dans le répertoire
- **Mémoire :** Les métadonnées sont collectées en mémoire avant l'écriture du fichier
- **Optimisation :** Utilise `RecursiveIteratorIterator` pour un parcours efficace des répertoires
- **Cache :** Aucun cache utilisé (scan direct)

### Recommandations

| Volume d'images | Approche recommandée |
|-----------------|---------------------|
| < 1000 | Scan direct sans préoccupation |
| 1000 - 10000 | Scan acceptable, peut prendre quelques secondes |
| > 10000 | Considérer un traitement par lots ou en arrière-plan |

---

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.2+ | ✅ Complet |
| PHP 8.3+ | ✅ Complet |
| PHP 8.4+ | ✅ Complet |
| PHP 8.5+ | ✅ Complet |

---

## Exemple complet

```bash
# 1. Scan simple
./bin/app images:scan images

# 2. Scan avec tous les paramètres
./bin/app images:scan images 2 json [png,jpg] [compressed,thumbnails] --hash --exclude-compressed

# 3. Scan avec l'alias
./bin/app ims images

# 4. Résultat : un fichier scan_result_YYYY-MM-DD_HH-MM-SS.json ou .php
```

**Sortie générée (JSON) :**
```json
[
  {
    "path": "images/avatars/user1.png",
    "filename": "user1.png",
    "original_filename": "user1.png",
    "extension": "png",
    "mime_type": "image/png",
    "size": 12345,
    "width": 800,
    "height": 600
  }
]
```

**Sortie générée (PHP array) :**
```php
<?php

return [
    [
        'path' => 'images/avatars/user1.png',
        'filename' => 'user1.png',
        'original_filename' => 'user1.png',
        'extension' => 'png',
        'mime_type' => 'image/png',
        'size' => 12345,
        'width' => 800,
        'height' => 600,
    ],
];
```

---

## Voir aussi

- `CompressImagesDirective` - Directive de compression d'images
- `ImageExtension` - Enum des extensions d'images
- `FileSystemInterface` - Interface du système de fichiers