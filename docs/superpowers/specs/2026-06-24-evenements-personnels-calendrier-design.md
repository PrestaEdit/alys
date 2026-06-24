# Événements personnels dans le calendrier — Design

**Date :** 2026-06-24
**Statut :** Validé

## Contexte et objectif

Aujourd'hui, tout ce qui apparaît dans le calendrier est un `CalendarEvent` rattaché
obligatoirement à un `Treatment` (`treatment_id` non nul). L'utilisateur veut pouvoir
noter dans le calendrier des **événements non médicaux** : vacances, excursions, etc.

Ces événements sont **purement informatifs** : ils n'affectent ni les prises ni la
logique de traitement. Ils peuvent **couvrir une plage de plusieurs jours** (ex :
vacances du 10 au 20).

## Approche retenue

**Table et modèle dédiés `PersonalEvent`**, séparés de `CalendarEvent`/`Treatment`.
Le `CalendarService` fusionne ces événements avec les événements de traitement à
l'affichage. La plage de dates est native (`start_date`/`end_date`), et la logique
médicale existante n'est pas touchée.

Alternatives écartées :
- **Réutiliser `CalendarEvent`** (rendre `treatment_id` nullable) : `CalendarEvent` est
  mono-date, donc une plage multi-jours imposerait une ligne par jour, et mêlerait des
  concepts non médicaux à la table des prises (casse les filtres `whereHas('treatment')`).
- **Modéliser comme un faux traitement** : les traitements portent dosage, posologie et
  notifications — inadapté.

## Modèle de données

Nouvelle migration `create_personal_events_table` :

| Colonne       | Type            | Notes                                                    |
|---------------|-----------------|----------------------------------------------------------|
| `id`          | bigint          |                                                          |
| `profile_id`  | FK → profiles   | comme `CalendarEvent` ; via trait `BelongsToActiveProfile` |
| `title`       | string          | obligatoire (ex : « Vacances Espagne »)                  |
| `category`    | string          | `vacances` \| `excursion` \| `autre`                     |
| `color`       | string          | hex ; pré-rempli par la catégorie, modifiable            |
| `icon`        | string          | emoji ; pré-rempli par la catégorie, modifiable          |
| `notes`       | text nullable   |                                                          |
| `start_date`  | date            |                                                          |
| `end_date`    | date            | = `start_date` pour un événement d'un seul jour          |
| timestamps    |                 |                                                          |

Modèle `App\Models\PersonalEvent` :
- Trait `BelongsToActiveProfile` (isolation par profil actif, comme `CalendarEvent`).
- Casts `start_date` / `end_date` en `date`.
- Scope `forMonth(int $year, int $month)` : sélectionne les événements dont la plage
  `[start_date, end_date]` chevauche le mois affiché.
- Constante des catégories par défaut avec leur icône et couleur :

| Catégorie  | Clé         | Icône | Couleur     |
|------------|-------------|-------|-------------|
| Vacances   | `vacances`  | 🏖️    | `#0ea5e9`   |
| Excursion  | `excursion` | 🚌    | `#10b981`   |
| Autre      | `autre`     | 📌    | `#f59e0b`   |

## Affichage dans le calendrier

Le `CalendarService` est étendu pour fusionner les événements personnels avec les
événements de traitement. Chaque entrée renvoyée porte un drapeau `kind`
(`treatment` | `personal`) pour les distinguer dans la vue et conditionner les actions.

**Grille mensuelle (`getEventsForMonth`)** : chaque `PersonalEvent` est étalé sur chaque
jour de sa plage `[start_date, end_date]` tombant dans le mois, en ajoutant un point de
sa couleur — au même titre que les traitements (aucune distinction visuelle dans la grille).

**Détail du jour (`getEventsForDay`)** : ajoute les événements personnels actifs ce
jour-là. Un événement personnel s'affiche avec **icône + titre**, sa couleur et sa note.
Pour une plage multi-jours, la période est indiquée discrètement (ex : « 10 → 20 juin »).
Un événement personnel propose **Modifier / Supprimer** au lieu de **Déplacer**.

## Création, édition, suppression

Tout passe par la vue calendrier (composant Livewire `Calendar`), via un **modal**
réutilisé pour la création et l'édition.

**Création**
- Quand un jour est sélectionné, un bouton **« + Événement »** apparaît dans le panneau
  de détail du jour.
- Le modal s'ouvre avec `start_date = end_date = ` jour sélectionné.
- Champs : titre (texte), catégorie (3 boutons Vacances / Excursion / Autre),
  date de début + date de fin (`<x-datepicker>`), couleur (pastilles de la palette
  `TreatmentCreate::COLORS`), icône (sélecteur d'emojis sur le modèle de `widget_icon`),
  note (textarea).
- Choisir une catégorie pré-remplit icône + couleur ; valeurs ensuite ajustables.

**Édition / suppression**
- Chaque événement personnel du détail du jour a **Modifier** (rouvre le modal pré-rempli)
  et **Supprimer** (avec confirmation).

**Validation**
- `title` requis.
- `start_date` requise.
- `end_date` requise et `>= start_date`.
- `category` dans la liste autorisée.

## i18n

L'app gère FR + EN. Toutes les nouvelles chaînes (libellés de catégories, boutons, titres
du modal, messages de validation) sont ajoutées dans `lang/fr/` et `lang/en/`, dans un
nouveau fichier `events.php`.

## Tests

Test couvrant modèle + service :
- Un événement multi-jours apparaît correctement sur **chaque** jour de sa plage dans
  `getEventsForMonth` et `getEventsForDay`.
- Isolation par profil : un événement d'un profil n'apparaît pas pour un autre profil.

## Hors périmètre (YAGNI)

- Pas de notifications / rappels (purement informatif).
- Pas de récurrence (un événement = une plage one-off).
- Pas de page dédiée séparée (tout depuis le calendrier).
- Intégration à l'export/import existant : **suivi séparé**, non inclus ici.
