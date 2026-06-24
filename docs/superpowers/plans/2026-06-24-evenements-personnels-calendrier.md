# Événements personnels dans le calendrier — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers-extended-cc:subagent-driven-development (recommended) or superpowers-extended-cc:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permettre de noter dans le calendrier des événements non médicaux (vacances, excursions, autre) couvrant une plage de dates, purement informatifs.

**Architecture:** Nouveau modèle/table `PersonalEvent` dédié (isolé par profil via `BelongsToActiveProfile`), avec plage `start_date`/`end_date`. Le `CalendarService` fusionne ces événements avec les événements de traitement à l'affichage en marquant chaque entrée d'un drapeau `kind`. Création/édition/suppression via un modal dans le composant Livewire `Calendar`.

**Tech Stack:** Laravel, Eloquent, Livewire, Blade, Tailwind, Pest. i18n FR/EN.

**Spec:** `docs/superpowers/specs/2026-06-24-evenements-personnels-calendrier-design.md`

---

### Task 1: Modèle `PersonalEvent` + migration

**Goal:** Créer la table `personal_events` et le modèle Eloquent avec catégories par défaut, isolation par profil et scope `forMonth`.

**Files:**
- Create: `database/migrations/2026_06_24_000000_create_personal_events_table.php`
- Create: `app/Models/PersonalEvent.php`
- Test: `tests/Unit/Models/PersonalEventTest.php`

**Acceptance Criteria:**
- [ ] La table `personal_events` est créée avec toutes les colonnes du design.
- [ ] `PersonalEvent` utilise `BelongsToActiveProfile` (profil auto-rempli, isolation).
- [ ] `PersonalEvent::CATEGORIES` expose icône + couleur par défaut pour `vacances`, `excursion`, `autre`.
- [ ] Le scope `forMonth($year, $month)` retourne les événements dont la plage chevauche le mois.

**Verify:** `php artisan test --filter=PersonalEventTest` → tous verts.

**Steps:**

- [ ] **Step 1: Écrire le test (échoue)**

Créer `tests/Unit/Models/PersonalEventTest.php` :

```php
<?php

use App\Models\PersonalEvent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('creates a personal event attached to the active profile', function () {
    $event = PersonalEvent::create([
        'title'      => 'Vacances Espagne',
        'category'   => 'vacances',
        'color'      => '#0ea5e9',
        'icon'       => '🏖️',
        'start_date' => '2026-07-10',
        'end_date'   => '2026-07-20',
    ]);

    expect($event->profile_id)->not->toBeNull();
    expect($event->start_date->toDateString())->toBe('2026-07-10');
    expect($event->end_date->toDateString())->toBe('2026-07-20');
});

it('exposes default icon and color per category', function () {
    expect(PersonalEvent::CATEGORIES['vacances']['icon'])->toBe('🏖️');
    expect(PersonalEvent::CATEGORIES['excursion']['color'])->toBe('#10b981');
    expect(array_keys(PersonalEvent::CATEGORIES))->toBe(['vacances', 'excursion', 'autre']);
});

it('forMonth returns events whose range overlaps the month', function () {
    // Couvre fin juin → début juillet
    PersonalEvent::create([
        'title' => 'Pont', 'category' => 'autre', 'color' => '#f59e0b', 'icon' => '📌',
        'start_date' => '2026-06-28', 'end_date' => '2026-07-02',
    ]);
    // Entièrement en août → exclu
    PersonalEvent::create([
        'title' => 'Août', 'category' => 'autre', 'color' => '#f59e0b', 'icon' => '📌',
        'start_date' => '2026-08-01', 'end_date' => '2026-08-05',
    ]);

    $july = PersonalEvent::forMonth(2026, 7)->get();
    expect($july)->toHaveCount(1);
    expect($july->first()->title)->toBe('Pont');
});
```

- [ ] **Step 2: Lancer le test (échoue)**

Run: `php artisan test --filter=PersonalEventTest`
Expected: FAIL (`Class "App\Models\PersonalEvent" not found` / table absente)

- [ ] **Step 3: Créer la migration**

Créer `database/migrations/2026_06_24_000000_create_personal_events_table.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category');
            $table->string('color');
            $table->string('icon');
            $table->text('notes')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index('profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_events');
    }
};
```

