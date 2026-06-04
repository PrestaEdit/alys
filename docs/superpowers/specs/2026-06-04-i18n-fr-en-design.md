# Design — Support multilingue FR / EN (Alys)

**Date :** 2026-06-04
**Statut :** Approuvé (brainstorming) — prêt pour plan d'implémentation

## Contexte

L'app Alys (calendrier de traitement médical, Laravel + NativePHP + Livewire/Blade) est aujourd'hui **100 % en français en dur** :

- Aucun dossier `lang/` ni infra de traduction.
- ~150-200 chaînes FR codées directement dans les Blade/Livewire.
- Dates Carbon formatées avec `.locale('fr')->isoFormat(...)` dans ~6 fichiers.
- `config/app.php` / `.env` configurés sur `en` (incohérent avec l'UI réelle).

Objectif : ajouter l'anglais (EN) à côté du français, avec **auto-détection** de la langue du téléphone au premier lancement **et** **choix manuel** persistant dans les Réglages.

### Contrainte NativePHP

NativePHP mobile **n'expose aucune API de locale/langue** (la facade `System` se limite à `flashlight/isAndroid/isIos/isMobile/appSettings` ; le Device API ne renvoie ni langue ni région). L'auto-détection passe donc par l'en-tête HTTP **`Accept-Language`** envoyé par la WebView vers le serveur Laravel embarqué, exploité côté serveur via un middleware.

## Décisions

- **Stratégie de traduction : clés sémantiques** (`__('dashboard.today')`), fichiers PHP structurés sous `lang/{fr,en}/`. Plus durable et idiomatique que la clé-=-texte-FR ; les deux langues restent synchronisées.
- **Langue source / fallback : `fr`.**
- **Périmètre exclu (YAGNI) :** pas d'autres langues que FR/EN ; pas de traduction des données saisies par l'utilisateur (noms de traitements) ; pas de détection via API native (inexistante).

## Architecture

### 1. Fichiers de traduction (`lang/fr/`, `lang/en/`)

Découpage par domaine, calqué sur les vues. Chaque clé existe à l'identique dans les deux langues.

| Fichier | Contenu |
|---|---|
| `nav.php` | Accueil, Calendrier, Traitements, Réglages |
| `dashboard.php` | Aujourd'hui, fin de traitement, prochaine visite… |
| `treatments.php` | Liste + création + édition (labels, aides, états vides) |
| `calendar.php` | Mois, états vides, libellés |
| `profiles.php` | profils + création + switcher |
| `settings.php` | Réglages + libellés du sélecteur de langue |
| `onboarding.php` | Onboarding |
| `data.php` | export / import / key-transfer |
| `common.php` | Boutons partagés (Modifier, Annuler, Enregistrer, Archiver…) |
| `validation.php` | Surcharge des messages de validation Laravel (FR + EN) |

Appels via `__('domaine.cle')` / `@lang('domaine.cle')`.

### 2. Middleware `SetLocale`

Même pattern que `EnsureOnboardingCompleted`. Ordre de priorité :

1. **Choix manuel** — `Setting::get('locale')` s'il vaut `fr` ou `en` → gagne toujours.
2. **Auto-détection** — sinon, parser `Accept-Language` : commence par `en` → `en`, sinon `fr`.
3. **Fallback** — `fr`.

Applique `app()->setLocale($l)` **et** `Carbon::setLocale($l)`. Enregistré dans le groupe `web`, suffisamment tôt pour que toutes les vues en bénéficient. Auto-détection (1er lancement, pas de Setting) et override manuel sortent du même endroit, sans duplication.

### 3. Config

- `config/app.php` + `.env` : `APP_LOCALE=fr`, `APP_FALLBACK_LOCALE=fr`.
- Corrige l'incohérence actuelle (`en` configuré alors que l'UI est FR).

### 4. Dates Carbon

Les ~6 fichiers utilisant `.locale('fr')->isoFormat(...)` : retirer le `.locale('fr')` explicite et s'appuyer sur le `Carbon::setLocale()` global posé par le middleware. Mois/jours suivent alors la langue active.

Fichiers concernés (à confirmer pendant le plan) :
- `app/Livewire/Dashboard.php`, `app/Livewire/Calendar.php`
- `resources/views/livewire/{dashboard,calendar,export,treatment-edit,treatments}.blade.php`

### 5. UI — sélecteur dans Réglages

`app/Livewire/Settings.php` + `resources/views/livewire/settings.blade.php` :
- Contrôle segmenté **FR / EN**.
- Méthode `setLocale(string $l)` → `Setting::set('locale', $l)` puis `$this->redirect(...)` pour relancer le middleware et re-rendre toutes les chaînes.
- État actif lu depuis `app()->getLocale()`.

## Flux

```
Requête WebView
  └─> middleware SetLocale
        1. Setting('locale') ∈ {fr,en} ?  → applique
        2. sinon Accept-Language ~ ^en ?  → en
        3. sinon                          → fr
        └─> app()->setLocale + Carbon::setLocale
              └─> vues rendent __() dans la bonne langue
                  dates Carbon dans la bonne langue

Réglages → setLocale('en')
  └─> Setting::set('locale','en') → redirect → middleware → app en EN
```

## Tests

- **Middleware :** résolution depuis `Setting` ; depuis `Accept-Language` (en / fr / autre) ; fallback `fr`.
- **Sélecteur Réglages :** persiste le choix et bascule la langue.
- **Parité des clés :** `lang/fr` et `lang/en` exposent exactement les mêmes clés (détecte les chaînes oubliées).

## Hors périmètre

- Langues autres que FR/EN.
- Traduction des données utilisateur saisies.
- Détection via API native NativePHP (inexistante).
