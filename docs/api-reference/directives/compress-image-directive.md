# CompressImagesDirective - Référence Technique

## Description

Directive de compression d'images qui utilise les outils système `pngquant` et `jpegoptim` pour réduire la taille des fichiers PNG, JPG et JPEG. Elle prend en charge la compression récursive, la simulation (dry-run), et plusieurs options de qualité.

## Hiérarchie / Implémentations

```
AbstractDirective
    └── CompressImagesDirective
```

## Rôle principal

Fournit une interface CLI pour compresser des images en masse. La directive orchestre la découverte des fichiers, l'appel aux outils système, et le suivi des statistiques de compression (taille avant/après, économie réalisée).

## API / Méthodes publiques

### `getSignature(): string`

Retourne la signature de la commande avec tous les paramètres et leurs commentaires.

**Retourne :** `string` - La signature complète

**Exemple :**
```php
public function getSignature(): string
{
    return 'images:compress 
                {source}#"Source directory containing images to compress" 
                {destination=?}#"Destination directory (source directory if omitted)" 
                {png-quality=45-50}#"PNG quality range (min-max, e.g. 30-40)" 
                {jpg-quality=50}#"JPEG quality (0-100)" 
                {--strip-meta}#"Remove metadata (Exif, comments, etc.)" 
                {--recursive}#"Process subdirectories recursively" 
                {--dry-run}#"Simulate compression without modifying files" 
                {--force}#"Force overwrite existing files"';
}
```

---

### `getAliases(): StringTypedCollection`

Retourne les alias de la commande.

**Retourne :** `StringTypedCollection` - Collection des alias

**Exemple :**
```php
public function getAliases(): StringTypedCollection
{
    return StringTypedCollection::from(['imc']);
}
```

---

### `getDescription(): string`

Retourne la description de la commande.

**Retourne :** `string` - Description

**Exemple :**
```php
public function getDescription(): string
{
    return 'Compress PNG and JPG/JPEG images using pngquant and jpegoptim';
}
```

---

### `beforeExecute(): void`

Prépare l'exécution : récupère les services, vérifie la source et les dépendances.

**Exceptions :** `RuntimeException` - Source introuvable ou dépendances manquantes

**Exemple :**
```php
protected function beforeExecute(): void
{
    $this->info('📷 Starting image compression...');
    $app = $this->getApplication();
    $this->fileSystem = $app->make(FileSystemInterface::class);
    $this->storage = $app->make(ImageStorageInterface::class);
    // ...
}
```

---

### `execute(): ExitCode`

Exécute la compression des images. C'est la méthode principale de la directive.

**Retourne :** `ExitCode` - Code de sortie (SUCCESS ou RUNTIME_ERROR)

**Exemple :**
```php
protected function execute(): ExitCode
{
    $source = $this->getArgument('source');
    $destination = $this->getArgument('destination') ?? $source;
    // ... logique de compression
    return ExitCode::SUCCESS;
}
```

---

### `afterExecute(ExitCode $exitCode): void`

Nettoyage après l'exécution.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$exitCode` | `ExitCode` | Code de sortie de l'exécution |

**Exemple :**
```php
protected function afterExecute(ExitCode $exitCode): void
{
    $this->newLine();
    $this->info('✅ Compression completed');
}
```

---

## Cas d'utilisation

### Cas 1 : Compression simple

Compression des images dans un dossier avec les paramètres par défaut.

```bash
./bin/images images:compress storage/app/public/images
```

### Cas 2 : Compression avec destination personnalisée

Compression des images vers un dossier de destination spécifique.

```bash
./bin/images images:compress storage/app/public/images storage/app/public/compressed
```

### Cas 3 : Compression récursive avec paramètres avancés

Compression récursive avec qualités personnalisées et suppression des métadonnées.

```bash
./bin/images images:compress storage/app/public/images --recursive --strip-meta --png-quality=30-40 --jpg-quality=40
```

### Cas 4 : Simulation (dry-run)

Vérification des fichiers qui seraient compressés sans effectuer de modifications.

```bash
./bin/images images:compress storage/app/public/images --dry-run
```

### Cas 5 : Utilisation de l'alias

Utilisation de l'alias `imc` pour une exécution plus rapide.

```bash
./bin/images imc storage/app/public/images --recursive
```

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Source introuvable | `RuntimeException` | `Source directory not found: {source}` |
| Outils manquants | `RuntimeException` | `Required tools not installed: pngquant, jpegoptim` |
| Échec de compression | `RuntimeException` | `Error compressing {file}: {error}` |

---

## Intégration

La directive s'intègre avec :

- **FileSystemInterface** : Opérations système de fichiers
- **ImageStorageInterface** : Stockage des images
- **AbstractDirective** : Classe de base des directives
- **Symfony Process** : Exécution des commandes système

**Dépendances système :**
- `pngquant` - Compression PNG
- `jpegoptim` - Compression JPG/JPEG

---

## Performance

- La compression est exécutée via des processus système (non bloquants)
- Les fichiers sont traités séquentiellement pour éviter la surcharge mémoire
- Les statistiques (taille avant/après) sont collectées en temps réel

**Bonnes pratiques :**
- Utiliser `--dry-run` pour évaluer l'impact avant exécution
- Ajuster les qualités selon les besoins (PNG: 30-40, JPG: 40-50 pour le web)
- Utiliser `--strip-meta` pour réduire davantage la taille

---

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.1+ | ✅ Complet |

| Outil système | Support |
|---------------|---------|
| pngquant | ✅ Requis |
| jpegoptim | ✅ Requis |

| Environnement | Support |
|---------------|---------|
| Linux | ✅ Complet |
| macOS | ✅ Complet |
| Windows | ⚠️ Nécessite installation manuelle |

---

## Exemple complet

```bash
#!/bin/bash

# 1. Vérifier que les outils sont installés
which pngquant || sudo apt install pngquant
which jpegoptim || sudo apt install jpegoptim

# 2. Compresser un dossier d'images avec les paramètres optimisés pour le web
./bin/images images:compress \
    storage/app/public/images \
    storage/app/public/compressed \
    --png-quality=30-40 \
    --jpg-quality=40 \
    --strip-meta \
    --recursive

# 3. Sortie attendue :
# 📷 Starting image compression...
# ✅ Source directory: storage/app/public/images
# 📁 Found 42 images to process
#    ✅ images/test/photo1.jpg - saved 120.5 KB (65.2%)
#    ✅ images/test/photo2.png - saved 45.2 KB (52.8%)
#    ...
# 📊 Summary:
#    📁 Files processed: 42
#    📦 Size before: 15.24 MB
#    📦 Size after: 5.67 MB
#    💾 Space saved: 9.57 MB (62.8%)
# ✅ Compression completed
```

---

## Voir aussi

- `AbstractDirective` - Classe de base des directives
- `FileSystemInterface` - Interface système de fichiers
- `ImageStorageInterface` - Interface de stockage des images
- `DirectiveTestingService` - Service de test des directives
- [pngquant documentation](https://pngquant.org/)
- [jpegoptim documentation](https://github.com/tjko/jpegoptim)