- [ ] **Step 4: Créer le modèle**

Créer `app/Models/PersonalEvent.php` :

```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToActiveProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PersonalEvent extends Model
{
    use BelongsToActiveProfile;

    protected $fillable = [
        'profile_id', 'title', 'category', 'color', 'icon', 'notes', 'start_date', 'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /** Catégories prédéfinies → icône + couleur par défaut (modifiables ensuite). */
    public const CATEGORIES = [
        'vacances'  => ['icon' => '🏖️', 'color' => '#0ea5e9'],
        'excursion' => ['icon' => '🚌', 'color' => '#10b981'],
        'autre'     => ['icon' => '📌', 'color' => '#f59e0b'],
    ];

    /** Emojis proposés dans le sélecteur d'icône. */
    public const ICONS = [
        '🏖️', '🚌', '✈️', '🏕️', '⛰️', '🏊', '🎉', '🎂', '🎄', '🏠', '🚗', '📌',
    ];

    /** Événements dont la plage [start_date, end_date] chevauche le mois donné. */
    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        return $query->whereDate('start_date', '<=', $end)
                     ->whereDate('end_date', '>=', $start);
    }
}
```

- [ ] **Step 5: Lancer le test (passe)**

Run: `php artisan test --filter=PersonalEventTest`
Expected: PASS (3 tests)

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_24_000000_create_personal_events_table.php app/Models/PersonalEvent.php tests/Unit/Models/PersonalEventTest.php
git commit -m "feat(events): add PersonalEvent model and migration"
```

---

### Task 2: Fusion des événements personnels dans `CalendarService`

**Goal:** Afficher les événements personnels dans la grille mensuelle (un point par jour de la plage) et dans le détail du jour, chaque entrée portant un drapeau `kind`.

**Files:**
- Modify: `app/Services/CalendarService.php` (`getEventsForMonth`, `getEventsForDay`)
- Test: `tests/Unit/Services/CalendarServiceTest.php` (ajouts)

**Acceptance Criteria:**
- [ ] Un événement multi-jours apparaît sur chaque jour de sa plage dans `getEventsForMonth`.
- [ ] `getEventsForDay` retourne l'événement personnel les jours couverts, avec `kind=personal`, `icon`, `title`, `is_multi_day`, `start_date`, `end_date`.
- [ ] Les entrées de traitement existantes portent `kind=treatment`.

**Verify:** `php artisan test --filter=CalendarServiceTest` → tous verts.

**Steps:**

- [ ] **Step 1: Écrire les tests (échouent)**

Ajouter à la fin de `tests/Unit/Services/CalendarServiceTest.php` :

```php
it('getEventsForMonth includes a personal event on each day of its range', function () {
    \App\Models\PersonalEvent::create([
        'title' => 'Vacances', 'category' => 'vacances', 'color' => '#0ea5e9', 'icon' => '🏖️',
        'start_date' => '2026-04-10', 'end_date' => '2026-04-12',
    ]);

    $month = $this->service->getEventsForMonth(2026, 4);

    foreach (['2026-04-10', '2026-04-11', '2026-04-12'] as $day) {
        $titles = collect($month[$day] ?? [])->where('kind', 'personal')->pluck('name')->toArray();
        expect($titles)->toContain('Vacances');
    }
    // Hors plage : pas d'événement personnel le 13
    $day13 = collect($month['2026-04-13'] ?? [])->where('kind', 'personal')->pluck('name')->toArray();
    expect($day13)->not->toContain('Vacances');
});

it('getEventsForDay returns a personal event with its metadata', function () {
    \App\Models\PersonalEvent::create([
        'title' => 'Excursion', 'category' => 'excursion', 'color' => '#10b981', 'icon' => '🚌',
        'start_date' => '2026-04-16', 'end_date' => '2026-04-18',
    ]);

    $events = collect($this->service->getEventsForDay(\Carbon\Carbon::parse('2026-04-17')));
    $personal = $events->firstWhere('kind', 'personal');

    expect($personal)->not->toBeNull();
    expect($personal['title'])->toBe('Excursion');
    expect($personal['icon'])->toBe('🚌');
    expect($personal['is_multi_day'])->toBeTrue();
    expect($personal['start_date'])->toBe('2026-04-16');
    expect($personal['end_date'])->toBe('2026-04-18');
});

