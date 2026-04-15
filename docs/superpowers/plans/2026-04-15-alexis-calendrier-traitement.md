# Alexis — Calendrier de traitement : Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers-extended-cc:subagent-driven-development (recommended) or superpowers-extended-cc:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construire une application Android NativePHP permettant de visualiser et gérer le calendrier de traitement d'Alexis (6-MP, 6-TG, MTX, VCR, IT MTTX) du 26 novembre 2025 au 31 mars 2027.

**Architecture:** Laravel 11 + NativePHP Mobile (serveur PHP embarqué dans un APK Android, UI via WebView). Livewire 3 gère la réactivité, Alpine.js les micro-interactions, Tailwind CSS + Preline l'UI. SQLite pour la persistance locale.

**Tech Stack:** PHP 8.4 · Laravel 11 · NativePHP Mobile · Livewire 3 · Alpine.js · Tailwind CSS · Preline UI · SQLite · Pest (tests)

---

## Structure des fichiers

```
app/
  Models/
    Treatment.php             — Eloquent model: traitements + règles de récurrence
    PosologyHistory.php       — Eloquent model: historique des posologies
    CalendarEvent.php         — Eloquent model: événements du calendrier (stockés)
    Setting.php               — Eloquent model: paramètres clé/valeur
  Services/
    CalendarService.php       — Calculs: compteurs widgets, événements du jour
    ExportService.php         — Génération JSON pour export
  Livewire/
    Dashboard.php             — Composant Livewire: onglet Accueil
    Calendar.php              — Composant Livewire: onglet Calendrier
    Treatments.php            — Composant Livewire: onglet Traitements (liste)
    TreatmentEdit.php         — Composant Livewire: modifier posologie + historique
database/
  migrations/
    xxxx_create_settings_table.php
    xxxx_create_treatments_table.php
    xxxx_create_posology_history_table.php
    xxxx_create_calendar_events_table.php
  seeders/
    DatabaseSeeder.php
    TreatmentSeeder.php       — Insère les 6 traitements
    CalendarSeeder.php        — Génère tous les calendar_events
resources/
  views/
    layouts/app.blade.php     — Layout principal avec bottom tab bar
    livewire/
      dashboard.blade.php
      calendar.blade.php
      treatments.blade.php
      treatment-edit.blade.php
routes/
  web.php                     — Routes: /, /calendar, /treatments, /treatments/{id}/edit
tests/
  Unit/
    Services/
      CalendarServiceTest.php
      ExportServiceTest.php
  Feature/
    Livewire/
      DashboardTest.php
      CalendarTest.php
      TreatmentsTest.php
```

---

## Task 0 : Scaffold Laravel + NativePHP Mobile + frontend

**Goal:** Projet Laravel 11 opérationnel avec NativePHP Mobile, Livewire 3, Tailwind CSS et Preline configurés.

**Files:**
- Create: `composer.json`, `package.json`, `tailwind.config.js`, `vite.config.js`
- Create: `resources/css/app.css`
- Create: `resources/js/app.js`

**Acceptance Criteria:**
- [ ] `php8.4 artisan --version` retourne Laravel 11.x
- [ ] `php8.4 artisan native:run android` lance l'app en mode développement sans erreur
- [ ] `npm run dev` compile sans erreur et Preline est disponible
- [ ] `php8.4 artisan test` passe (0 tests, 0 échecs)

**Verify:** `php8.4 artisan --version` → `Laravel Framework 11.x.x`

**Steps:**

- [ ] **Step 1 : Créer le projet Laravel dans le répertoire courant**

```bash
cd /Users/jonathan/Documents/GitHub/PrestaEdit/Alexis
composer create-project laravel/laravel . --prefer-dist
```

> Note : si `composer` utilise PHP 8.1 par défaut, forcer avec `PHP=/usr/local/bin/php8.4 composer create-project laravel/laravel .`

- [ ] **Step 2 : Installer NativePHP Mobile**

```bash
composer require nativephp/mobile
php8.4 artisan native:install
```

Lire attentivement la sortie de `native:install` — il peut demander des confirmations. Accepter toutes les valeurs par défaut pour le moment.

> Référence officielle : https://nativephp.com/docs/mobile/1/getting-started/installation

- [ ] **Step 3 : Installer Livewire 3**

```bash
composer require livewire/livewire
php8.4 artisan livewire:publish --config
```

- [ ] **Step 4 : Installer les dépendances frontend**

```bash
npm install
npm install preline
npm install -D @tailwindcss/forms
```

- [ ] **Step 5 : Configurer Tailwind CSS avec Preline**

Remplacer le contenu de `tailwind.config.js` :

```js
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './node_modules/preline/dist/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd',
                    300: '#7dd3fc', 400: '#38bdf8', 500: '#0ea5e9',
                    600: '#0284c7', 700: '#0369a1',
                },
            },
        },
    },
    plugins: [forms],
};
```

- [ ] **Step 6 : Configurer `resources/css/app.css`**

```css
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
@tailwind base;
@tailwind components;
@tailwind utilities;
```

- [ ] **Step 7 : Configurer `resources/js/app.js`**

```js
import './bootstrap';
import 'preline';
```

- [ ] **Step 8 : Configurer la base de données SQLite**

Dans `.env`, vérifier :

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

Créer le fichier SQLite :

```bash
touch database/database.sqlite
```

- [ ] **Step 9 : Vérification**

```bash
npm run build
php8.4 artisan test
```

Attendu : build sans erreur, 0 tests passés (normal à ce stade).

- [ ] **Step 10 : Commit**

```bash
git init
git add .
git commit -m "feat: scaffold Laravel 11 + NativePHP Mobile + Livewire + Tailwind + Preline"
```

---

## Task 1 : Migrations et modèles Eloquent

**Goal:** Les 4 tables (`settings`, `treatments`, `posology_history`, `calendar_events`) sont créées en base avec leurs modèles Eloquent et relations testées.

**Files:**
- Create: `database/migrations/xxxx_create_settings_table.php`
- Create: `database/migrations/xxxx_create_treatments_table.php`
- Create: `database/migrations/xxxx_create_posology_history_table.php`
- Create: `database/migrations/xxxx_create_calendar_events_table.php`
- Create: `app/Models/Setting.php`
- Create: `app/Models/Treatment.php`
- Create: `app/Models/PosologyHistory.php`
- Create: `app/Models/CalendarEvent.php`
- Create: `tests/Unit/Models/TreatmentTest.php`

**Acceptance Criteria:**
- [ ] `php8.4 artisan migrate` passe sans erreur
- [ ] `Treatment::find(1)` retourne un objet avec les bons attributs
- [ ] `Treatment::find(1)->posologyHistory` retourne une collection
- [ ] `Treatment::find(1)->calendarEvents` retourne une collection
- [ ] Tests unitaires des modèles passent

**Verify:** `php8.4 artisan test --filter ModelTest` → 1 test suite, tous verts

**Steps:**

- [ ] **Step 1 : Créer les migrations**

```bash
php8.4 artisan make:migration create_settings_table
php8.4 artisan make:migration create_treatments_table
php8.4 artisan make:migration create_posology_history_table
php8.4 artisan make:migration create_calendar_events_table
```

- [ ] **Step 2 : Remplir la migration `settings`**

```php
public function up(): void
{
    Schema::create('settings', function (Blueprint $table) {
        $table->string('key')->primary();
        $table->string('value');
        $table->timestamps();
    });
}
```

- [ ] **Step 3 : Remplir la migration `treatments`**

```php
public function up(): void
{
    Schema::create('treatments', function (Blueprint $table) {
        $table->id();
        $table->string('name');                    // ex: "6-MP"
        $table->string('commercial_name')->nullable(); // ex: "Purinéthol"
        $table->enum('type', ['daily', 'weekly', 'cyclic', 'medical_act']);
        $table->string('unit')->nullable();        // "cachet", "ml", "IV"
        $table->decimal('current_dose', 8, 2)->nullable();
        $table->string('color', 7)->default('#6b7280'); // hex
        $table->unsignedTinyInteger('frequency_weeks')->nullable();
        $table->unsignedTinyInteger('day_of_week')->nullable(); // 0=lun…6=dim
        $table->date('recurrence_start')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });
}
```

- [ ] **Step 4 : Remplir la migration `posology_history`**

```php
public function up(): void
{
    Schema::create('posology_history', function (Blueprint $table) {
        $table->id();
        $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
        $table->decimal('dose', 8, 2);
        $table->text('note')->nullable();
        $table->date('started_at');
        $table->timestamps();
    });
}
```

- [ ] **Step 5 : Remplir la migration `calendar_events`**

```php
public function up(): void
{
    Schema::create('calendar_events', function (Blueprint $table) {
        $table->id();
        $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
        $table->date('scheduled_date');
        $table->date('original_date')->nullable();
        $table->boolean('is_cancelled')->default(false);
        $table->text('notes')->nullable();
        $table->timestamps();

        $table->index(['scheduled_date', 'is_cancelled']);
        $table->index('treatment_id');
    });
}
```

- [ ] **Step 6 : Créer `app/Models/Setting.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, string $default = ''): string
    {
        return static::find($key)?->value ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
```

- [ ] **Step 7 : Créer `app/Models/Treatment.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treatment extends Model
{
    protected $fillable = [
        'name', 'commercial_name', 'type', 'unit', 'current_dose',
        'color', 'frequency_weeks', 'day_of_week', 'recurrence_start', 'notes',
    ];

    protected $casts = [
        'current_dose' => 'decimal:2',
        'recurrence_start' => 'date',
        'frequency_weeks' => 'integer',
        'day_of_week' => 'integer',
    ];

    public function posologyHistory(): HasMany
    {
        return $this->hasMany(PosologyHistory::class)->orderByDesc('started_at');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function isDaily(): bool
    {
        return $this->type === 'daily';
    }

    public function isDosageEditable(): bool
    {
        return $this->type !== 'medical_act';
    }
}
```

