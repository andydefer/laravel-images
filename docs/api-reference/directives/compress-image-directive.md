# CompressImagesDirective - Référence Technique

## Description

La directive `CompressImagesDirective` compresse les images PNG et JPG/JPEG en utilisant les outils système `pngquant` et `jpegoptim`. Elle préserve la structure des répertoires et peut traiter récursivement les sous-dossiers.

## Hiérarchie / Implémentations

```
AbstractDirective
    └── CompressImagesDirective
```

**Interfaces :** `DirectiveInterface` (via `AbstractDirective`)

## Rôle principal

Optimiser la taille des images en appliquant une compression sans perte ou avec perte contrôlée, tout en conservant l'architecture des dossiers source dans le répertoire de destination.

## DETAILS

[Voir la classe CompressImagesDirective](https://github.com/andydefer/laravel-images/blob/main/src/Directives/CompressImagesDirective.php)

## Prérequis système

Les outils suivants doivent être installés sur le système :

```bash
sudo apt install pngquant jpegoptim
```

## API / Méthodes publiques

### `getSignature(): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `string` - La signature de la commande CLI

**Exemple :**
```php
$directive = new CompressImagesDirective();
echo $directive->getSignature();
// images:compress {source} {destination} {png-quality=45-50} {jpg-quality=50} {max-size=0} {--strip-meta} {--recursive} {--dry-run} {--force} {--skip-compressed}
```

---

### `getAliases(): StringTypedCollection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `StringTypedCollection` - Collection contenant les alias de la commande

**Exemple :**
```php
$aliases = $directive->getAliases(); // ['imc']
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
// "Compress PNG and JPG/JPEG images using pngquant and jpegoptim"
```

---

### `beforeExecute(): void`

Méthode d'initialisation appelée avant l'exécution principale.

- Initialise les services (`FileSystemInterface`)
- Vérifie l'existence du répertoire source
- Vérifie la présence des outils système

**Exceptions :** `RuntimeException` - Si le répertoire source n'existe pas ou si les outils sont manquants

---

### `execute(): ExitCode`

Point d'entrée principal de la directive.

1. Construit la configuration à partir des arguments CLI
2. Scanne le répertoire source pour trouver les images
3. Applique les filtres (taille, compression déjà effectuée)
4. Exécute la compression (sauf en mode dry-run)
5. Affiche un résumé des opérations

**Retourne :** `ExitCode::SUCCESS` ou `ExitCode::FAILURE`

**Exceptions :** `RuntimeException` - En cas d'erreur lors de la compression

---

### `afterExecute(ExitCode $exitCode): void`

Méthode appelée après l'exécution, affiche un message de confirmation.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$exitCode` | `ExitCode` | Code de sortie de l'exécution |

---

## Cas d'utilisation

### Cas 1 : Compression simple

```bash
./bin/afya images:compress storage/app/public/images storage/app/public/images/compressed
```

Compresse toutes les images trouvées dans `images` et les sauvegarde dans `compressed` (sans structure de dossiers).

---

### Cas 2 : Compression récursive avec conservation de la structure

```bash
./bin/afya images:compress storage/app/public/images storage/app/public/images/compressed --recursive
```

Préserve l'architecture des sous-dossiers. Exemple :
- `images/avatars/patient.jpg` → `compressed/avatars/patient.jpg`
- `images/banners/hero.png` → `compressed/banners/hero.png`

---

### Cas 3 : Simulation (dry-run)

```bash
./bin/afya images:compress storage/app/public/images storage/app/public/images/compressed --recursive --dry-run
```

Affiche les fichiers qui seraient compressés sans effectuer de modification.

---

### Cas 4 : Compression avec qualités personnalisées

```bash
# PNG avec qualité 30-40
./bin/afya images:compress storage/app/public/images storage/app/public/images/compressed --png-quality=30-40

# JPG avec qualité 40
./bin/afya images:compress storage/app/public/images storage/app/public/images/compressed --jpg-quality=40
```

---

### Cas 5 : Ignorer les images déjà compressées

```bash
./bin/afya images:compress storage/app/public/images storage/app/public/images/compressed --recursive --skip-compressed
```

Évite de recompresser les images déjà optimisées.

---

### Cas 6 : Combinaison de flags

```bash
./bin/afya images:compress storage/app/public/images storage/app/public/images/compressed --recursive --strip-meta --force --skip-compressed
```

- `--recursive` : Traite les sous-dossiers
- `--strip-meta` : Supprime les métadonnées (Exif)
- `--force` : Force l'écrasement
- `--skip-compressed` : Ignore les images déjà compressées

---

### Cas 7 : Ignorer les images de petite taille

```bash
./bin/afya images:compress storage/app/public/images storage/app/public/images/compressed --recursive max-size=20
```

Ignore les images de moins de 20 KB (ne les compresse pas).

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Répertoire source inexistant | `RuntimeException` | `Source directory not found: {$source}` |
| Outils non installés | `RuntimeException` | `Missing dependencies: pngquant, jpegoptim` |
| Échec de compression PNG | Avertissement | `⚠️ Error compressing {$source}: {$error}` |
| Échec de compression JPG | Avertissement | `⚠️ Error compressing {$source}: {$error}` |

## Intégration

La directive utilise :
- `FileSystemInterface` pour toutes les opérations sur le système de fichiers
- `Process` de Symfony pour exécuter les commandes système
- `AbstractDirective` pour l'infrastructure CLI

## Performance

- **Complexité** : O(n) où n est le nombre d'images
- **Facteurs impactant** :
  - Taille des images
  - Qualité demandée (plus basse = plus rapide)
  - Nombre de fichiers
- **Optimisation** : Le flag `--skip-compressed` évite de retraiter les images déjà optimisées

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |

## Exemple complet

```bash
# Compression complète de toutes les images avec structure
./bin/afya images:compress storage/app/public/images storage/app/public/images/compressed --recursive --strip-meta --force

# Utilisation avec l'alias
./bin/afya imc storage/app/public/images storage/app/public/images/compressed --recursive
```

## Voir aussi

- `ScanImagesDirective` - Scan des images
- `SeedImagesDirective` - Seeding des images en base de données
- `ImageExtension` - Types d'extensions supportées
- `FileSystemInterface` - Interface pour les opérations système
---