it('treatment entries are flagged with kind=treatment', function () {
    $events = collect($this->service->getEventsForDay(\Carbon\Carbon::parse('2026-04-29')));
    $hospital = $events->firstWhere('name', 'Hôpital');
    expect($hospital['kind'])->toBe('treatment');
});
```

- [ ] **Step 2: Lancer les tests (échouent)**

Run: `php artisan test --filter=CalendarServiceTest`
Expected: FAIL (clé `kind` absente, événements personnels non retournés)

- [ ] **Step 3: Importer le modèle**

Dans `app/Services/CalendarService.php`, ajouter sous les `use` existants :

```php
use App\Models\PersonalEvent;
```

- [ ] **Step 4: Marquer les traitements et ajouter les événements personnels dans `getEventsForDay`**

Dans `getEventsForDay`, ajouter `'kind' => 'treatment',` en première clé de **chacun** des deux tableaux poussés (boucle `$dailyTreatments` et boucle `$calendarEvents`). Puis, juste avant `return $events;`, ajouter :

```php
        $personalEvents = PersonalEvent::whereDate('start_date', '<=', $dateStr)
            ->whereDate('end_date', '>=', $dateStr)
            ->orderBy('start_date')
            ->get();

        foreach ($personalEvents as $event) {
            $events[] = [
                'kind'             => 'personal',
                'id'               => $event->id,
                'title'            => $event->title,
                'name'             => $event->title,
                'display_name'     => $event->title,
                'category'         => $event->category,
                'icon'             => $event->icon,
                'color'            => $event->color,
                'notes'            => $event->notes,
                'start_date'       => $event->start_date->toDateString(),
                'end_date'         => $event->end_date->toDateString(),
                'is_multi_day'     => ! $event->start_date->isSameDay($event->end_date),
                'requires_fasting' => false,
                'can_move'         => false,
                'moved'            => false,
            ];
        }
```

- [ ] **Step 5: Marquer les traitements et étaler les événements personnels dans `getEventsForMonth`**

Remplacer le corps de `getEventsForMonth` par :

```php
    public function getEventsForMonth(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $grouped = CalendarEvent::with('treatment')
            ->whereBetween('scheduled_date', [$start->toDateString(), $end->toDateString()])
            ->where('is_cancelled', false)
            ->whereHas('treatment', fn($q) => $q->whereNull('archived_at'))
            ->get()
            ->groupBy(fn($e) => $e->scheduled_date->toDateString());

        $result = $grouped->map(fn($dayEvents) => $dayEvents->map(fn($e) => [
            'kind'             => 'treatment',
            'treatment_id'     => $e->treatment_id,
            'name'             => $e->treatment->name,
            'display_name'     => $e->treatment->displayName(),
            'color'            => $e->treatment->color,
            'requires_fasting' => $e->treatment->requiresFasting(),
        ])->values()->toArray())->toArray();

        foreach (PersonalEvent::forMonth($year, $month)->get() as $event) {
            $cursor = $event->start_date->copy()->max($start);
            $last   = $event->end_date->copy()->min($end);
            for (; $cursor->lte($last); $cursor->addDay()) {
                $key = $cursor->toDateString();
                $result[$key][] = [
                    'kind'             => 'personal',
                    'name'             => $event->title,
                    'display_name'     => $event->title,
                    'color'            => $event->color,
                    'requires_fasting' => false,
                ];
            }
        }

        return $result;
    }
```

- [ ] **Step 6: Lancer les tests (passent)**

Run: `php artisan test --filter=CalendarServiceTest`
Expected: PASS (tous, anciens + 3 nouveaux)

- [ ] **Step 7: Commit**

```bash
git add app/Services/CalendarService.php tests/Unit/Services/CalendarServiceTest.php
git commit -m "feat(events): merge personal events into calendar service views"
```

---

### Task 3: Chaînes i18n (`events.php` FR + EN)

**Goal:** Fournir toutes les chaînes du modal et de l'affichage en français et anglais, avec parité des clés.

**Files:**
- Create: `lang/fr/events.php`
- Create: `lang/en/events.php`
- Test: `tests/Feature/TranslationParityTest.php` (existant, doit rester vert)

**Acceptance Criteria:**
- [ ] `lang/fr/events.php` et `lang/en/events.php` existent avec des clés identiques.
- [ ] Le test de parité des traductions passe.

**Verify:** `php artisan test --filter=TranslationParityTest` → vert.

**Steps:**

- [ ] **Step 1: Créer `lang/fr/events.php`**

```php
<?php