- [ ] **Step 8 : Créer `app/Models/PosologyHistory.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosologyHistory extends Model
{
    protected $table = 'posology_history';

    protected $fillable = ['treatment_id', 'dose', 'note', 'started_at'];

    protected $casts = [
        'dose' => 'decimal:2',
        'started_at' => 'date',
    ];

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }
}
```

- [ ] **Step 9 : Créer `app/Models/CalendarEvent.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    protected $fillable = [
        'treatment_id', 'scheduled_date', 'original_date', 'is_cancelled', 'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'original_date' => 'date',
        'is_cancelled' => 'boolean',
    ];

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function hasMoved(): bool
    {
        return $this->original_date !== null;
    }
}
```

- [ ] **Step 10 : Écrire les tests unitaires `tests/Unit/Models/TreatmentTest.php`**

```php
<?php

use App\Models\Treatment;
use App\Models\PosologyHistory;
use App\Models\CalendarEvent;

it('isDaily returns true for daily treatments', function () {
    $treatment = new Treatment(['type' => 'daily']);
    expect($treatment->isDaily())->toBeTrue();
});

it('isDaily returns false for non-daily treatments', function () {
    $treatment = new Treatment(['type' => 'weekly']);
    expect($treatment->isDaily())->toBeFalse();
});

it('isDosageEditable returns false for medical_act', function () {
    $treatment = new Treatment(['type' => 'medical_act']);
    expect($treatment->isDosageEditable())->toBeFalse();
});

it('isDosageEditable returns true for daily treatments', function () {
    $treatment = new Treatment(['type' => 'daily']);
    expect($treatment->isDosageEditable())->toBeTrue();
});

it('hasMoved returns true when original_date is set', function () {
    $event = new CalendarEvent(['original_date' => now()->toDateString()]);
    expect($event->hasMoved())->toBeTrue();
});
```

- [ ] **Step 11 : Migrer et tester**

```bash
php8.4 artisan migrate
php8.4 artisan test --filter TreatmentTest
```

Attendu : 5 tests passés.

- [ ] **Step 12 : Commit**

```bash
git add database/migrations app/Models tests/Unit/Models
git commit -m "feat: add migrations and Eloquent models (Treatment, PosologyHistory, CalendarEvent, Setting)"
```

---

## Task 2 : Seeders — traitements et calendrier initial

**Goal:** La base de données est peuplée avec les 6 traitements et tous les événements calendrier générés correctement pour la période nov. 2025 → mars 2027.

**Files:**
- Create: `database/seeders/TreatmentSeeder.php`
- Create: `database/seeders/CalendarSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Create: `tests/Unit/Seeders/CalendarSeederTest.php`

**Acceptance Criteria:**
- [ ] `php8.4 artisan db:seed` passe sans erreur
- [ ] 6 traitements insérés dans `treatments`
- [ ] Les visites hôpital (toutes les 2 sem. depuis 26/11/2025) génèrent 35 événements
- [ ] Les VCR (toutes les 4 sem. depuis 26/11/2025) génèrent 18 événements
- [ ] Les IT MTTX (toutes les 8 sem. depuis 21/01/2026) génèrent 8 événements
- [ ] Les MTX (tous les mardis sauf jours IT MTTX) génèrent le bon nombre d'événements
- [ ] Aucun MTX le même jour qu'un IT MTTX
- [ ] La posologie initiale de 6-TG (2,8 ml depuis 26/11/2025) est dans `posology_history`

**Verify:** `php8.4 artisan test --filter SeederTest` → tous verts

**Steps:**

- [ ] **Step 1 : Créer `database/seeders/TreatmentSeeder.php`**

```php
<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Treatment;
use App\Models\PosologyHistory;
use Illuminate\Database\Seeder;

class TreatmentSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('treatment_start', '2025-11-26');
        Setting::set('treatment_end', '2027-03-31');

        $treatments = [
            [
                'name' => '6-MP',
                'commercial_name' => 'Purinéthol',
                'type' => 'daily',
                'unit' => 'cachet',
                'current_dose' => 1.00,
                'color' => '#3b82f6',
                'frequency_weeks' => null,
                'day_of_week' => null,
                'recurrence_start' => null,
            ],
            [
                'name' => '6-TG',
                'commercial_name' => 'Lanvis',
                'type' => 'daily',
                'unit' => 'ml',
                'current_dose' => 3.00,
                'color' => '#10b981',
                'frequency_weeks' => null,
                'day_of_week' => null,
                'recurrence_start' => null,
            ],
            [
                'name' => 'MTX',
                'commercial_name' => 'Méthotrexate',
                'type' => 'weekly',
                'unit' => 'cachet',
                'current_dose' => 9.00,
                'color' => '#ef4444',
                'frequency_weeks' => null,
                'day_of_week' => 1, // mardi (0=lun)
                'recurrence_start' => null,
            ],
            [
                'name' => 'VCR',
                'commercial_name' => 'Vincristine',
                'type' => 'cyclic',
                'unit' => 'IV',
                'current_dose' => null,
                'color' => '#8b5cf6',
                'frequency_weeks' => 4,
                'day_of_week' => null,
                'recurrence_start' => '2025-11-26',
            ],
            [
                'name' => 'IT MTTX',
                'commercial_name' => 'Ponction lombaire',
                'type' => 'medical_act',
                'unit' => null,
                'current_dose' => null,
                'color' => '#0ea5e9',
                'frequency_weeks' => 8,
                'day_of_week' => null,
                'recurrence_start' => '2026-01-21',
            ],
            [
                'name' => 'Hôpital',
                'commercial_name' => 'Visite hôpital',
                'type' => 'cyclic',
                'unit' => null,
                'current_dose' => null,
                'color' => '#f97316',
                'frequency_weeks' => 2,
                'day_of_week' => null,
                'recurrence_start' => '2025-11-26',
            ],
        ];

        foreach ($treatments as $data) {
            $treatment = Treatment::create($data);
        }

        // Posologie initiale 6-TG : 2,8 ml depuis le 26/11/2025
        $sixTg = Treatment::where('name', '6-TG')->first();
        PosologyHistory::create([
            'treatment_id' => $sixTg->id,
            'dose' => 2.80,
            'note' => 'Posologie initiale',
            'started_at' => '2025-11-26',
        ]);
        PosologyHistory::create([
            'treatment_id' => $sixTg->id,
            'dose' => 3.00,
            'note' => 'Passage à 3ml suite RDV',
            'started_at' => '2026-04-15',
        ]);
    }
}
```

- [ ] **Step 2 : Créer `database/seeders/CalendarSeeder.php`**

```php
<?php

namespace Database\Seeders;

use App\Models\Treatment;
use App\Models\CalendarEvent;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        $start = Carbon::parse(Setting::get('treatment_start'));
        $end = Carbon::parse(Setting::get('treatment_end'));

        // 1. Générer les événements cycliques (Hôpital, VCR, IT MTTX)
        $cyclicTreatments = Treatment::whereIn('type', ['cyclic', 'medical_act'])
            ->whereNotNull('frequency_weeks')
            ->whereNotNull('recurrence_start')
            ->get();

        foreach ($cyclicTreatments as $treatment) {
            $current = Carbon::parse($treatment->recurrence_start);
            while ($current->lte($end)) {
                CalendarEvent::create([
                    'treatment_id' => $treatment->id,
                    'scheduled_date' => $current->toDateString(),
                ]);
                $current->addWeeks($treatment->frequency_weeks);
            }
        }

        // 2. Récupérer les dates IT MTTX générées (pour exclure les mardis MTX)
        $itMttx = Treatment::where('name', 'IT MTTX')->first();
        $itMttxDates = CalendarEvent::where('treatment_id', $itMttx->id)
            ->pluck('scheduled_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        // 3. Générer les MTX (tous les mardis sauf jours IT MTTX)
        $mtx = Treatment::where('name', 'MTX')->first();
        $current = $start->copy()->startOfWeek(Carbon::MONDAY);

        while ($current->lte($end)) {
            // day_of_week=1 => mardi (Carbon: MONDAY=1, TUESDAY=2... mais notre convention 0=lun donc +1)
            $tuesday = $current->copy()->addDay($mtx->day_of_week); // 0=lun, 1=mar => addDay(1)

            if ($tuesday->gte($start) && $tuesday->lte($end)) {
                $dateStr = $tuesday->toDateString();
                if (!in_array($dateStr, $itMttxDates)) {
                    CalendarEvent::create([
                        'treatment_id' => $mtx->id,
                        'scheduled_date' => $dateStr,
                    ]);
                }
            }
            $current->addWeek();
        }
    }
}
```

> **Note :** `day_of_week` dans la table stocke 0=lundi, 1=mardi… Pour obtenir le mardi à partir du début de la semaine (lundi), on fait `startOfWeek(MONDAY)->addDay(day_of_week)`.

- [ ] **Step 3 : Mettre à jour `DatabaseSeeder.php`**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TreatmentSeeder::class,
            CalendarSeeder::class,
        ]);
    }
}
```

- [ ] **Step 4 : Écrire les tests `tests/Unit/Seeders/CalendarSeederTest.php`**

```php
<?php

use App\Models\Treatment;
use App\Models\CalendarEvent;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('seeds 6 treatments', function () {
    expect(Treatment::count())->toBe(6);
});

it('generates 35 hospital visits', function () {
    $hopital = Treatment::where('name', 'Hôpital')->first();
    expect(CalendarEvent::where('treatment_id', $hopital->id)->count())->toBe(35);
});

it('generates 18 VCR events', function () {
    $vcr = Treatment::where('name', 'VCR')->first();
    expect(CalendarEvent::where('treatment_id', $vcr->id)->count())->toBe(18);
});

it('generates 8 IT MTTX events', function () {
    $itMttx = Treatment::where('name', 'IT MTTX')->first();
    expect(CalendarEvent::where('treatment_id', $itMttx->id)->count())->toBe(8);
});

it('never creates MTX on an IT MTTX day', function () {
    $mtx = Treatment::where('name', 'MTX')->first();
    $itMttx = Treatment::where('name', 'IT MTTX')->first();

    $mtxDates = CalendarEvent::where('treatment_id', $mtx->id)->pluck('scheduled_date')->toArray();
    $itDates = CalendarEvent::where('treatment_id', $itMttx->id)->pluck('scheduled_date')->toArray();

    $overlap = array_intersect($mtxDates, $itDates);
    expect($overlap)->toBeEmpty();
});

it('all MTX events fall on a Tuesday', function () {
    $mtx = Treatment::where('name', 'MTX')->first();
    $nonTuesdays = CalendarEvent::where('treatment_id', $mtx->id)
        ->get()
        ->filter(fn($e) => $e->scheduled_date->dayOfWeek !== 2) // Carbon TUESDAY = 2
        ->count();
    expect($nonTuesdays)->toBe(0);
});

it('seeds 6-TG posology history with 2 entries', function () {
    $sixTg = Treatment::where('name', '6-TG')->first();
    expect($sixTg->posologyHistory->count())->toBe(2);
    expect($sixTg->posologyHistory->first()->dose)->toBe('3.00');
});
```

