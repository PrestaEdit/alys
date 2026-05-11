# Treatment Creation Wizard — Design Spec

**Date:** 2026-05-11  
**Status:** Approved

---

## Objectif

Transformer le formulaire de création de traitement (page unique avec 4 panels) en un wizard multi-étapes qui guide l'utilisateur pas à pas, en ne montrant que les informations pertinentes selon le type de traitement.

---

## Architecture

### Approche retenue : `$step` dans `TreatmentCreate`

Le composant Livewire existant (`app/Livewire/TreatmentCreate.php`) est étendu avec une propriété `$step` (entier, 1–5). Aucun nouveau composant Livewire n'est créé.

**Nouvelles propriétés :**
```php
public int $step = 1;
```

**Nouvelles méthodes :**
- `nextStep()` — valide l'étape courante, incrémente `$step` en ignorant les étapes non applicables
- `prevStep()` — décrémente `$step` en ignorant les étapes non applicables
- `applicableSteps()` — retourne le tableau des numéros d'étapes actives selon `$type` et `$isMedicalAct`
- `stepLabel(int $step)` — retourne le titre de l'étape courante

La vue Blade (`resources/views/livewire/treatment-create.blade.php`) est réécrite pour afficher le contenu selon `$step`.

---

## Structure des étapes

| # | Nom | Champs | Condition d'affichage |
|---|-----|--------|-----------------------|
| 1 | Informations de base | `name`, `commercialName`, `type`, `color`, `isMedicalAct`, `requiresFasting`, `parentTreatmentId`, `linkedDays` | Toujours |
| 2 | Widget accueil | `showWidget`, `widgetIcon` | Toujours |
| 3 | Posologie | `unit`, `dosageMode`, `currentDose`, `doseMorning`, `doseNoon`, `doseEvening` | Si `!isMedicalAct` |
| 4 | Récurrence | `recurrenceStart`, `frequencyWeeks` | Si `type === 'cyclic'` |
| 5 | Récapitulatif | Lecture seule, bouton "Créer le traitement" | Toujours |

---

## Navigation

### Indicateur de progression (dots)

- 5 dots fixes en haut de chaque écran
- Dot actif : pill allongée (24 px × 7 px), couleur sky-500
- Dots complétés (étapes passées) : couleur emerald-500
- Dots non applicables (étapes skippées) : gris pâle, opacité 35 %
- Dots à venir : gris clair
- Sous les dots : texte "Étape X sur 5"

### Titre d'étape

Affiché dans le header de la card, en remplacement de l'ancien label de panel.

### Boutons de navigation

- **Étapes 1–4 :** bouton "Précédent" (gris, flex-1) + bouton "Suivant →" (gradient sky→indigo, flex-2)
- **Étape 1 :** pas de bouton "Précédent" (ou désactivé)
- **Étape 5 :** bouton "Créer le traitement" (pleine largeur, gradient)

---

## Gestion des étapes conditionnelles

Les dots des étapes non applicables sont toujours affichés (gris pâle, 35 % opacité). Lors de la navigation, `nextStep()` et `prevStep()` calculent la prochaine étape applicable via `applicableSteps()` et sautent les étapes non concernées. L'utilisateur ne voit jamais le contenu d'une étape inapplicable.

**Logique `applicableSteps()` :**
```php
public function applicableSteps(): array
{
    $steps = [1, 2];
    if (!$this->isMedicalAct) $steps[] = 3;
    if ($this->type === 'cyclic') $steps[] = 4;
    $steps[] = 5;
    return $steps;
}
```

**Effets de bord :** si l'utilisateur change `type` ou `isMedicalAct` en étape 1, les étapes applicables se recalculent. Si `$step` devient non applicable suite à un changement, on revient à l'étape 1.

---

## Validation par étape

`nextStep()` déclenche `$this->validate()` avec uniquement les règles de l'étape courante avant d'avancer :

| Étape | Règles validées |
|-------|-----------------|
| 1 | `name` (required), `type` (required\|in:daily,weekly,cyclic), `color` (required) |
| 2 | Aucune (tout optionnel) |
| 3 | `unit` (nullable), doses (nullable) |
| 4 | `frequencyWeeks` (required\|min:1), `recurrenceStart` (nullable\|date) |
| 5 | Validation complète avant `save()` |

---

## Étape 5 — Récapitulatif

Affichage en lecture seule des valeurs saisies :
- Nom (+ nom commercial si renseigné)
- Type (label lisible : "Quotidien", "Hebdomadaire", "Cyclique")
- Couleur (pastille colorée)
- Acte médical / À jeun (si actifs)
- Widget (si activé)
- Posologie (si applicable) : mode + valeurs
- Récurrence (si applicable) : date début + fréquence
- Traitement lié (si sélectionné)

Bouton "Créer le traitement" en bas, pleine largeur. Déclenche `save()`.

---

## Fichiers modifiés

| Fichier | Action |
|---------|--------|
| `app/Livewire/TreatmentCreate.php` | Ajout `$step`, `nextStep()`, `prevStep()`, `applicableSteps()`, `stepLabel()` + validation par étape dans `nextStep()` |
| `resources/views/livewire/treatment-create.blade.php` | Réécriture complète : dots, titre étape, contenu conditionnel par `$step`, boutons de navigation |

Aucun autre fichier n'est modifié (modèle, routes, migrations inchangés).

---

## Non-inclus dans ce scope

- Animation/transition entre étapes (simple swap Livewire suffit)
- Sauvegarde de brouillon (pas de session intermédiaire)
- Modification de `TreatmentEdit` (hors scope)