return [
    'add'              => 'Événement',
    'new_title'        => 'Nouvel événement',
    'edit_title'       => 'Modifier l\'événement',
    'field_title'      => 'Titre',
    'field_title_ph'   => 'Ex : Vacances en Espagne',
    'field_category'   => 'Catégorie',
    'field_start'      => 'Du',
    'field_end'        => 'Au',
    'field_color'      => 'Couleur',
    'field_icon'       => 'Icône',
    'field_notes'      => 'Note',
    'field_notes_ph'   => 'Note optionnelle',
    'category_vacances'  => 'Vacances',
    'category_excursion' => 'Excursion',
    'category_autre'     => 'Autre',
    'period'           => 'Du :start au :end',
    'save'             => 'Enregistrer',
    'edit'             => 'Modifier',
    'delete'           => 'Supprimer',
    'delete_confirm'   => 'Supprimer cet événement ?',
];
```

- [ ] **Step 2: Créer `lang/en/events.php`**

```php
<?php

return [
    'add'              => 'Event',
    'new_title'        => 'New event',
    'edit_title'       => 'Edit event',
    'field_title'      => 'Title',
    'field_title_ph'   => 'E.g. Holidays in Spain',
    'field_category'   => 'Category',
    'field_start'      => 'From',
    'field_end'        => 'To',
    'field_color'      => 'Color',
    'field_icon'       => 'Icon',
    'field_notes'      => 'Note',
    'field_notes_ph'   => 'Optional note',
    'category_vacances'  => 'Holidays',
    'category_excursion' => 'Excursion',
    'category_autre'     => 'Other',
    'period'           => 'From :start to :end',
    'save'             => 'Save',
    'edit'             => 'Edit',
    'delete'           => 'Delete',
    'delete_confirm'   => 'Delete this event?',
];
```

- [ ] **Step 3: Lancer le test de parité**

Run: `php artisan test --filter=TranslationParityTest`
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add lang/fr/events.php lang/en/events.php
git commit -m "feat(events): add FR/EN translations for personal events"
```

---

### Task 4: Méthodes CRUD dans le composant Livewire `Calendar`

**Goal:** Ajouter au composant `Calendar` l'état et les méthodes pour créer, éditer et supprimer un événement personnel via un modal.

**Files:**
- Modify: `app/Livewire/Calendar.php`
- Test: `tests/Feature/Livewire/CalendarTest.php` (ajouts)

**Acceptance Criteria:**
- [ ] `openEventModal()` pré-remplit les dates avec le jour sélectionné et les valeurs par défaut de la catégorie `vacances`.
- [ ] `selectCategory()` applique l'icône et la couleur par défaut de la catégorie.
- [ ] `saveEvent()` valide et crée un `PersonalEvent` (et met à jour en mode édition) puis recharge le mois et le jour.
- [ ] `editEvent($id)` charge un événement existant dans le formulaire.
- [ ] `deleteEvent($id)` supprime l'événement et recharge.
- [ ] La validation rejette `eventEndDate` antérieure à `eventStartDate`.

**Verify:** `php artisan test --filter=CalendarTest` → tous verts.

**Steps:**

- [ ] **Step 1: Écrire les tests (échouent)**

Ajouter à la fin de `tests/Feature/Livewire/CalendarTest.php` :