- [ ] **Step 5 : Lancer les seeds et les tests**

```bash
php8.4 artisan migrate:fresh --seed
php8.4 artisan test --filter SeederTest
```

Attendu : 6 tests passés.

- [ ] **Step 6 : Commit**

```bash
git add database/seeders tests/Unit/Seeders
git commit -m "feat: add TreatmentSeeder and CalendarSeeder with full event generation"
```

---

## Task 3 : CalendarService — logique métier et compteurs

**Goal:** `CalendarService` expose les méthodes pour calculer les compteurs widgets, récupérer les événements d'un jour, et la liste d'un mois, toutes couvertes par des tests.

**Files:**
- Create: `app/Services/CalendarService.php`
- Create: `tests/Unit/Services/CalendarServiceTest.php`

**Acceptance Criteria:**
- [ ] `getCounters(today)` retourne le bon nombre de visites, VCR, IT MTTX, MTX restants
- [ ] `getEventsForDay(date)` retourne les événements ponctuels + les traitements quotidiens
- [ ] `getEventsForMonth(year, month)` retourne un tableau indexé par date
- [ ] `getNextHospitalVisit(today)` retourne la prochaine date de visite hôpital
- [ ] `getDaysRemaining(today)` retourne le nombre de jours jusqu'au 31/03/2027
- [ ] Tous les tests passent

**Verify:** `php8.4 artisan test --filter CalendarServiceTest` → tous verts

**Steps:**

- [ ] **Step 1 : Écrire les tests en premier**

```php
<?php
// tests/Unit/Services/CalendarServiceTest.php

use App\Services\CalendarService;
use App\Models\Treatment;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->service = app(CalendarService::class);
});

it('returns correct days remaining from a known date', function () {
    // 15 avril 2026 → 31 mars 2027 = 350 jours
    $remaining = $this->service->getDaysRemaining(Carbon::parse('2026-04-15'));
    expect($remaining)->toBe(350);
});

it('returns correct hospital visit count from a date', function () {
    // À partir du 16 avril 2026 (après la visite du 15), il reste 24 visites
    $counters = $this->service->getCounters(Carbon::parse('2026-04-16'));
    expect($counters['hospital'])->toBe(24);
});

it('returns next hospital visit', function () {
    $next = $this->service->getNextHospitalVisit(Carbon::parse('2026-04-15'));
    expect($next->toDateString())->toBe('2026-04-29');
});

it('getEventsForDay includes daily treatments', function () {
    $events = $this->service->getEventsForDay(Carbon::parse('2026-04-16'));
    $names = collect($events)->pluck('name')->toArray();
    expect($names)->toContain('6-MP');
    expect($names)->toContain('6-TG');
});

it('getEventsForDay includes hospital visit on visit day', function () {
    $events = $this->service->getEventsForDay(Carbon::parse('2026-04-29'));
    $names = collect($events)->pluck('name')->toArray();
    expect($names)->toContain('Hôpital');
});

it('getEventsForDay does not include cancelled events', function () {
    $hopital = Treatment::where('name', 'Hôpital')->first();
    CalendarEvent::where('treatment_id', $hopital->id)
        ->where('scheduled_date', '2026-04-29')
        ->update(['is_cancelled' => true]);

    $events = $this->service->getEventsForDay(Carbon::parse('2026-04-29'));
    $names = collect($events)->pluck('name')->toArray();
    expect($names)->not->toContain('Hôpital');
});

it('getEventsForDay flags IT MTTX as requiring fasting', function () {
    // Le 13 mai 2026 est un jour IT MTTX
    $events = $this->service->getEventsForDay(Carbon::parse('2026-05-13'));
    $itMttx = collect($events)->firstWhere('name', 'IT MTTX');
    expect($itMttx['requires_fasting'])->toBeTrue();
});

it('getEventsForMonth returns array indexed by date string', function () {
    $month = $this->service->getEventsForMonth(2026, 4);
    expect($month)->toBeArray();
    expect($month)->toHaveKey('2026-04-15'); // visite + VCR
});
```

- [ ] **Step 2 : Créer `app/Services/CalendarService.php`**

```php
<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Setting;
use App\Models\Treatment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarService
{
    public function getDaysRemaining(Carbon $from): int
    {
        $end = Carbon::parse(Setting::get('treatment_end', '2027-03-31'));
        return (int) $from->copy()->startOfDay()->diffInDays($end->copy()->startOfDay(), false);
    }

    public function getCounters(Carbon $from): array
    {
        $fromDate = $from->toDateString();

        $countFor = function (string $treatmentName) use ($fromDate): int {
            $treatment = Treatment::where('name', $treatmentName)->first();
            if (!$treatment) return 0;
            return CalendarEvent::where('treatment_id', $treatment->id)
                ->where('scheduled_date', '>', $fromDate)
                ->where('is_cancelled', false)
                ->count();
        };

        return [
            'hospital' => $countFor('Hôpital'),
            'vcr' => $countFor('VCR'),
            'it_mttx' => $countFor('IT MTTX'),
            'mtx' => $countFor('MTX'),
        ];
    }

    public function getNextHospitalVisit(Carbon $from): ?Carbon
    {
        $hopital = Treatment::where('name', 'Hôpital')->first();
        if (!$hopital) return null;

        $event = CalendarEvent::where('treatment_id', $hopital->id)
            ->where('scheduled_date', '>', $from->toDateString())
            ->where('is_cancelled', false)
            ->orderBy('scheduled_date')
            ->first();

        return $event ? Carbon::parse($event->scheduled_date) : null;
    }

    public function getEventsForDay(Carbon $date): array
    {
        $dateStr = $date->toDateString();
        $events = [];

        // Traitements quotidiens (non stockés en base)
        $dailyTreatments = Treatment::where('type', 'daily')->with('posologyHistory')->get();
        foreach ($dailyTreatments as $treatment) {
            $events[] = [
                'id' => null,
                'treatment_id' => $treatment->id,
                'name' => $treatment->name,
                'commercial_name' => $treatment->commercial_name,
                'type' => 'daily',
                'unit' => $treatment->unit,
                'dose' => $this->getDoseForDate($treatment, $date),
                'color' => $treatment->color,
                'requires_fasting' => false,
                'can_move' => false,
                'moved' => false,
            ];
        }

        // Événements ponctuels du jour
        $calendarEvents = CalendarEvent::with('treatment')
            ->where('scheduled_date', $dateStr)
            ->where('is_cancelled', false)
            ->get();

        foreach ($calendarEvents as $event) {
            $events[] = [
                'id' => $event->id,
                'treatment_id' => $event->treatment_id,
                'name' => $event->treatment->name,
                'commercial_name' => $event->treatment->commercial_name,
                'type' => $event->treatment->type,
                'unit' => $event->treatment->unit,
                'dose' => $event->treatment->current_dose,
                'color' => $event->treatment->color,
                'requires_fasting' => $event->treatment->name === 'IT MTTX',
                'can_move' => true,
                'moved' => $event->hasMoved(),
                'original_date' => $event->original_date?->toDateString(),
                'notes' => $event->notes,
            ];
        }

        return $events;
    }

    public function getEventsForMonth(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $events = CalendarEvent::with('treatment')
            ->whereBetween('scheduled_date', [$start->toDateString(), $end->toDateString()])
            ->where('is_cancelled', false)
            ->get()
            ->groupBy(fn($e) => $e->scheduled_date->toDateString());

        return $events->map(fn($dayEvents) => $dayEvents->map(fn($e) => [
            'treatment_id' => $e->treatment_id,
            'name' => $e->treatment->name,
            'color' => $e->treatment->color,
            'requires_fasting' => $e->treatment->name === 'IT MTTX',
        ])->values()->toArray())->toArray();
    }

    private function getDoseForDate(Treatment $treatment, Carbon $date): ?string
    {
        $history = $treatment->posologyHistory
            ->filter(fn($h) => $h->started_at->lte($date))
            ->first();

        $dose = $history ? $history->dose : $treatment->current_dose;

        return $dose !== null ? "{$dose} {$treatment->unit}" : null;
    }
}
```

- [ ] **Step 3 : Enregistrer le service dans `AppServiceProvider`**

Dans `app/Providers/AppServiceProvider.php`, méthode `register()` :

```php
$this->app->singleton(\App\Services\CalendarService::class);
```

- [ ] **Step 4 : Lancer les tests**

```bash
php8.4 artisan test --filter CalendarServiceTest
```

Attendu : 8 tests passés.

- [ ] **Step 5 : Commit**

```bash
git add app/Services/CalendarService.php app/Providers/AppServiceProvider.php tests/Unit/Services/CalendarServiceTest.php
git commit -m "feat: add CalendarService with counters, day events, and month view logic"
```

---

## Task 4 : Layout principal + bottom tab bar

**Goal:** Le layout Blade principal avec la bottom tab bar (Accueil / Calendrier / Traitements) est en place et les routes fonctionnent.

**Files:**
- Create: `resources/views/layouts/app.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/NavigationTest.php`

