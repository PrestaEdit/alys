# Import Preview — Spec

**Date :** 2026-05-10
**Scope :** Ajouter une étape de prévisualisation/confirmation entre la lecture du fichier .alys et l'import réel, avec diff profil par profil et sélection granulaire traitement par traitement.

---

## Contexte

Actuellement, `Import.php` lit le fichier, le déchiffre et appelle immédiatement `ImportService::restore()`. L'utilisateur n'a aucun contrôle sur ce qui est importé et ne voit pas ce qui va changer.

---

## Flux utilisateur

```
idle → picking → previewing → importing → success / error
```

Le seul nouvel état est **previewing**. Il s'insère entre la lecture du fichier et l'exécution de l'import.

1. L'utilisateur sélectionne un fichier `.alys`.
2. Le fichier est déchiffré et le diff calculé (sans écrire en BDD).
3. L'écran de prévisualisation s'affiche avec profils, traitements classifiés et avant/après pour les modifiés.
4. L'utilisateur coche/décoche profils et traitements, puis confirme.
5. Seuls les éléments sélectionnés sont importés.

---

## Architecture

### Nouveaux fichiers

| Fichier | Rôle |
|---|---|
| `app/Services/ImportPreviewService.php` | Calcule le diff sans écrire en BDD |
| `tests/Unit/Services/ImportPreviewServiceTest.php` | Tests unitaires du service de diff |

### Fichiers modifiés

| Fichier | Changement |
|---|---|
| `app/Livewire/Import.php` | Nouveaux états et propriétés, nouvelles méthodes |
| `app/Services/ImportService.php` | `restore()` accepte un filtre de sélection optionnel |
| `resources/views/livewire/import.blade.php` | Nouvel état `previewing` dans la vue |
| `tests/Feature/ImportServiceTest.php` | Adaptation pour le filtre de sélection |
| `tests/Feature/Livewire/ImportTest.php` | Nouveaux cas + adaptation des existants |

---

## ImportPreviewService

Responsabilité unique : comparer les données d'un fichier .alys avec l'état actuel de la BDD et retourner un tableau structuré. **N'écrit rien en BDD.**

### Signature

```php
public function preview(array $data): array
```

### Structure retournée

```php
[
  [
    'old_id'     => 4,              // id du profil dans le fichier .alys
    'name'       => 'Alys',
    'color'      => '#0ea5e9',
    'status'     => 'existing',     // 'new' | 'existing'
    'treatments' => [
      [
        'name'        => 'Methotrexate',
        'status'      => 'modified',  // 'new' | 'modified' | 'unchanged'
        'incoming'    => [...],       // données du fichier .alys
        'current'     => [...],       // données en BDD (null si 'new')
        'diff_fields' => ['current_dose'],  // champs qui diffèrent
      ],
    ],
    'local_only' => [               // traitements en BDD absents du fichier
      ['name' => 'Voltarène'],
    ],
  ],
]
```

### Champs comparés pour détecter "modifié"

`commercial_name`, `type`, `unit`, `current_dose`, `dose_morning`, `dose_noon`, `dose_evening`, `color`, `frequency_weeks`, `day_of_week`, `is_medical_act`, `requires_fasting`, `archived_at`

Les champs `notes`, `show_widget`, `widget_icon` sont importés silencieusement mais n'entrent pas dans le calcul du diff visuel.

### Rétro-compatibilité

Si le fichier .alys ne contient pas de section `profiles` (ancien format), le service produit un profil virtuel unique contenant tous les traitements du fichier, tous comparés au profil actif en BDD.

---

## Import.php — Nouvelles propriétés

```php
public bool  $previewing          = false;
public array $previewData         = [];
public array $selectedProfiles    = [];   // old_ids des profils cochés
public array $selectedTreatments  = [];   // clés "old_profile_id:treatment_name"

private string $pendingContent    = '';   // JSON déchiffré, non exposé à la vue
```

### Nouvelles méthodes

| Méthode | Rôle |
|---|---|
| `doPreview(string $content)` | Déchiffre, calcule le diff, initialise les sélections (tout coché par défaut), passe en état `previewing` |
| `toggleProfile(int $oldId)` | Coche/décoche un profil et tous ses traitements |
| `toggleTreatment(string $key)` | Coche/décoche un traitement individuel |
| `confirmImport()` | Appelle `ImportService::restore()` avec le filtre de sélection, passe en état `importing` puis `success` |
| `cancelPreview()` | Efface `$pendingContent` et `$previewData`, revient à `idle` |

### Modification de `doImport()`

L'actuelle méthode `doImport()` est renommée `doPreview()`. Elle ne déclenche plus l'import immédiatement.

---

## ImportService — Signature modifiée

```php
public function restore(
    string  $alysContent,
    string  $keyBase64,
    ?array  $selectedTreatments = null  // null = tout importer, [] = rien importer
): void
```

Quand `$selectedTreatments` est un tableau (y compris vide) :
- Un traitement dont la clé `"old_profile_id:name"` n'est pas dans `$selectedTreatments` est ignoré, ainsi que son historique et ses événements.
- Un profil n'est créé que si au moins un de ses traitements est dans `$selectedTreatments` (la création de profil est dérivée de la sélection de traitements, pas d'un paramètre séparé).

`$selectedProfiles` reste une propriété Livewire pour le toggle-all UX mais n'est pas passé au service.

---

## Vue import.blade.php

Nouvel état `@elseif($previewing)` entre `@elseif($importing)` et le bloc du bouton de sélection.

### Structure de l'état previewing

```
Résumé (date export, nb profils, nb traitements)
↓
Pour chaque profil :
  Checkbox profil + nom + badge (existant | NOUVEAU PROFIL)
  Pour chaque traitement :
    Checkbox + nom + badge (NOUVEAU | MODIFIÉ | IDENTIQUE)
    Si MODIFIÉ → grille avant/après avec diff_fields en amber
  <details> Traitements non présents dans la sauvegarde (local_only)
↓
Bouton "Importer la sélection (N traitements)"
Bouton "Annuler"
```

Le bouton affiche dynamiquement le compte de traitements dont la clé est dans `$selectedTreatments`.

---

## Tests

### ImportPreviewServiceTest (Unit, nouveau)

- Traitement absent de la BDD → `status === 'new'`
- Traitement identique en BDD → `status === 'unchanged'`, `diff_fields === []`
- Dose différente → `status === 'modified'`, `diff_fields` contient `current_dose`
- Traitement en BDD absent du fichier → apparaît dans `local_only`
- Profil absent de la BDD → `profile status === 'new'`
- Fichier sans section `profiles` (ancien format) → profil virtuel, traitements classifiés

### ImportServiceTest (Feature, existants adaptés)

- Sélection vide → rien n'est écrit en BDD
- Un seul traitement sélectionné → seul ce traitement + son historique sont importés
- Profil non sélectionné → profil non créé en BDD
- Sélection complète (comportement actuel) → tous les tests existants continuent de passer

### ImportTest Livewire (Feature, existants adaptés + nouveaux)

- `FileChosen` valide → `$previewing === true`, `$previewData` non vide
- Clic "Annuler" depuis previewing → retour à `idle`, `$pendingContent` effacé
- Clic "Importer la sélection" → `$success === true`, `import-complete` dispatché
- Test existant "imports successfully via FileChosen" → adapté pour passer par `confirmImport()` après preview