```php
use App\Models\PersonalEvent;

it('creates a personal event through the modal', function () {
    Livewire::test(Calendar::class)
        ->call('selectDay', '2026-04-10')
        ->call('openEventModal')
        ->assertSet('showEventModal', true)
        ->assertSet('eventStartDate', '2026-04-10')
        ->set('eventTitle', 'Vacances')
        ->set('eventEndDate', '2026-04-15')
        ->call('saveEvent')
        ->assertSet('showEventModal', false);

    expect(PersonalEvent::where('title', 'Vacances')->exists())->toBeTrue();
});

it('applies category defaults when selecting a category', function () {
    Livewire::test(Calendar::class)
        ->call('selectCategory', 'excursion')
        ->assertSet('eventCategory', 'excursion')
        ->assertSet('eventIcon', '🚌')
        ->assertSet('eventColor', '#10b981');
});

it('rejects an end date before the start date', function () {
    Livewire::test(Calendar::class)
        ->call('selectDay', '2026-04-10')
        ->call('openEventModal')
        ->set('eventTitle', 'Invalide')
        ->set('eventStartDate', '2026-04-10')
        ->set('eventEndDate', '2026-04-05')
        ->call('saveEvent')
        ->assertHasErrors(['eventEndDate']);

    expect(PersonalEvent::where('title', 'Invalide')->exists())->toBeFalse();
});

it('edits an existing personal event', function () {
    $event = PersonalEvent::create([
        'title' => 'Avant', 'category' => 'autre', 'color' => '#f59e0b', 'icon' => '📌',
        'start_date' => '2026-04-10', 'end_date' => '2026-04-10',
    ]);

    Livewire::test(Calendar::class)
        ->call('editEvent', $event->id)
        ->assertSet('eventTitle', 'Avant')
        ->set('eventTitle', 'Après')
        ->call('saveEvent');

    expect($event->fresh()->title)->toBe('Après');
});

it('deletes a personal event', function () {
    $event = PersonalEvent::create([
        'title' => 'À supprimer', 'category' => 'autre', 'color' => '#f59e0b', 'icon' => '📌',
        'start_date' => '2026-04-10', 'end_date' => '2026-04-10',
    ]);

    Livewire::test(Calendar::class)->call('deleteEvent', $event->id);

    expect(PersonalEvent::find($event->id))->toBeNull();
});
```

- [ ] **Step 2: Lancer les tests (échouent)**

Run: `php artisan test --filter=CalendarTest`
Expected: FAIL (méthodes/propriétés inexistantes)

- [ ] **Step 3: Ajouter l'état et les méthodes au composant**

Dans `app/Livewire/Calendar.php`, ajouter l'import en haut :

```php
use App\Models\PersonalEvent;
```

Ajouter les propriétés après `public string $moveToDate = '';` :

```php
    // Événements personnels
    public bool $showEventModal = false;
    public ?int $editingEventId = null;
    public string $eventTitle = '';
    public string $eventCategory = 'vacances';
    public string $eventColor = '#0ea5e9';
    public string $eventIcon = '🏖️';
    public string $eventStartDate = '';
    public string $eventEndDate = '';
    public string $eventNotes = '';
```

Ajouter les méthodes avant `private function loadMonth(...)` :