**Acceptance Criteria:**
- [ ] GET `/` retourne 200
- [ ] GET `/calendar` retourne 200
- [ ] GET `/treatments` retourne 200
- [ ] La tab bar met en surbrillance l'onglet actif
- [ ] Le layout inclut Tailwind CSS, Alpine.js et Preline

**Verify:** `php8.4 artisan test --filter NavigationTest` → 3 tests verts

**Steps:**

- [ ] **Step 1 : Définir les routes dans `routes/web.php`**

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('livewire.dashboard'))->name('home');
Route::get('/calendar', fn() => view('livewire.calendar'))->name('calendar');
Route::get('/treatments', fn() => view('livewire.treatments'))->name('treatments');
Route::get('/treatments/{treatment}/edit', fn(\App\Models\Treatment $treatment) => view('livewire.treatment-edit', compact('treatment')))->name('treatments.edit');
```

- [ ] **Step 2 : Créer `resources/views/layouts/app.blade.php`**

```html
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? 'Alexis' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-slate-50 font-sans antialiased">

    <!-- Contenu principal avec padding bottom pour la tab bar -->
    <main class="min-h-full pb-20">
        {{ $slot }}
    </main>

    <!-- Bottom tab bar -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 z-50">
        <div class="flex justify-around items-center h-16 max-w-lg mx-auto px-4">

            <!-- Accueil -->
            <a href="{{ route('home') }}"
               class="flex flex-col items-center gap-1 px-4 py-2 rounded-xl transition-colors
                      {{ request()->routeIs('home') ? 'text-sky-500' : 'text-slate-400 hover:text-slate-600' }}">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
                <span class="text-xs font-medium">Accueil</span>
            </a>

            <!-- Calendrier -->
            <a href="{{ route('calendar') }}"
               class="flex flex-col items-center gap-1 px-4 py-2 rounded-xl transition-colors
                      {{ request()->routeIs('calendar') ? 'text-sky-500' : 'text-slate-400 hover:text-slate-600' }}">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                </svg>
                <span class="text-xs font-medium">Calendrier</span>
            </a>

            <!-- Traitements -->
            <a href="{{ route('treatments') }}"
               class="flex flex-col items-center gap-1 px-4 py-2 rounded-xl transition-colors
                      {{ request()->routeIs('treatments*') ? 'text-sky-500' : 'text-slate-400 hover:text-slate-600' }}">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h4a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                </svg>
                <span class="text-xs font-medium">Traitements</span>
            </a>

        </div>
    </nav>

    @livewireScripts
    <script>
        // Empêcher le scroll rebond iOS/Android sur la WebView
        document.body.style.overscrollBehavior = 'none';
    </script>
</body>
</html>
```

- [ ] **Step 3 : Créer des vues stub pour les 3 onglets** (contenus temporaires pour les tests de navigation)

```bash
mkdir -p resources/views/livewire
```

`resources/views/livewire/dashboard.blade.php` :
```html
<x-layouts.app title="Accueil">
    <div class="p-4"><p>Dashboard</p></div>
</x-layouts.app>
```

`resources/views/livewire/calendar.blade.php` :
```html
<x-layouts.app title="Calendrier">
    <div class="p-4"><p>Calendrier</p></div>
</x-layouts.app>
```

`resources/views/livewire/treatments.blade.php` :
```html
<x-layouts.app title="Traitements">
    <div class="p-4"><p>Traitements</p></div>
</x-layouts.app>
```

`resources/views/livewire/treatment-edit.blade.php` :
```html
<x-layouts.app title="Modifier">
    <div class="p-4"><p>Modifier posologie</p></div>
</x-layouts.app>
```

- [ ] **Step 4 : Écrire les tests de navigation**

```php
<?php
// tests/Feature/NavigationTest.php

it('home route returns 200', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);
});

it('calendar route returns 200', function () {
    $response = $this->get(route('calendar'));
    $response->assertStatus(200);
});

it('treatments route returns 200', function () {
    $response = $this->get(route('treatments'));
    $response->assertStatus(200);
});
```

- [ ] **Step 5 : Lancer les tests**

```bash
php8.4 artisan test --filter NavigationTest
```

Attendu : 3 tests passés.

- [ ] **Step 6 : Commit**

```bash
git add routes/web.php resources/views/layouts resources/views/livewire tests/Feature/NavigationTest.php
git commit -m "feat: add main layout with bottom tab bar and routes"
```

---

## Task 5 : Onglet Accueil — composant Livewire Dashboard

**Goal:** L'onglet Accueil affiche la bannière du prochain RDV, la barre de progression, les 4 widgets compteurs et la liste des traitements du jour, avec les bonnes valeurs calculées dynamiquement.

**Files:**
- Create: `app/Livewire/Dashboard.php`
- Modify: `resources/views/livewire/dashboard.blade.php`
- Create: `tests/Feature/Livewire/DashboardTest.php`

**Acceptance Criteria:**
- [ ] Le composant affiche les 4 widgets avec les bons chiffres (basés sur `CalendarService`)
- [ ] La bannière affiche la date du prochain RDV hôpital
- [ ] La barre de progression reflète l'avancement réel
- [ ] La section "Aujourd'hui" liste les traitements du jour
- [ ] Le bouton export est présent
- [ ] Les tests Livewire passent

**Verify:** `php8.4 artisan test --filter DashboardTest` → tous verts

**Steps:**

- [ ] **Step 1 : Créer `app/Livewire/Dashboard.php`**

```php
<?php

namespace App\Livewire;

use App\Services\CalendarService;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public array $counters = [];
    public array $todayEvents = [];
    public ?string $nextHospitalDate = null;
    public int $daysRemaining = 0;
    public int $progressPercent = 0;

    public function mount(CalendarService $service): void
    {
        $today = Carbon::today();
        $this->counters = $service->getCounters($today);
        $this->todayEvents = $service->getEventsForDay($today);
        $this->daysRemaining = $service->getDaysRemaining($today);

        $nextVisit = $service->getNextHospitalVisit($today);
        $this->nextHospitalDate = $nextVisit?->locale('fr')->isoFormat('dddd D MMMM YYYY');

        $start = Carbon::parse('2025-11-26');
        $end = Carbon::parse('2027-03-31');
        $totalDays = $start->diffInDays($end);
        $elapsed = $start->diffInDays($today);
        $this->progressPercent = (int) min(100, round(($elapsed / $totalDays) * 100));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard')
            ->layout('layouts.app', ['title' => 'Accueil']);
    }
}
```

- [ ] **Step 2 : Mettre à jour `routes/web.php`** pour utiliser le composant Livewire

```php
<?php

