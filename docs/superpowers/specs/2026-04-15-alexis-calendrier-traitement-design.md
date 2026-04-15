# Design — Alexis : Calendrier de traitement

**Date :** 15 avril 2026  
**Stack :** NativePHP Mobile · Laravel 11 · PHP 8.4 · SQLite · Livewire 3 · Alpine.js · Tailwind CSS · Preline UI  
**Plateforme cible :** Android  
**Usage :** Familial uniquement

---

## Contexte

Application mobile permettant de suivre le calendrier de traitement d'Alexis.  
**Période couverte :** 26 novembre 2025 → 31 mars 2027.

L'application est un **calendrier de référence** (pas de suivi de compliance). Elle permet de visualiser les traitements prévus, de modifier les posologies et de déplacer des événements ponctuels.

---

## Traitements

| Traitement | Nom commercial | Fréquence | Posologie initiale | Notes |
|---|---|---|---|---|
| 6-MP | Purinéthol | Quotidien | 1 cachet/jour | — |
| 6-TG | Lanvis | Quotidien | 2,8 ml/jour → **3 ml dès le 15/04/2026** | Posologie variable |
| MTX | Méthotrexate | Tous les mardis | 9 cachets | Sauf jours de ponction lombaire |
| VCR | Vincristine | Toutes les 4 semaines | IV à l'hôpital | Depuis le 26/11/2025 |
| IT MTTX | Ponction lombaire | Toutes les 8 semaines | Acte médical | Depuis le 21/01/2026 — **jeûne obligatoire** |
| Hôpital | Visite | Toutes les 2 semaines | — | Depuis le 26/11/2025 |

---

## Architecture

### Stack technique

- **NativePHP Mobile** : serveur PHP embarqué dans l'APK Android, UI en WebView
- **Laravel 11** avec **PHP 8.4**
- **SQLite** : base de données locale embarquée (aucune dépendance réseau)
- **Livewire 3** : réactivité côté serveur (mise à jour des widgets, modification de posologie)
- **Alpine.js** : micro-interactions UI (modals, animations)
- **Tailwind CSS + Preline UI** : composants visuels, thème clair

### Navigation

3 onglets en bas (bottom tab bar) :

```
[ 🏠 Accueil ] [ 📅 Calendrier ] [ 💊 Traitements ]
```

---

## Modèle de données

### Table `treatments`

| Colonne | Type | Description |
|---|---|---|
| id | integer PK | — |
| name | string | Nom court (6-MP, MTX…) |
| commercial_name | string | Nom commercial (Purinéthol…) |
| type | enum | `daily`, `weekly`, `cyclic`, `medical_act` |
| unit | string | cachet, ml, IV… |
| current_dose | decimal | Posologie courante |
| color | string | Code couleur hex pour l'UI |
| frequency_weeks | integer | Nb de semaines entre occurrences (null si quotidien/hebdo) |
| day_of_week | integer nullable | Jour de semaine fixe pour `weekly` (0=lundi … 6=dimanche ; MTX = 1) |
| recurrence_start | date nullable | Date de première occurrence (VCR, IT MTTX, hôpital ; null si daily/weekly) |
| notes | text | Notes libres |

### Table `posology_history`

| Colonne | Type | Description |
|---|---|---|
| id | integer PK | — |
| treatment_id | integer FK | → treatments |
| dose | decimal | Valeur de la posologie |
| note | text | Note associée au changement (optionnel) |
| started_at | date | Date d'entrée en vigueur (= date du jour à la saisie) |
| created_at | timestamp | Horodatage automatique |

### Table `calendar_events`

Générée **une fois à l'installation** à partir des règles de récurrence, puis stockée pour permettre les déplacements.

| Colonne | Type | Description |
|---|---|---|
| id | integer PK | — |
| treatment_id | integer FK | → treatments |
| scheduled_date | date | Date planifiée (modifiable) |
| original_date | date nullable | Date initiale si l'événement a été déplacé |
| is_cancelled | boolean | Événement annulé |
| notes | text | Notes (ex: motif du déplacement) |