```php
    public function openEventModal(): void
    {
        $date = $this->selectedDate ?? now()->toDateString();
        $this->resetEventForm();
        $this->eventStartDate = $date;
        $this->eventEndDate = $date;
        $this->showEventModal = true;
    }

    public function selectCategory(string $category): void
    {
        if (! array_key_exists($category, PersonalEvent::CATEGORIES)) {
            return;
        }
        $this->eventCategory = $category;
        $this->eventIcon = PersonalEvent::CATEGORIES[$category]['icon'];
        $this->eventColor = PersonalEvent::CATEGORIES[$category]['color'];
    }

    public function editEvent(int $id): void
    {
        $event = PersonalEvent::findOrFail($id);
        $this->editingEventId = $event->id;
        $this->eventTitle = $event->title;
        $this->eventCategory = $event->category;
        $this->eventColor = $event->color;
        $this->eventIcon = $event->icon;
        $this->eventStartDate = $event->start_date->toDateString();
        $this->eventEndDate = $event->end_date->toDateString();
        $this->eventNotes = $event->notes ?? '';
        $this->showEventModal = true;
    }

    public function saveEvent(CalendarService $service): void
    {
        $data = $this->validate([
            'eventTitle'     => 'required|string|max:255',
            'eventCategory'  => 'required|in:vacances,excursion,autre',
            'eventColor'     => 'required|string',
            'eventIcon'      => 'required|string',
            'eventStartDate' => 'required|date',
            'eventEndDate'   => 'required|date|after_or_equal:eventStartDate',
        ]);

        $attributes = [
            'title'      => $data['eventTitle'],
            'category'   => $data['eventCategory'],
            'color'      => $data['eventColor'],
            'icon'       => $data['eventIcon'],
            'notes'      => $this->eventNotes !== '' ? $this->eventNotes : null,
            'start_date' => $data['eventStartDate'],
            'end_date'   => $data['eventEndDate'],
        ];

        if ($this->editingEventId !== null) {
            PersonalEvent::findOrFail($this->editingEventId)->update($attributes);
        } else {
            PersonalEvent::create($attributes);
        }

        $this->showEventModal = false;
        $this->resetEventForm();
        $this->loadMonth($service);
        $this->loadDay($service);
    }

    public function deleteEvent(int $id, CalendarService $service): void
    {
        PersonalEvent::findOrFail($id)->delete();
        $this->loadMonth($service);
        $this->loadDay($service);
    }

    public function cancelEventModal(): void
    {
        $this->showEventModal = false;
        $this->resetEventForm();
    }

    private function resetEventForm(): void
    {
        $this->editingEventId = null;
        $this->eventTitle = '';
        $this->eventCategory = 'vacances';
        $this->eventColor = PersonalEvent::CATEGORIES['vacances']['color'];
        $this->eventIcon = PersonalEvent::CATEGORIES['vacances']['icon'];
        $this->eventStartDate = '';
        $this->eventEndDate = '';
        $this->eventNotes = '';
        $this->resetErrorBag();
    }
```

- [ ] **Step 4: Exposer catégories, couleurs et icônes à la vue**

Dans la méthode `render()` de `Calendar.php`, ajouter ces clés au tableau passé à `view('livewire.calendar', [...])` :

```php
            'eventCategories' => array_keys(PersonalEvent::CATEGORIES),
            'eventColors'     => \App\Livewire\TreatmentCreate::COLORS,
            'eventIcons'      => PersonalEvent::ICONS,
```

- [ ] **Step 5: Lancer les tests (passent)**

Run: `php artisan test --filter=CalendarTest`
Expected: PASS (anciens + 5 nouveaux)

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Calendar.php tests/Feature/Livewire/CalendarTest.php
git commit -m "feat(events): add personal event CRUD to Calendar component"
```

---

### Task 5: UI — bouton, rendu du jour et modal dans la vue calendrier

**Goal:** Afficher le bouton « + Événement », rendre les événements personnels (icône + titre + période + Modifier/Supprimer) dans le détail du jour, et fournir le modal de création/édition.

**Files:**
- Modify: `resources/views/livewire/calendar.blade.php`

**Acceptance Criteria:**
- [ ] Un bouton « + Événement » est visible dans le panneau du jour sélectionné.
- [ ] Un événement personnel s'affiche avec son icône, son titre, sa note et, si multi-jours, sa période ; avec boutons Modifier et Supprimer.
- [ ] Un événement de traitement conserve son bouton « Déplacer » et n'affiche jamais Modifier/Supprimer.
- [ ] Le modal permet de saisir titre, catégorie, dates, couleur, icône, note.
- [ ] `php artisan test --filter=CalendarTest` reste vert (rendu sans erreur).

**Verify:** `php artisan test --filter=CalendarTest` → vert ; vérification visuelle de la page calendrier.

**Steps:**

- [ ] **Step 1: Ajouter le bouton « + Événement » dans l'en-tête du panneau du jour**

Dans `resources/views/livewire/calendar.blade.php`, remplacer le bloc titre du panneau du jour (la ligne `<p class="text-xs font-bold ...">{{ \Carbon\Carbon::parse($selectedDate)->isoFormat('dddd D MMMM YYYY') }}</p>`) par :

```blade
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                {{ \Carbon\Carbon::parse($selectedDate)->isoFormat('dddd D MMMM YYYY') }}
            </p>
            <button wire:click="openEventModal"
                    class="text-xs text-sky-600 font-semibold border border-sky-200 rounded-lg px-2 py-1 bg-sky-50 hover:bg-sky-100 transition-colors flex items-center gap-1">
                <span class="text-sm leading-none">+</span> {{ __('events.add') }}
            </button>
        </div>