use App\Livewire\Dashboard;
use App\Livewire\Calendar;
use App\Livewire\Treatments;
use App\Livewire\TreatmentEdit;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)->name('home');
Route::get('/calendar', Calendar::class)->name('calendar');
Route::get('/treatments', Treatments::class)->name('treatments');
Route::get('/treatments/{treatment}/edit', TreatmentEdit::class)->name('treatments.edit');
```

- [ ] **Step 3 : Écrire la vue `resources/views/livewire/dashboard.blade.php`**

```html
<div class="p-4 max-w-lg mx-auto">

    {{-- En-tête --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <p class="text-xs text-slate-400 font-medium">{{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</p>
            <h1 class="text-xl font-extrabold text-slate-900">Alexis 💙</h1>
        </div>
        <button wire:click="export"
                class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
        </button>
    </div>

    {{-- Bannière prochain RDV --}}
    @if($nextHospitalDate)
    <div class="rounded-2xl p-4 mb-4 text-white overflow-hidden relative"
         style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
        <div class="absolute top-0 right-0 w-24 h-24 rounded-full bg-white/10 -translate-y-8 translate-x-8"></div>
        <p class="text-xs opacity-80 uppercase tracking-wide font-semibold mb-1">Prochain RDV hôpital</p>
        <p class="text-lg font-extrabold capitalize">{{ $nextHospitalDate }}</p>
        <p class="text-xs opacity-70 mt-1">dans {{ now()->diffInDays(\Carbon\Carbon::parse($nextHospitalDate)) }} jours</p>
    </div>
    @endif

    {{-- Barre de progression --}}
    <div class="bg-white rounded-2xl p-4 mb-4 shadow-sm">
        <div class="flex justify-between items-center mb-2">
            <p class="text-xs font-semibold text-slate-700">Fin du traitement</p>
            <p class="text-xs font-bold text-sky-500">{{ $daysRemaining }} jours restants</p>
        </div>
        <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500"
                 style="width: {{ $progressPercent }}%; background: linear-gradient(90deg, #0ea5e9, #6366f1);">
            </div>
        </div>
        <div class="flex justify-between mt-1.5">
            <p class="text-xs text-slate-400">26 nov. 2025</p>
            <p class="text-xs text-slate-400">31 mars 2027</p>
        </div>
    </div>

    {{-- Widgets 2x2 --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        @foreach ([
            ['label' => 'Visites hôpital', 'count' => $counters['hospital'], 'icon' => '🏥', 'color' => 'text-orange-500', 'bg' => 'bg-orange-50'],
            ['label' => 'Vincristines', 'count' => $counters['vcr'], 'icon' => '💉', 'color' => 'text-violet-500', 'bg' => 'bg-violet-50'],
            ['label' => 'Ponctions lombaires', 'count' => $counters['it_mttx'], 'icon' => '🔬', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50'],
            ['label' => 'MTX restants', 'count' => $counters['mtx'], 'icon' => '💊', 'color' => 'text-red-500', 'bg' => 'bg-red-50'],
        ] as $widget)
        <div class="bg-white rounded-2xl p-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl {{ $widget['bg'] }} flex items-center justify-center text-lg mb-1">
                {{ $widget['icon'] }}
            </div>
            <p class="text-2xl font-extrabold {{ $widget['color'] }} leading-none">{{ $widget['count'] }}</p>
            <p class="text-xs text-slate-400 font-medium mt-0.5">{{ $widget['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Traitements du jour --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-bold text-slate-600 uppercase tracking-wide mb-3">Aujourd'hui</p>
        <div class="space-y-2">
            @forelse($todayEvents as $event)
            <div class="flex items-center gap-3 px-3 py-2 rounded-xl
                        {{ $event['requires_fasting'] ? 'bg-amber-50 border border-amber-200' :
                           ($event['type'] === 'medical_act' || $event['type'] === 'cyclic' ? 'bg-slate-50 border border-slate-100' : 'bg-slate-50') }}">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $event['color'] }};"></span>
                <span class="text-xs font-medium text-slate-700 flex-1">{{ $event['name'] }}</span>
                @if($event['requires_fasting'])
                    <span class="text-xs text-amber-600 font-bold">À jeun !</span>
                @elseif($event['dose'])
                    <span class="text-xs text-slate-400">{{ $event['dose'] }}</span>
                @else
                    <span class="text-xs font-semibold" style="color: {{ $event['color'] }};">Hôpital</span>
                @endif
            </div>
            @empty
            <p class="text-xs text-slate-400 text-center py-2">Aucun événement particulier aujourd'hui.</p>
            @endforelse
        </div>
    </div>

</div>
```

- [ ] **Step 4 : Ajouter la méthode `export` temporaire dans `Dashboard.php`**

```php
public function export(): void
{
    // Implémenté en Task 9
    session()->flash('message', 'Export à venir');
}
```

- [ ] **Step 5 : Écrire les tests Livewire**

```php
<?php
// tests/Feature/Livewire/DashboardTest.php

use App\Livewire\Dashboard;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

it('renders dashboard component', function () {
    Livewire::test(Dashboard::class)
        ->assertStatus(200);
});

it('shows four widget counters', function () {
    $component = Livewire::test(Dashboard::class);
    expect($component->get('counters'))->toHaveKeys(['hospital', 'vcr', 'it_mttx', 'mtx']);
});

it('shows next hospital visit date', function () {
    $component = Livewire::test(Dashboard::class);
    expect($component->get('nextHospitalDate'))->not->toBeNull();
});

it('shows days remaining', function () {
    $component = Livewire::test(Dashboard::class);
    expect($component->get('daysRemaining'))->toBeGreaterThan(0);
});

it('shows progress percent between 0 and 100', function () {
    $component = Livewire::test(Dashboard::class);
    expect($component->get('progressPercent'))->toBeGreaterThanOrEqual(0);
    expect($component->get('progressPercent'))->toBeLessThanOrEqual(100);
});
```

- [ ] **Step 6 : Créer les stubs des composants manquants** (pour que les routes fonctionnent)

```bash
php8.4 artisan make:livewire Calendar
php8.4 artisan make:livewire Treatments
php8.4 artisan make:livewire TreatmentEdit
```

- [ ] **Step 7 : Lancer les tests**

```bash
php8.4 artisan test --filter DashboardTest
```

Attendu : 5 tests passés.

- [ ] **Step 8 : Commit**

```bash
git add app/Livewire/Dashboard.php resources/views/livewire/dashboard.blade.php tests/Feature/Livewire/DashboardTest.php routes/web.php
git commit -m "feat: implement Dashboard Livewire component with widgets, progress bar and today's events"
```

---

## Task 6 : Onglet Calendrier — grille mensuelle + détail du jour

**Goal:** L'onglet Calendrier affiche une grille mensuelle navigable avec des points de couleur par type d'événement, et un panneau de détail du jour sélectionné.

**Files:**
- Modify: `app/Livewire/Calendar.php`
- Modify: `resources/views/livewire/calendar.blade.php`
- Create: `tests/Feature/Livewire/CalendarTest.php`

**Acceptance Criteria:**
- [ ] La grille affiche le mois courant avec navigation précédent/suivant
- [ ] Les jours avec événements affichent des points colorés
- [ ] Tap sur un jour → panneau de détail avec la liste des événements
- [ ] Les jours IT MTTX affichent une alerte "À jeun"
- [ ] 6-MP et 6-TG apparaissent dans le détail mais pas dans la grille
- [ ] Les événements déplacés montrent leur date d'origine dans le détail

**Verify:** `php8.4 artisan test --filter CalendarTest` → tous verts

**Steps:**

- [ ] **Step 1 : Remplir `app/Livewire/Calendar.php`**

```php
<?php

namespace App\Livewire;

use App\Services\CalendarService;
use Carbon\Carbon;
use Livewire\Component;

class Calendar extends Component
{
    public int $year;
    public int $month;
    public ?string $selectedDate = null;
    public array $monthEvents = [];
    public array $selectedDayEvents = [];

    public function mount(CalendarService $service): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
        $this->selectedDate = now()->toDateString();
        $this->loadMonth($service);
        $this->loadDay($service);
    }

    public function previousMonth(CalendarService $service): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->selectedDate = null;
        $this->selectedDayEvents = [];
        $this->loadMonth($service);
    }

    public function nextMonth(CalendarService $service): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->selectedDate = null;
        $this->selectedDayEvents = [];
        $this->loadMonth($service);
    }

    public function selectDay(string $date, CalendarService $service): void
    {
        $this->selectedDate = $date;
        $this->loadDay($service);
    }

    private function loadMonth(CalendarService $service): void
    {
        $this->monthEvents = $service->getEventsForMonth($this->year, $this->month);
    }

    private function loadDay(CalendarService $service): void
    {
        if ($this->selectedDate) {
            $this->selectedDayEvents = $service->getEventsForDay(Carbon::parse($this->selectedDate));
        }
    }

    public function render(): \Illuminate\View\View
    {
        $firstDay = Carbon::create($this->year, $this->month, 1);
        $daysInMonth = $firstDay->daysInMonth;
        // Offset pour commencer la grille au lundi (Carbon: Monday=1)
        $startOffset = ($firstDay->dayOfWeek === 0) ? 6 : $firstDay->dayOfWeek - 1;

        return view('livewire.calendar', [
            'firstDay' => $firstDay,
            'daysInMonth' => $daysInMonth,
            'startOffset' => $startOffset,
            'monthName' => $firstDay->locale('fr')->isoFormat('MMMM YYYY'),
            'today' => now()->toDateString(),
        ])->layout('layouts.app', ['title' => 'Calendrier']);
    }
}
```

- [ ] **Step 2 : Écrire la vue `resources/views/livewire/calendar.blade.php`**

```html
<div class="p-4 max-w-lg mx-auto" x-data>

    {{-- Navigation mensuelle --}}
    <div class="flex items-center justify-between mb-4">
        <button wire:click="previousMonth"
                class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </button>
        <h2 class="text-sm font-bold text-slate-800 capitalize">{{ $monthName }}</h2>
        <button wire:click="nextMonth"
                class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ›
        </button>
    </div>

    {{-- Grille du calendrier --}}
    <div class="bg-white rounded-2xl p-3 shadow-sm mb-4">

        {{-- En-têtes jours --}}
        <div class="grid grid-cols-7 mb-1">
            @foreach(['L','M','M','J','V','S','D'] as $header)
            <div class="text-center text-xs font-semibold text-slate-400 py-1">{{ $header }}</div>
            @endforeach
        </div>

        {{-- Cases du calendrier --}}
        <div class="grid grid-cols-7 gap-y-1">

            {{-- Cellules vides au début --}}
            @for($i = 0; $i < $startOffset; $i++)
            <div></div>
            @endfor

            {{-- Jours du mois --}}
            @for($day = 1; $day <= $daysInMonth; $day++)
            @php
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $events = $monthEvents[$dateStr] ?? [];
                $isToday = $dateStr === $today;
                $isSelected = $dateStr === $selectedDate;
            @endphp
            <button wire:click="selectDay('{{ $dateStr }}')"
                    class="flex flex-col items-center py-1 rounded-xl transition-colors
                           {{ $isToday ? 'bg-sky-500' : ($isSelected ? 'bg-sky-100 ring-2 ring-sky-400' : 'hover:bg-slate-50') }}">
                <span class="text-xs font-medium mb-0.5
                             {{ $isToday ? 'text-white font-bold' : 'text-slate-700' }}">
                    {{ $day }}
                </span>
                {{-- Points de couleur (max 3) --}}
                <div class="flex gap-0.5 flex-wrap justify-center max-w-5">
                    @foreach(array_slice($events, 0, 3) as $event)
                    <span class="w-1 h-1 rounded-full flex-shrink-0
                                 {{ $isToday ? 'bg-white/80' : '' }}"
                          style="{{ !$isToday ? 'background-color: '.$event['color'].';' : '' }}">
                    </span>
                    @endforeach
                </div>
            </button>
            @endfor

        </div>
    </div>

    {{-- Légende --}}
    <div class="flex flex-wrap gap-x-4 gap-y-1 mb-4 px-1">
        @foreach([
            ['color' => '#f97316', 'label' => 'Hôpital'],
            ['color' => '#ef4444', 'label' => 'MTX'],
            ['color' => '#8b5cf6', 'label' => 'VCR'],
            ['color' => '#0ea5e9', 'label' => 'IT MTTX'],
        ] as $item)
        <div class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full" style="background-color: {{ $item['color'] }};"></span>
            <span class="text-xs text-slate-500">{{ $item['label'] }}</span>
        </div>
        @endforeach
    </div>

    {{-- Panneau de détail du jour sélectionné --}}
    @if($selectedDate)
    <div class="bg-white rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-bold text-slate-800 uppercase tracking-wide mb-3">
            {{ \Carbon\Carbon::parse($selectedDate)->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
        </p>

        @if(empty($selectedDayEvents))
        <p class="text-xs text-slate-400 text-center py-2">Aucun événement ce jour.</p>
        @else
        <div class="space-y-2">
            @foreach($selectedDayEvents as $event)
            <div class="flex items-center gap-3 px-3 py-2 rounded-xl
                        {{ $event['requires_fasting'] ? 'bg-amber-50 border border-amber-200' : 'bg-slate-50' }}">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $event['color'] }};"></span>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-slate-800">{{ $event['name'] }}</p>
                    @if($event['requires_fasting'])
                        <p class="text-xs text-amber-600 font-bold">⚠️ Alexis doit être à jeun</p>
                    @endif
                    @if($event['dose'])
                        <p class="text-xs text-slate-400">{{ $event['dose'] }}</p>
                    @endif
                    @if(!empty($event['moved']) && $event['moved'])
                        <p class="text-xs text-orange-500 italic">Déplacé (était le {{ \Carbon\Carbon::parse($event['original_date'])->locale('fr')->isoFormat('D MMM') }})</p>
                    @endif
                </div>
                @if(!empty($event['can_move']) && $event['can_move'])
                <button wire:click="$dispatch('move-event', { id: {{ $event['id'] }} })"
                        class="text-xs text-sky-500 font-semibold border border-sky-200 rounded-lg px-2 py-1 bg-sky-50 hover:bg-sky-100 transition-colors flex-shrink-0">
                    Déplacer
                </button>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

</div>
```

- [ ] **Step 3 : Écrire les tests Livewire**

```php
<?php
// tests/Feature/Livewire/CalendarTest.php

use App\Livewire\Calendar;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Carbon\Carbon;

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

it('renders calendar component', function () {
    Livewire::test(Calendar::class)->assertStatus(200);
});

it('starts on current month', function () {
    $component = Livewire::test(Calendar::class);
    expect($component->get('month'))->toBe(now()->month);
    expect($component->get('year'))->toBe(now()->year);
});

it('can navigate to next month', function () {
    $component = Livewire::test(Calendar::class);
    $nextMonth = now()->addMonth()->month;
    $component->call('nextMonth');
    expect($component->get('month'))->toBe($nextMonth);
});

it('can navigate to previous month', function () {
    $component = Livewire::test(Calendar::class);
    $prevMonth = now()->subMonth()->month;
    $component->call('previousMonth');
    expect($component->get('month'))->toBe($prevMonth);
});

it('selecting a day loads day events', function () {
    $component = Livewire::test(Calendar::class);
    $component->call('selectDay', '2026-04-29'); // Jour visite hôpital
    $events = $component->get('selectedDayEvents');
    $names = collect($events)->pluck('name')->toArray();
    expect($names)->toContain('Hôpital');
});

it('IT MTTX day events have requires_fasting true', function () {
    $component = Livewire::test(Calendar::class);
    $component->call('selectDay', '2026-05-13'); // IT MTTX
    $events = collect($component->get('selectedDayEvents'));
    $itMttx = $events->firstWhere('name', 'IT MTTX');
    expect($itMttx['requires_fasting'])->toBeTrue();
});
```

- [ ] **Step 4 : Lancer les tests**

```bash
php8.4 artisan test --filter CalendarTest
```

Attendu : 6 tests passés.

- [ ] **Step 5 : Commit**

```bash
git add app/Livewire/Calendar.php resources/views/livewire/calendar.blade.php tests/Feature/Livewire/CalendarTest.php
git commit -m "feat: implement Calendar Livewire component with monthly grid and day detail panel"
```

---

## Task 7 : Déplacement d'événements + règle MTX/IT MTTX

**Goal:** Un événement ponctuel peut être déplacé à une autre date via un sélecteur de date, et la règle de cohérence MTX/IT MTTX est appliquée automatiquement lors de tout déplacement.

**Files:**
- Create: `app/Services/EventMoveService.php`
- Modify: `app/Livewire/Calendar.php`
- Modify: `resources/views/livewire/calendar.blade.php`
- Create: `tests/Unit/Services/EventMoveServiceTest.php`

**Acceptance Criteria:**
- [ ] Déplacer un événement met à jour `scheduled_date` et renseigne `original_date`
- [ ] Déplacer un IT MTTX sur un mardi → MTX de ce mardi est `is_cancelled = true`
- [ ] Déplacer un IT MTTX hors d'un mardi (depuis un mardi) → MTX restauré
- [ ] Déplacer un MTX fonctionne sans affecter IT MTTX
- [ ] Tous les tests passent

**Verify:** `php8.4 artisan test --filter EventMoveServiceTest` → tous verts

**Steps:**

- [ ] **Step 1 : Écrire les tests en premier**

```php
<?php
// tests/Unit/Services/EventMoveServiceTest.php

use App\Models\CalendarEvent;
use App\Models\Treatment;
use App\Services\EventMoveService;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->service = app(EventMoveService::class);
});

it('moves an event and stores original_date', function () {
    $hopital = Treatment::where('name', 'Hôpital')->first();
    $event = CalendarEvent::where('treatment_id', $hopital->id)
        ->where('scheduled_date', '2026-04-29')
        ->first();

    $this->service->move($event, '2026-04-30');

    $event->refresh();
    expect($event->scheduled_date->toDateString())->toBe('2026-04-30');
    expect($event->original_date->toDateString())->toBe('2026-04-29');
});

it('cancels MTX when IT MTTX moves to a Tuesday', function () {
    $itMttx = Treatment::where('name', 'IT MTTX')->first();
    $mtx = Treatment::where('name', 'MTX')->first();

    // Trouver un IT MTTX existant et le déplacer sur un mardi
    $itEvent = CalendarEvent::where('treatment_id', $itMttx->id)->first();

    // Trouver le prochain mardi avec un MTX
    $mtxEvent = CalendarEvent::where('treatment_id', $mtx->id)
        ->where('is_cancelled', false)
        ->first();
    $targetTuesday = $mtxEvent->scheduled_date->toDateString();

    $this->service->move($itEvent, $targetTuesday);

    $mtxEvent->refresh();
    expect($mtxEvent->is_cancelled)->toBeTrue();
});

it('restores MTX when IT MTTX moves away from a Tuesday', function () {
    $itMttx = Treatment::where('name', 'IT MTTX')->first();
    $mtx = Treatment::where('name', 'MTX')->first();

    // Setup: IT MTTX sur un mardi (MTX annulé)
    $itEvent = CalendarEvent::where('treatment_id', $itMttx->id)->first();
    $mtxEvent = CalendarEvent::where('treatment_id', $mtx->id)->where('is_cancelled', false)->first();
    $tuesday = $mtxEvent->scheduled_date->toDateString();
    $this->service->move($itEvent, $tuesday);
    $mtxEvent->refresh();
    expect($mtxEvent->is_cancelled)->toBeTrue();

    // Action: déplacer IT MTTX hors du mardi
    $itEvent->refresh();
    $wednesday = \Carbon\Carbon::parse($tuesday)->addDay()->toDateString();
    $this->service->move($itEvent, $wednesday);

    $mtxEvent->refresh();
    expect($mtxEvent->is_cancelled)->toBeFalse();
});

it('moving a non-IT-MTTX event does not affect MTX', function () {
    $hopital = Treatment::where('name', 'Hôpital')->first();
    $mtx = Treatment::where('name', 'MTX')->first();

    $hopitalEvent = CalendarEvent::where('treatment_id', $hopital->id)->first();
    $mtxCountBefore = CalendarEvent::where('treatment_id', $mtx->id)->where('is_cancelled', false)->count();

    $this->service->move($hopitalEvent, '2026-05-01');

    $mtxCountAfter = CalendarEvent::where('treatment_id', $mtx->id)->where('is_cancelled', false)->count();
    expect($mtxCountAfter)->toBe($mtxCountBefore);
});
```

- [ ] **Step 2 : Créer `app/Services/EventMoveService.php`**

```php
<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Treatment;
use Carbon\Carbon;

class EventMoveService
{
    public function move(CalendarEvent $event, string $newDate): void
    {
        $previousDate = $event->scheduled_date->toDateString();

        // Stocker la date d'origine (seulement la première fois)
        if ($event->original_date === null) {
            $event->original_date = $previousDate;
        }

        $event->scheduled_date = $newDate;
        $event->save();

        // Appliquer la règle MTX/IT MTTX uniquement pour les ponctions lombaires
        $treatment = $event->treatment;
        if ($treatment->name === 'IT MTTX') {
            $this->applyMtxCoherenceRule($previousDate, $newDate);
        }
    }

    private function applyMtxCoherenceRule(string $previousDate, string $newDate): void
    {
        $mtx = Treatment::where('name', 'MTX')->first();
        if (!$mtx) return;

        $previousCarbon = Carbon::parse($previousDate);
        $newCarbon = Carbon::parse($newDate);

        // Si l'IT MTTX quittait un mardi, restaurer le MTX de ce jour
        if ($previousCarbon->dayOfWeek === Carbon::TUESDAY) {
            CalendarEvent::where('treatment_id', $mtx->id)
                ->where('scheduled_date', $previousDate)
                ->update(['is_cancelled' => false]);
        }

        // Si l'IT MTTX arrive sur un mardi, annuler le MTX de ce jour
        if ($newCarbon->dayOfWeek === Carbon::TUESDAY) {
            CalendarEvent::where('treatment_id', $mtx->id)
                ->where('scheduled_date', $newDate)
                ->update(['is_cancelled' => true]);
        }
    }
}
```

- [ ] **Step 3 : Enregistrer le service**

Dans `app/Providers/AppServiceProvider.php`, méthode `register()` :

```php
$this->app->singleton(\App\Services\EventMoveService::class);
```

- [ ] **Step 4 : Ajouter le modal de déplacement dans `Calendar.php`**

Ajouter les propriétés et méthodes :

```php
public bool $showMoveModal = false;
public ?int $movingEventId = null;
public string $moveToDate = '';

public function openMoveModal(int $eventId): void
{
    $this->movingEventId = $eventId;
    $this->moveToDate = '';
    $this->showMoveModal = true;
}

public function confirmMove(EventMoveService $moveService, CalendarService $calendarService): void
{
    if (!$this->movingEventId || !$this->moveToDate) return;

    $event = CalendarEvent::findOrFail($this->movingEventId);
    $moveService->move($event, $this->moveToDate);

    $this->showMoveModal = false;
    $this->movingEventId = null;
    $this->loadMonth($calendarService);
    $this->loadDay($calendarService);
}

public function cancelMove(): void
{
    $this->showMoveModal = false;
    $this->movingEventId = null;
    $this->moveToDate = '';
}
```

Ajouter l'import en haut de `Calendar.php` :
```php
use App\Models\CalendarEvent;
use App\Services\EventMoveService;
```

- [ ] **Step 5 : Ajouter le modal dans la vue `calendar.blade.php`**

Juste avant la balise fermante `</div>` principale :

```html
{{-- Modal déplacement --}}
@if($showMoveModal)
<div class="fixed inset-0 bg-black/50 z-50 flex items-end justify-center p-4"
     x-data x-show="true" x-transition>
    <div class="bg-white rounded-2xl p-5 w-full max-w-sm shadow-xl">
        <h3 class="text-sm font-bold text-slate-800 mb-1">Déplacer l'événement</h3>
        <p class="text-xs text-slate-400 mb-4">Choisir la nouvelle date :</p>
        <input type="date"
               wire:model="moveToDate"
               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400 mb-4">
        <div class="flex gap-3">
            <button wire:click="cancelMove"
                    class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                Annuler
            </button>
            <button wire:click="confirmMove"
                    class="flex-1 py-2.5 rounded-xl bg-sky-500 text-sm font-semibold text-white hover:bg-sky-600 transition-colors">
                Confirmer
            </button>
        </div>
    </div>
</div>
@endif
```

Mettre à jour le bouton "Déplacer" dans la vue pour utiliser `openMoveModal` :

```html
<button wire:click="openMoveModal({{ $event['id'] }})"
        class="text-xs text-sky-500 font-semibold border border-sky-200 rounded-lg px-2 py-1 bg-sky-50 hover:bg-sky-100 transition-colors flex-shrink-0">
    Déplacer
</button>
```

- [ ] **Step 6 : Lancer les tests**

```bash
php8.4 artisan test --filter EventMoveServiceTest
```

Attendu : 4 tests passés.

- [ ] **Step 7 : Commit**

```bash
git add app/Services/EventMoveService.php app/Livewire/Calendar.php resources/views/livewire/calendar.blade.php tests/Unit/Services/EventMoveServiceTest.php
git commit -m "feat: add event move functionality with MTX/IT MTTX coherence rule"
```

---

## Task 8 : Onglet Traitements — liste + modification de posologie

**Goal:** L'onglet Traitements affiche la liste des traitements avec posologie courante, et permet de modifier la dose avec enregistrement dans l'historique.

**Files:**
- Modify: `app/Livewire/Treatments.php`
- Modify: `resources/views/livewire/treatments.blade.php`
- Modify: `app/Livewire/TreatmentEdit.php`
- Modify: `resources/views/livewire/treatment-edit.blade.php`
- Create: `tests/Feature/Livewire/TreatmentsTest.php`

**Acceptance Criteria:**
- [ ] La liste affiche les 6 traitements avec couleur, nom, posologie, fréquence
- [ ] IT MTTX n'a pas de bouton "Modifier"
- [ ] Cliquer "Modifier" → vue de détail du traitement
- [ ] Boutons +/− modifient la valeur de dose affichée
- [ ] "Enregistrer" insère dans `posology_history` et met à jour `current_dose`
- [ ] L'historique s'affiche en frise chronologique
- [ ] Tests passent

**Verify:** `php8.4 artisan test --filter TreatmentsTest` → tous verts

**Steps:**

- [ ] **Step 1 : Remplir `app/Livewire/Treatments.php`**

```php
<?php

namespace App\Livewire;

use App\Models\Treatment;
use Livewire\Component;

class Treatments extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.treatments', [
            'treatments' => Treatment::all(),
        ])->layout('layouts.app', ['title' => 'Traitements']);
    }
}
```

- [ ] **Step 2 : Écrire la vue `resources/views/livewire/treatments.blade.php`**

```html
<div class="p-4 max-w-lg mx-auto">
    <h1 class="text-xl font-extrabold text-slate-900 mb-5">Traitements</h1>

    <div class="space-y-3">
        @foreach($treatments as $treatment)
        <div class="bg-white rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full flex-shrink-0"
                          style="background-color: {{ $treatment->color }};"></span>
                    <div>
                        <p class="text-sm font-bold text-slate-800">{{ $treatment->name }}</p>
                        <p class="text-xs text-slate-400">{{ $treatment->commercial_name }}</p>
                    </div>
                </div>
                @if($treatment->isDosageEditable())
                <a href="{{ route('treatments.edit', $treatment) }}"
                   class="text-xs text-sky-500 font-semibold border border-sky-200 rounded-xl px-3 py-1.5 bg-sky-50 hover:bg-sky-100 transition-colors">
                    Modifier
                </a>
                @endif
            </div>

            <div class="flex items-center justify-between">
                <div>
                    @if($treatment->current_dose !== null)
                    <p class="text-xl font-extrabold leading-none" style="color: {{ $treatment->color }};">
                        {{ $treatment->current_dose }}
                        <span class="text-sm font-normal text-slate-400">{{ $treatment->unit }}</span>
                    </p>
                    @else
                    <p class="text-sm font-bold" style="color: {{ $treatment->color }};">
                        {{ $treatment->unit ?? 'Acte médical' }}
                    </p>
                    @endif
                </div>
                <span class="text-xs font-semibold px-2 py-1 rounded-full"
                      style="color: {{ $treatment->color }}; background-color: {{ $treatment->color }}18;">
                    @if($treatment->type === 'daily') Quotidien
                    @elseif($treatment->type === 'weekly') Hebdo · mardi
                    @elseif($treatment->frequency_weeks) / {{ $treatment->frequency_weeks }} sem.
                    @else Médical
                    @endif
                </span>
            </div>

            {{-- Dernier changement de posologie --}}
            @if($treatment->posologyHistory->count() > 1)
            <div class="mt-2 pt-2 border-t border-slate-100 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                <p class="text-xs text-slate-400">
                    Modifié le {{ $treatment->posologyHistory->first()->started_at->locale('fr')->isoFormat('D MMM YYYY') }}
                </p>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
```

- [ ] **Step 3 : Remplir `app/Livewire/TreatmentEdit.php`**

```php
<?php

namespace App\Livewire;

use App\Models\PosologyHistory;
use App\Models\Treatment;
use Livewire\Component;

class TreatmentEdit extends Component
{
    public Treatment $treatment;
    public float $newDose;
    public string $note = '';

    public function mount(Treatment $treatment): void
    {
        $this->treatment = $treatment;
        $this->newDose = (float) $treatment->current_dose;
    }

    public function increment(): void
    {
        $step = $this->treatment->unit === 'ml' ? 0.1 : 1;
        $this->newDose = round($this->newDose + $step, 2);
    }

    public function decrement(): void
    {
        $step = $this->treatment->unit === 'ml' ? 0.1 : 1;
        $this->newDose = max(0, round($this->newDose - $step, 2));
    }

    public function save(): void
    {
        $this->validate(['newDose' => 'required|numeric|min:0']);

        PosologyHistory::create([
            'treatment_id' => $this->treatment->id,
            'dose' => $this->newDose,
            'note' => $this->note ?: null,
            'started_at' => today()->toDateString(),
        ]);

        $this->treatment->update(['current_dose' => $this->newDose]);
        $this->treatment->refresh();
        $this->note = '';

        session()->flash('success', 'Posologie mise à jour.');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.treatment-edit', [
            'history' => $this->treatment->posologyHistory()->get(),
        ])->layout('layouts.app', ['title' => $this->treatment->name]);
    }
}
```

- [ ] **Step 4 : Écrire la vue `resources/views/livewire/treatment-edit.blade.php`**

```html
<div class="p-4 max-w-lg mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('treatments') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </a>
        <div>
            <h1 class="text-base font-extrabold text-slate-900">{{ $treatment->name }} · {{ $treatment->commercial_name }}</h1>
            <p class="text-xs text-slate-400">Traitement {{ $treatment->type === 'daily' ? 'quotidien' : ($treatment->type === 'weekly' ? 'hebdomadaire' : 'cyclique') }}</p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2 mb-4">
        <p class="text-xs font-semibold text-emerald-700">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Sélecteur de dose --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Posologie actuelle</p>

        <div class="flex items-center gap-4 mb-4">
            <button wire:click="decrement"
                    class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                −
            </button>
            <div class="flex-1 text-center">
                <p class="text-4xl font-extrabold leading-none" style="color: {{ $treatment->color }};">
                    {{ number_format($newDose, $treatment->unit === 'ml' ? 1 : 0, ',', '') }}
                </p>
                <p class="text-sm text-slate-400 font-medium mt-1">{{ $treatment->unit }} / {{ $treatment->type === 'daily' ? 'jour' : ($treatment->type === 'weekly' ? 'mardi' : 'prise') }}</p>
            </div>
            <button wire:click="increment"
                    class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                +
            </button>
        </div>

        {{-- Saisie directe --}}
        <input type="number"
               wire:model.live="newDose"
               step="{{ $treatment->unit === 'ml' ? '0.1' : '1' }}"
               min="0"
               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 text-center focus:outline-none focus:ring-2 focus:ring-sky-400 mb-3">

        {{-- Note --}}
        <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 mb-4">
            <svg class="w-3 h-3 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <input type="text"
                   wire:model="note"
                   placeholder="Note optionnelle..."
                   class="flex-1 bg-transparent text-xs text-slate-600 focus:outline-none">
        </div>

        <button wire:click="save"
                class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Enregistrer la modification
        </button>
    </div>

    {{-- Historique --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Historique</p>

        <div class="relative pl-5">
            <div class="absolute left-[3px] top-2 bottom-2 w-0.5 bg-slate-200 rounded-full"></div>

            @foreach($history as $index => $entry)
            <div class="relative mb-4 last:mb-0">
                <div class="absolute -left-5 top-0.5 w-2.5 h-2.5 rounded-full border-2 border-white shadow
                            {{ $index === 0 ? 'bg-sky-500' : 'bg-slate-300' }}"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-bold {{ $index === 0 ? 'text-slate-800' : 'text-slate-500' }}">
                            {{ number_format($entry->dose, $treatment->unit === 'ml' ? 1 : 0, ',', '') }} {{ $treatment->unit }} / {{ $treatment->type === 'daily' ? 'jour' : 'prise' }}
                        </p>
                        <p class="text-xs text-slate-400">
                            Depuis le {{ $entry->started_at->locale('fr')->isoFormat('D MMM YYYY') }}
                        </p>
                        @if($entry->note)
                        <p class="text-xs text-slate-500 italic mt-0.5">{{ $entry->note }}</p>
                        @endif
                    </div>
                    @if($index === 0)
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 flex-shrink-0">Actuel</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
```

- [ ] **Step 5 : Écrire les tests Livewire**

```php
<?php
// tests/Feature/Livewire/TreatmentsTest.php

use App\Livewire\Treatments;
use App\Livewire\TreatmentEdit;
use App\Models\PosologyHistory;
use App\Models\Treatment;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

it('renders treatments list', function () {
    Livewire::test(Treatments::class)->assertStatus(200);
});

it('increments dose by 1 for tablet unit', function () {
    $sixMp = Treatment::where('name', '6-MP')->first();
    $component = Livewire::test(TreatmentEdit::class, ['treatment' => $sixMp]);
    $before = $component->get('newDose');
    $component->call('increment');
    expect($component->get('newDose'))->toBe($before + 1.0);
});

it('increments dose by 0.1 for ml unit', function () {
    $sixTg = Treatment::where('name', '6-TG')->first();
    $component = Livewire::test(TreatmentEdit::class, ['treatment' => $sixTg]);
    $before = $component->get('newDose');
    $component->call('increment');
    expect($component->get('newDose'))->toBeCloseTo($before + 0.1, 1);
});

it('saves new dose and creates posology history entry', function () {
    $sixMp = Treatment::where('name', '6-MP')->first();
    $countBefore = PosologyHistory::where('treatment_id', $sixMp->id)->count();

    Livewire::test(TreatmentEdit::class, ['treatment' => $sixMp])
        ->set('newDose', 2.0)
        ->set('note', 'Augmentation de dose')
        ->call('save');

    expect(PosologyHistory::where('treatment_id', $sixMp->id)->count())->toBe($countBefore + 1);
    $sixMp->refresh();
    expect((float) $sixMp->current_dose)->toBe(2.0);
});

it('does not go below 0 on decrement', function () {
    $sixMp = Treatment::where('name', '6-MP')->first();
    $component = Livewire::test(TreatmentEdit::class, ['treatment' => $sixMp])
        ->set('newDose', 0.0)
        ->call('decrement');
    expect($component->get('newDose'))->toBe(0.0);
});
```

- [ ] **Step 6 : Lancer les tests**

```bash
php8.4 artisan test --filter TreatmentsTest
```

Attendu : 5 tests passés.

- [ ] **Step 7 : Commit**

```bash
git add app/Livewire/Treatments.php app/Livewire/TreatmentEdit.php resources/views/livewire/treatments.blade.php resources/views/livewire/treatment-edit.blade.php tests/Feature/Livewire/TreatmentsTest.php
git commit -m "feat: implement Treatments and TreatmentEdit Livewire components with posology history"
```

---

## Task 9 : Export JSON via Share Sheet Android

**Goal:** Le bouton export de l'onglet Accueil déclenche le partage du fichier JSON via le mécanisme natif Android (Share Sheet).

**Files:**
- Create: `app/Services/ExportService.php`
- Modify: `app/Livewire/Dashboard.php`
- Create: `tests/Unit/Services/ExportServiceTest.php`

**Acceptance Criteria:**
- [ ] `ExportService::generate()` retourne un JSON valide avec les 4 sections (settings, treatments, posology_history, calendar_events)
- [ ] Le JSON contient toutes les entrées de la base
- [ ] Le composant Dashboard déclenche l'action native NativePHP au clic sur export
- [ ] Tests du service passent

**Verify:** `php8.4 artisan test --filter ExportServiceTest` → tous verts

**Steps:**

- [ ] **Step 1 : Écrire les tests en premier**

```php
<?php
// tests/Unit/Services/ExportServiceTest.php

use App\Services\ExportService;
use App\Models\Treatment;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->service = app(ExportService::class);
});

it('generates valid JSON', function () {
    $json = $this->service->generate();
    $data = json_decode($json, true);
    expect($data)->not->toBeNull();
});

it('export contains all required sections', function () {
    $data = json_decode($this->service->generate(), true);
    expect($data)->toHaveKeys(['settings', 'treatments', 'posology_history', 'calendar_events', 'exported_at']);
});

it('export contains all 6 treatments', function () {
    $data = json_decode($this->service->generate(), true);
    expect(count($data['treatments']))->toBe(6);
});

it('export contains calendar events', function () {
    $data = json_decode($this->service->generate(), true);
    expect(count($data['calendar_events']))->toBeGreaterThan(0);
});

it('export contains settings', function () {
    $data = json_decode($this->service->generate(), true);
    expect($data['settings'])->toHaveKey('treatment_start');
    expect($data['settings'])->toHaveKey('treatment_end');
});
```

- [ ] **Step 2 : Créer `app/Services/ExportService.php`**

```php
<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\PosologyHistory;
use App\Models\Setting;
use App\Models\Treatment;

class ExportService
{
    public function generate(): string
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        $treatments = Treatment::all()->map(fn($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'commercial_name' => $t->commercial_name,
            'type' => $t->type,
            'unit' => $t->unit,
            'current_dose' => $t->current_dose,
            'color' => $t->color,
            'frequency_weeks' => $t->frequency_weeks,
            'day_of_week' => $t->day_of_week,
            'recurrence_start' => $t->recurrence_start?->toDateString(),
        ])->toArray();

        $history = PosologyHistory::with('treatment')
            ->orderBy('started_at')
            ->get()
            ->map(fn($h) => [
                'treatment_name' => $h->treatment->name,
                'dose' => $h->dose,
                'unit' => $h->treatment->unit,
                'note' => $h->note,
                'started_at' => $h->started_at->toDateString(),
            ])->toArray();

        $events = CalendarEvent::with('treatment')
            ->orderBy('scheduled_date')
            ->get()
            ->map(fn($e) => [
                'treatment_name' => $e->treatment->name,
                'scheduled_date' => $e->scheduled_date->toDateString(),
                'original_date' => $e->original_date?->toDateString(),
                'is_cancelled' => $e->is_cancelled,
                'notes' => $e->notes,
            ])->toArray();

        return json_encode([
            'exported_at' => now()->toIso8601String(),
            'settings' => $settings,
            'treatments' => $treatments,
            'posology_history' => $history,
            'calendar_events' => $events,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
```

- [ ] **Step 3 : Mettre à jour la méthode `export()` dans `Dashboard.php`**

Ajouter l'import en haut :
```php
use App\Services\ExportService;
use Native\Mobile\Facades\Share;
```

Remplacer la méthode `export()` stub :

```php
public function export(ExportService $exportService): void
{
    $json = $exportService->generate();
    $filename = 'alexis-traitement-' . now()->format('Y-m-d') . '.json';
    $path = storage_path('app/' . $filename);
    file_put_contents($path, $json);

    // Partage natif Android via NativePHP Mobile
    Share::file($path, 'Exporter le calendrier de traitement');
}
```

> **Note :** La façade `Share` de NativePHP Mobile déclenche le `Intent.ACTION_SEND` Android. Consulter la doc NativePHP pour l'API exacte si différente.

- [ ] **Step 4 : Lancer les tests**

```bash
php8.4 artisan test --filter ExportServiceTest
```

Attendu : 5 tests passés.

- [ ] **Step 5 : Commit**

```bash
git add app/Services/ExportService.php app/Livewire/Dashboard.php tests/Unit/Services/ExportServiceTest.php
git commit -m "feat: add ExportService and Android Share Sheet integration"
```

---

## Task 10 : Validation finale et build Android

**Goal:** Toute la suite de tests passe, l'app se lance sur Android via NativePHP, et les 3 onglets sont fonctionnels.

**Files:** Aucun fichier nouveau — vérification et ajustements.

**Acceptance Criteria:**
- [ ] `php8.4 artisan test` → 100% de tests verts
- [ ] `php8.4 artisan native:run android` lance l'app sur émulateur/appareil
- [ ] Les 3 onglets sont navigables
- [ ] Les widgets affichent les bonnes valeurs
- [ ] La modification de posologie s'enregistre et apparaît dans l'historique
- [ ] Le déplacement d'un événement fonctionne
- [ ] L'export produit un fichier JSON partageable

**Verify:** `php8.4 artisan test` → tous verts, 0 échecs

**Steps:**

- [ ] **Step 1 : Lancer tous les tests**

```bash
php8.4 artisan test
```

Corriger tout échec avant de continuer.

- [ ] **Step 2 : Vérifier le build frontend**

```bash
npm run build
```

Attendu : build sans erreur, assets générés dans `public/build/`.

- [ ] **Step 3 : Lancer l'app sur Android**

```bash
php8.4 artisan native:run android
```

Si des erreurs apparaissent, consulter la sortie et corriger.

- [ ] **Step 4 : Test manuel des 3 onglets**

Vérifier sur l'appareil :
1. **Accueil** : widgets avec chiffres corrects, bannière RDV, traitement du jour
2. **Calendrier** : navigation mois par mois, points de couleur, détail du jour, bouton Déplacer
3. **Traitements** : liste avec posologies, modification 6-TG → historique mis à jour

- [ ] **Step 5 : Commit final**

```bash
git add .
git commit -m "feat: complete Alexis treatment calendar app — all tests passing"
```

---

## Récapitulatif des dépendances

```
Task 0 (Scaffold)
    └── Task 1 (Migrations + Modèles)
            └── Task 2 (Seeders)
                    └── Task 3 (CalendarService)
                            └── Task 4 (Layout + Navigation)
                                    ├── Task 5 (Dashboard)
                                    ├── Task 6 (Calendrier)
                                    │       └── Task 7 (Déplacement)
                                    └── Task 8 (Traitements)
                                            └── Task 9 (Export)
                                                    └── Task 10 (Validation finale)
```