> **Traitements quotidiens (6-MP, 6-TG) :** non stockés en base événement par événement. Calculés à l'affichage à partir de `treatments.recurrence_start` et de la posologie courante issue de `posology_history`.

### Table `settings`

| Clé | Valeur |
|---|---|
| `treatment_start` | `2025-11-26` |
| `treatment_end` | `2027-03-31` |

---

## Écrans

### Onglet Accueil

- **En-tête** : date du jour + prénom "Alexis 💙" + bouton export (haut droite)
- **Bannière** : prochain RDV hôpital (calculé dynamiquement)
- **Barre de progression** : avancement du traitement nov. 2025 → mars 2027
- **4 widgets** (grille 2×2) :
  - Visites hôpital restantes (orange)
  - Vincristines restantes (violet)
  - Ponctions lombaires restantes (vert)
  - MTX restants (rouge)
- **Section "Aujourd'hui"** : liste des traitements du jour avec posologie courante ; événements spéciaux (VCR, hôpital) mis en évidence
- **Export** : partage JSON via le Share Sheet Android (Google Drive, email…)

Tous les compteurs sont calculés dynamiquement à partir de la date du jour et de `calendar_events`.

### Onglet Calendrier

- **Navigation mensuelle** avec flèches (mois précédent / suivant)
- **Grille mensuelle** avec points de couleur par type d'événement :
  - 🟠 Visite hôpital
  - 🔴 MTX
  - 🟣 VCR
  - 🔵 IT MTTX
  - (6-MP et 6-TG : non affichés sur la grille, visible dans le détail)
- **Tap sur un jour** → panneau de détail en bas :
  - Liste des événements du jour avec posologie
  - **Alerte jeûne** si IT MTTX
  - Bouton **Déplacer** sur les événements ponctuels (VCR, IT MTTX, hôpital, MTX)
- **Déplacement** : sélecteur de date → met à jour `calendar_events.scheduled_date` et renseigne `original_date`

### Onglet Traitements

**Vue liste :**
- Carte par traitement : couleur, nom, nom commercial, posologie courante, fréquence
- Badge "Actuel" sur la posologie en vigueur
- Mini indicateur d'historique si au moins un changement passé
- Bouton **Modifier** (sauf IT MTTX — acte médical sans posologie variable)

**Vue détail (tap sur "Modifier") :**
- Sélecteur +/− pour la dose (ou saisie libre)
- Champ note optionnel
- Bouton **Enregistrer** → horodate automatiquement, insère dans `posology_history`, met à jour `treatments.current_dose`
- **Frise historique** : toutes les posologies passées avec date de début/fin et note

---

## Génération du calendrier initial

À la première installation, un **seeder Laravel** génère tous les `calendar_events` :

1. **Hôpital** : toutes les 2 semaines depuis le 26/11/2025 jusqu'au 31/03/2027
2. **VCR** : toutes les 4 semaines depuis le 26/11/2025 jusqu'au 31/03/2027
3. **IT MTTX** : toutes les 8 semaines depuis le 21/01/2026 jusqu'au 31/03/2027
4. **MTX** : tous les mardis de la période, **sauf** les jours où un IT MTTX est prévu à la même date

**Règle de cohérence MTX / IT MTTX :** si un IT MTTX est ultérieurement **déplacé sur un mardi**, l'événement MTX de ce mardi est automatiquement marqué `is_cancelled = true`. Inversement, si l'IT MTTX quitte un mardi (déplacement ou annulation), le MTX annulé est restauré.

---

## Export

Format JSON incluant :
- `settings` (dates de traitement)
- `treatments` (avec posologie courante)
- `posology_history` (historique complet)
- `calendar_events` (événements et déplacements)

Partagé via `Intent.ACTION_SEND` Android (Share Sheet natif).

---

## Ce qui est hors périmètre (v1)

- Notifications push (prévu pour une version ultérieure)
- Authentification / multi-utilisateur
- Synchronisation temps réel
- Import de données externes