```

(Supprimer l'ancienne ligne `<p>…</p>` avec `mb-3` qu'elle remplace.)

- [ ] **Step 2: Rendre les événements personnels dans la liste du jour**

Dans la boucle `@foreach($selectedDayEvents as $event)`, remplacer le bloc d'actions final (le `@if(!empty($event['can_move']) && $event['can_move']) … @endif` avec le bouton Déplacer) par un branchement sur `kind`, et adapter le contenu. Remplacer tout le contenu intérieur de la `<div>` de l'événement par :

```blade
            <div class="flex items-start gap-3 px-3 py-2 rounded-xl
                        {{ $event['requires_fasting'] ? 'bg-amber-50 border border-amber-200' : 'bg-slate-50' }}">
                @if(($event['kind'] ?? 'treatment') === 'personal')
                    <span class="text-base leading-none flex-shrink-0 mt-0.5">{{ $event['icon'] }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-slate-800">{{ $event['title'] }}</p>
                        @if(!empty($event['is_multi_day']))
                            <p class="text-xs text-slate-400">
                                {{ __('events.period', [
                                    'start' => \Carbon\Carbon::parse($event['start_date'])->isoFormat('D MMM'),
                                    'end'   => \Carbon\Carbon::parse($event['end_date'])->isoFormat('D MMM'),
                                ]) }}
                            </p>
                        @endif
                        @if(!empty($event['notes']))
                            <p class="text-xs text-slate-400">{{ $event['notes'] }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button wire:click="editEvent({{ $event['id'] }})"
                                class="text-xs text-sky-500 font-semibold border border-sky-200 rounded-lg px-2 py-1 bg-sky-50 hover:bg-sky-100 transition-colors">
                            {{ __('events.edit') }}
                        </button>
                        <button wire:click="deleteEvent({{ $event['id'] }})"
                                wire:confirm="{{ __('events.delete_confirm') }}"
                                class="text-xs text-red-500 font-semibold border border-red-200 rounded-lg px-2 py-1 bg-red-50 hover:bg-red-100 transition-colors">
                            {{ __('events.delete') }}
                        </button>
                    </div>
                @else
                    <span class="w-2 h-2 rounded-full flex-shrink-0 mt-1" style="background-color: {{ $event['color'] }};"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-slate-800">{{ $event['display_name'] ?? $event['name'] }}</p>
                        @if($event['requires_fasting'])
                            <p class="text-xs text-amber-600 font-bold">⚠️ {{ __('calendar.fasting_warning', ['name' => $profileName]) }}</p>
                        @endif
                        @if(!empty($event['notes']))
                            <p class="text-xs text-slate-400">{{ $event['notes'] }}</p>
                        @elseif(isset($event['dose']) && $event['dose'])
                            @foreach(explode(' · ', $event['dose']) as $dosePart)
                            <p class="text-xs text-slate-400">{{ $dosePart }}</p>
                            @endforeach
                        @endif
                        @if(!empty($event['moved']) && $event['moved'])
                            <p class="text-xs text-orange-500 italic">{{ __('calendar.moved_from', ['date' => \Carbon\Carbon::parse($event['original_date'])->isoFormat('D MMM')]) }}</p>
                        @endif
                    </div>
                    @if(!empty($event['can_move']) && $event['can_move'])
                    <button wire:click="openMoveModal({{ $event['id'] }})"
                            class="text-xs text-sky-500 font-semibold border border-sky-200 rounded-lg px-2 py-1 bg-sky-50 hover:bg-sky-100 transition-colors flex-shrink-0">
                        {{ __('calendar.move') }}
                    </button>
                    @endif
                @endif
            </div>
```

- [ ] **Step 3: Ajouter le modal de création/édition**

Juste avant la fermeture finale `</div>` du composant (après le modal de déplacement `@endif`), ajouter :

```blade
    {{-- Modal événement personnel --}}
    @if($showEventModal)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-5 w-full max-w-sm shadow-xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-sm font-bold text-slate-800 mb-4">
                {{ $editingEventId ? __('events.edit_title') : __('events.new_title') }}
            </h3>

            {{-- Titre --}}
            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('events.field_title') }}</label>
            <input type="text" wire:model="eventTitle" placeholder="{{ __('events.field_title_ph') }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400 mb-1">
            @error('eventTitle') <p class="text-xs text-red-500 mb-2">{{ $message }}</p> @enderror

            {{-- Catégorie --}}
            <label class="block text-xs font-semibold text-slate-600 mb-1 mt-3">{{ __('events.field_category') }}</label>
            <div class="grid grid-cols-3 gap-2 mb-3">
                @foreach($eventCategories as $cat)
                <button type="button" wire:click="selectCategory('{{ $cat }}')"
                        class="px-2 py-2 rounded-xl border text-xs font-semibold transition-colors
                               {{ $eventCategory === $cat ? 'bg-sky-100 ring-2 ring-sky-400 text-sky-700 border-sky-200' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                    {{ __('events.category_' . $cat) }}
                </button>
                @endforeach
            </div>

            {{-- Dates --}}
            <div class="grid grid-cols-2 gap-3 mb-1">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('events.field_start') }}</label>
                    <x-datepicker model="eventStartDate" :value="$eventStartDate" wire:key="ev-start-{{ $editingEventId ?? 'new' }}" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('events.field_end') }}</label>
                    <x-datepicker model="eventEndDate" :value="$eventEndDate" wire:key="ev-end-{{ $editingEventId ?? 'new' }}" />
                </div>
            </div>
            @error('eventEndDate') <p class="text-xs text-red-500 mb-2">{{ $message }}</p> @enderror

            {{-- Couleur --}}
            <label class="block text-xs font-semibold text-slate-600 mb-1 mt-3">{{ __('events.field_color') }}</label>
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach($eventColors as $c)
                <button type="button" wire:click="$set('eventColor', '{{ $c }}')"
                        class="w-7 h-7 rounded-full transition-transform {{ $eventColor === $c ? 'ring-2 ring-offset-2 ring-slate-400 scale-110' : '' }}"
                        style="background-color: {{ $c }};"></button>
                @endforeach
            </div>

            {{-- Icône --}}
            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('events.field_icon') }}</label>
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach($eventIcons as $icon)
                <button type="button" wire:click="$set('eventIcon', '{{ $icon }}')"
                        class="w-9 h-9 rounded-xl flex items-center justify-center text-lg transition-colors
                               {{ $eventIcon === $icon ? 'bg-sky-100 ring-2 ring-sky-400' : 'bg-slate-100 hover:bg-slate-200' }}">
                    {{ $icon }}
                </button>
                @endforeach
            </div>

            {{-- Note --}}
            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('events.field_notes') }}</label>
            <textarea wire:model="eventNotes" rows="2" placeholder="{{ __('events.field_notes_ph') }}"
                      class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400 mb-4"></textarea>

            {{-- Actions --}}
            <div class="flex gap-3">
                <button wire:click="cancelEventModal"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    {{ __('common.cancel') }}
                </button>
                <button wire:click="saveEvent"
                        class="flex-1 py-2.5 rounded-xl bg-sky-500 text-sm font-semibold text-white hover:bg-sky-600 transition-colors">
                    {{ __('events.save') }}
                </button>
            </div>
        </div>
    </div>
    @endif
```

- [ ] **Step 4: Lancer les tests + vérifier le rendu**

Run: `php artisan test --filter=CalendarTest`
Expected: PASS

Vérification visuelle : lancer l'app, ouvrir le calendrier, sélectionner un jour, créer un événement « Vacances » sur plusieurs jours, vérifier les points dans la grille et l'affichage dans le détail du jour, puis modifier et supprimer.

- [ ] **Step 5: Commit**

```bash
git add resources/views/livewire/calendar.blade.php
git commit -m "feat(events): add personal event UI (button, day list, modal)"
```

---

## Vérification finale

- [ ] `php artisan test` → toute la suite verte.
- [ ] Création, édition, suppression d'un événement multi-jours fonctionnent depuis le calendrier.
- [ ] Points colorés présents sur chaque jour de la plage ; isolation par profil respectée.
- [ ] Parité des traductions FR/EN.
