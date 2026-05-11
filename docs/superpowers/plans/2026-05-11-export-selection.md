# Export Selection Screen Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers-extended-cc:subagent-driven-development (recommended) or superpowers-extended-cc:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `/export` page where the user selects which profiles and treatments to include before exporting the `.alys` file.

**Architecture:** New `Export` Livewire component mirrors the Import preview pattern — active (non-archived) profiles with nested treatments, all pre-selected, with cascade checkboxes. `ExportService::generate()` gains an optional `$selectedTreatmentIds` filter; `Dashboard` export button navigates to `/export` instead of triggering export directly.

**Tech Stack:** Laravel 11, Livewire 3, NativePHP Mobile (`SecureStorage`, `Share`), Pest, Tailwind CSS.

---

### Task 1: Add `$selectedTreatmentIds` filter to `ExportService`

**Goal:** Make `ExportService::generate()` accept an optional list of treatment IDs so only selected data is exported.

**Files:**
- Modify: `app/Services/ExportService.php`
- Test: `tests/Feature/ExportServiceTest.php`

**Acceptance Criteria:**
- [ ] `generate()` with empty `$selectedTreatmentIds` produces identical output to the current behaviour
- [ ] `generate(['1','2'])` only includes treatments with those IDs, their profiles, posology history, and calendar events
- [ ] Profiles with no selected treatments are excluded from the export
- [ ] Settings are always exported in full regardless of filter

**Verify:** `php artisan test tests/Feature/ExportServiceTest.php` → all green

**Steps:**

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/ExportServiceTest.php`:

```php
<?php

use App\Services\ExportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

it('exports all data when no filter given', function () {
    $service = new ExportService();
    $json = $service->generate();
    $data = json_decode($json, true);

    expect($data['treatments'])->not->toBeEmpty();
    expect($data['profiles'])->not->toBeEmpty();
});

it('exports only selected treatments and their profile', function () {
    $service = new ExportService();

    // Get first treatment ID from a full export
    $allData = json_decode($service->generate(), true);
    expect($allData['treatments'])->not->toBeEmpty();

    // Get the actual treatment model to know its ID
    $treatment = \App\Models\Treatment::withoutGlobalScopes()->first();

    $filtered = json_decode($service->generate([$treatment->id]), true);

    expect($filtered['treatments'])->toHaveCount(1);
    expect($filtered['treatments'][0]['name'])->toBe($treatment->name);
});

it('excludes profiles with no selected treatments', function () {
    // Seed a second profile with its own treatment
    $profile2 = \App\Models\Profile::create([
        'name' => 'Profil B', 'color' => '#10b981', 'icon' => 'B',
    ]);
    app(\App\Services\ActiveProfile::class)->set($profile2->id);
    $treatment2 = \App\Models\Treatment::create(['name' => 'Médicament B', 'type' => 'daily', 'unit' => 'mg', 'current_dose' => 100]);
    app(\App\Services\ActiveProfile::class)->set(\App\Models\Profile::where('name', 'Alys')->first()->id);

    $service = new ExportService();
    // Only export treatment2 (which belongs to profile2)
    $data = json_decode($service->generate([$treatment2->id]), true);

    $profileNames = array_column($data['profiles'], 'name');
    expect($profileNames)->not->toContain('Alys');
    expect($profileNames)->toContain('Profil B');
});

it('always exports settings regardless of filter', function () {
    \App\Models\Setting::updateOrCreate(['key' => 'test_key'], ['value' => 'test_val']);

    $service = new ExportService();
    $treatment = \App\Models\Treatment::withoutGlobalScopes()->first();
    $data = json_decode($service->generate([$treatment->id]), true);

    expect($data['settings'])->toHaveKey('test_key');
});

it('excludes posology history for unselected treatments', function () {
    $treatment = \App\Models\Treatment::withoutGlobalScopes()->first();

    // Insert a history entry for this treatment
    \App\Models\PosologyHistory::create([
        'treatment_id' => $treatment->id,
        'profile_id'   => $treatment->profile_id,
        'dose'         => 500,
        'started_at'   => now(),
    ]);

    $service = new ExportService();

    // Export with empty filter → history included
    $all = json_decode($service->generate(), true);
    expect($all['posology_history'])->not->toBeEmpty();

    // Export with different treatment ID → history excluded
    $data = json_decode($service->generate([0]), true);
    expect($data['posology_history'])->toBeEmpty();
    expect($data['treatments'])->toBeEmpty();
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/ExportServiceTest.php
```

Expected: multiple failures because `generate()` doesn't accept arguments yet.

- [ ] **Step 3: Modify `ExportService::generate()`**

Replace the `generate()` signature and add filtering in `app/Services/ExportService.php`:

```php
public function generate(array $selectedTreatmentIds = []): string
{
    $filtering = ! empty($selectedTreatmentIds);

    $settings = Setting::all()->pluck('value', 'key')->toArray();

    $treatmentsQuery = Treatment::withoutGlobalScopes();
    if ($filtering) {
        $treatmentsQuery->whereIn('id', $selectedTreatmentIds);
    }
    $treatmentModels = $treatmentsQuery->get();

    $profileIds = $filtering
        ? $treatmentModels->pluck('profile_id')->unique()->values()->all()
        : null;

    $profilesQuery = Profile::query();
    if ($filtering) {
        $profilesQuery->whereIn('id', $profileIds);
    }
    $profiles = $profilesQuery->get()->map(fn($p) => [
        'id'              => $p->id,
        'name'            => $p->name,
        'color'           => $p->color,
        'icon'            => $p->icon,
        'treatment_start' => $p->treatment_start?->toDateString(),
        'treatment_end'   => $p->treatment_end?->toDateString(),
        'archived_at'     => $p->archived_at?->toIso8601String(),
    ])->toArray();

    $treatments = $treatmentModels->map(fn($t) => [
        'profile_id'       => $t->profile_id,
        'name'             => $t->name,
        'commercial_name'  => $t->commercial_name,
        'type'             => $t->type,
        'unit'             => $t->unit,
        'current_dose'     => $t->current_dose,
        'dose_morning'     => $t->dose_morning,
        'dose_noon'        => $t->dose_noon,
        'dose_evening'     => $t->dose_evening,
        'color'            => $t->color,
        'frequency_weeks'  => $t->frequency_weeks,
        'day_of_week'      => $t->day_of_week,
        'recurrence_start' => $t->recurrence_start?->toDateString(),
        'is_medical_act'   => $t->is_medical_act,
        'requires_fasting' => $t->requires_fasting,
        'notes'            => $t->notes,
        'show_widget'      => $t->show_widget,
        'widget_icon'      => $t->widget_icon,
        'archived_at'      => $t->archived_at?->toIso8601String(),
    ])->toArray();

    $historyQuery = PosologyHistory::withoutGlobalScopes()->with('treatment')->orderBy('started_at');
    if ($filtering) {
        $historyQuery->whereIn('treatment_id', $selectedTreatmentIds);
    }
    $history = $historyQuery->get()->map(fn($h) => [
        'profile_id'     => $h->profile_id,
        'treatment_name' => $h->treatment->name,
        'dose'           => $h->dose,
        'dose_morning'   => $h->dose_morning,
        'dose_noon'      => $h->dose_noon,
        'dose_evening'   => $h->dose_evening,
        'note'           => $h->note,
        'started_at'     => $h->started_at->toDateString(),
    ])->toArray();

    $eventsQuery = CalendarEvent::withoutGlobalScopes()->with('treatment')->orderBy('scheduled_date');
    if ($filtering) {
        $eventsQuery->whereIn('treatment_id', $selectedTreatmentIds);
    }
    $events = $eventsQuery->get()->map(fn($e) => [
        'profile_id'     => $e->profile_id,
        'treatment_name' => $e->treatment->name,
        'scheduled_date' => $e->scheduled_date->toDateString(),
        'original_date'  => $e->original_date?->toDateString(),
        'is_cancelled'   => $e->is_cancelled,
        'notes'          => $e->notes,
    ])->toArray();

    return json_encode([
        'exported_at'      => now()->toIso8601String(),
        'settings'         => $settings,
        'profiles'         => $profiles,
        'treatments'       => $treatments,
        'posology_history' => $history,
        'calendar_events'  => $events,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}
```

The `generateEncrypted()` signature also gets the filter parameter passed through:

```php
public function generateEncrypted(string $keyBase64, array $selectedTreatmentIds = []): string
{
    $json = $this->generate($selectedTreatmentIds);
    return app(\App\Services\CryptoService::class)->encrypt($json, $keyBase64);
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test tests/Feature/ExportServiceTest.php
```

Expected: all green.

- [ ] **Step 5: Make sure existing ImportTest still passes**

```bash
php artisan test tests/Feature/Livewire/ImportTest.php
```

Expected: all green (no regression — `makeValidAlys()` calls `generateEncrypted($key)` with no second arg, which still works).

- [ ] **Step 6: Commit**

```bash
git add app/Services/ExportService.php tests/Feature/ExportServiceTest.php
git commit -m "feat: add selectedTreatmentIds filter to ExportService"
```

---

### Task 2: Create the `Export` Livewire component

**Goal:** Build the `/export` page with profile/treatment selection, cascade logic, and the export action.

**Files:**
- Create: `app/Livewire/Export.php`
- Create: `resources/views/livewire/export.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Livewire/ExportTest.php`

**Acceptance Criteria:**
- [ ] Page loads and shows all active (non-archived) profiles with their treatments, all pre-selected
- [ ] Archived profiles are not shown
- [ ] Toggling a profile selects/deselects all its treatments
- [ ] Toggling a treatment recomputes the parent profile's checked state (checked only if ALL its treatments are selected)
- [ ] Export button label shows "Exporter (N traitements)" and is disabled when 0 treatments selected
- [ ] Summary header shows correct profile and treatment counts, reactive to selection changes
- [ ] `export()` calls `ExportService::generateEncrypted()` with the correct treatment IDs

**Verify:** `php artisan test tests/Feature/Livewire/ExportTest.php` → all green

**Steps:**

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/Livewire/ExportTest.php`:

```php
<?php

use App\Livewire\Export;
use App\Services\CryptoService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

function mockExportStorageKey(): string
{
    $key = (new CryptoService())->generateKey();
    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn($key);
    return $key;
}

it('renders export component', function () {
    Livewire::test(Export::class)->assertStatus(200);
});

it('initializes with all active profiles and treatments selected', function () {
    $dbProfiles = \App\Models\Profile::active()
        ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
        ->get();

    $component = Livewire::test(Export::class);

    $component->assertSet('selectedProfiles', fn($v) => count($v) === $dbProfiles->count());
    $component->assertSet('selectedTreatments', fn($v) => ! empty($v));
});

it('does not include archived profiles in selection', function () {
    $archivedProfile = \App\Models\Profile::create([
        'name' => 'Archivé', 'color' => '#64748b', 'icon' => 'X',
        'archived_at' => now(),
    ]);

    $component = Livewire::test(Export::class);

    $component->assertSet('selectedProfiles', fn($v) => ! in_array($archivedProfile->id, $v, true));
});

it('toggleProfile deselects profile and all its treatments', function () {
    $firstProfile = \App\Models\Profile::active()
        ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
        ->first();

    $component = Livewire::test(Export::class);
    $component->call('toggleProfile', $firstProfile->id);

    $component->assertSet('selectedProfiles', fn($v) => ! in_array($firstProfile->id, $v, true));
    $component->assertSet('selectedTreatments', fn($v) => collect($v)->every(
        fn($key) => ! str_starts_with($key, $firstProfile->id . ':')
    ));
});

it('toggleProfile re-selects profile and all its treatments', function () {
    $firstProfile = \App\Models\Profile::active()->first();

    $component = Livewire::test(Export::class);
    // Deselect then re-select
    $component->call('toggleProfile', $firstProfile->id);
    $component->call('toggleProfile', $firstProfile->id);

    $component->assertSet('selectedProfiles', fn($v) => in_array($firstProfile->id, $v, true));
});

it('toggleTreatment deselects treatment and removes profile from selectedProfiles', function () {
    $firstProfile = \App\Models\Profile::active()
        ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
        ->first();
    $firstTreatment = $firstProfile->treatments->first();
    $key = $firstProfile->id . ':' . $firstTreatment->id;

    $component = Livewire::test(Export::class);
    $component->call('toggleTreatment', $key);

    $component->assertSet('selectedTreatments', fn($v) => ! in_array($key, $v, true));
    $component->assertSet('selectedProfiles', fn($v) => ! in_array($firstProfile->id, $v, true));
});

it('export button is disabled when no treatments selected', function () {
    $profiles = \App\Models\Profile::active()->get();

    $component = Livewire::test(Export::class);
    foreach ($profiles as $profile) {
        $component->call('toggleProfile', $profile->id);
    }

    $component->assertSet('selectedTreatments', []);
});

it('export calls Share and redirects to home with flash on success', function () {
    mockExportStorageKey();
    \Native\Mobile\Facades\Share::shouldReceive('file')->once();

    $component = Livewire::test(Export::class);
    $component->call('export');

    $component->assertRedirect(route('home'));
    expect(session('export_success'))->toBeTrue();
});

it('export sets error when key is missing', function () {
    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn(null);

    $component = Livewire::test(Export::class);
    $component->call('export');

    $component->assertSet('exportError', fn($v) => $v !== '');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/Livewire/ExportTest.php
```

Expected: failures because `Export` class doesn't exist.

- [ ] **Step 3: Create `app/Livewire/Export.php`**

`profiles` is not stored as a public Livewire property (serialization overhead). Instead, it is queried fresh on each render and passed to the view. Toggle methods query the DB directly for the profile they need.

```php
<?php

namespace App\Livewire;

use App\Models\Profile;
use App\Services\ExportService;
use Livewire\Component;
use Native\Mobile\Facades\SecureStorage;
use Native\Mobile\Facades\Share;

class Export extends Component
{
    public array $selectedProfiles = [];
    public array $selectedTreatments = [];
    public bool $exporting = false;
    public string $exportError = '';

    public function mount(): void
    {
        $profiles = Profile::active()
            ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
            ->get();

        foreach ($profiles as $profile) {
            $this->selectedProfiles[] = $profile->id;
            foreach ($profile->treatments as $treatment) {
                $this->selectedTreatments[] = $profile->id . ':' . $treatment->id;
            }
        }
    }

    public function toggleProfile(int $profileId): void
    {
        $profile = Profile::active()
            ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
            ->find($profileId);

        if (! $profile) {
            return;
        }

        $treatmentKeys = $profile->treatments
            ->map(fn($t) => $profileId . ':' . $t->id)
            ->all();

        if (in_array($profileId, $this->selectedProfiles, true)) {
            $this->selectedProfiles = array_values(array_filter(
                $this->selectedProfiles,
                fn($id) => $id !== $profileId
            ));
            $this->selectedTreatments = array_values(array_filter(
                $this->selectedTreatments,
                fn($key) => ! in_array($key, $treatmentKeys, true)
            ));
        } else {
            $this->selectedProfiles   = array_values(array_unique([...$this->selectedProfiles, $profileId]));
            $this->selectedTreatments = array_values(array_unique([...$this->selectedTreatments, ...$treatmentKeys]));
        }
    }

    public function toggleTreatment(string $key): void
    {
        if (in_array($key, $this->selectedTreatments, true)) {
            $this->selectedTreatments = array_values(array_filter(
                $this->selectedTreatments,
                fn($k) => $k !== $key
            ));
        } else {
            $this->selectedTreatments = array_values(array_unique([...$this->selectedTreatments, $key]));
        }

        // A profile is selected only if ALL its treatments are selected
        $profiles = Profile::active()
            ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
            ->get();

        $this->selectedProfiles = [];
        foreach ($profiles as $profile) {
            $allSelected = $profile->treatments->every(
                fn($t) => in_array($profile->id . ':' . $t->id, $this->selectedTreatments, true)
            );
            if ($allSelected && $profile->treatments->isNotEmpty()) {
                $this->selectedProfiles[] = $profile->id;
            }
        }
    }

    public function export(ExportService $exportService): void
    {
        $this->exportError = '';
        $this->exporting   = true;

        try {
            $key = SecureStorage::get('device_key');

            if ($key === null) {
                $this->exportError = 'Clés non initialisées. Allez dans Réglages > Transfert de clés.';
                return;
            }

            // Extract treatment IDs from "profileId:treatmentId" keys
            $treatmentIds = array_map(
                fn($k) => (int) explode(':', $k)[1],
                $this->selectedTreatments
            );

            $envelope = $exportService->generateEncrypted($key, $treatmentIds);

            $filename = 'alys-traitement-' . now()->format('Y-m-d') . '.alys';
            $tempDir  = config('nativephp-internal.tempdir') ?: sys_get_temp_dir();
            $path     = rtrim($tempDir, '/') . '/' . $filename;

            if (file_put_contents($path, $envelope) === false) {
                $this->exportError = 'Impossible d\'écrire dans : ' . $path;
                return;
            }

            Share::file('Alys Traitement', 'Export chiffré du calendrier de traitement', $path);

            session()->flash('export_success', true);
            $this->redirect(route('home'), navigate: true);
        } catch (\Throwable $e) {
            $this->exportError = get_class($e) . ': ' . $e->getMessage();
        } finally {
            $this->exporting = false;
        }
    }

    public function render(): \Illuminate\View\View
    {
        $profiles = Profile::active()
            ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
            ->get();

        return view('livewire.export', ['profiles' => $profiles])
            ->layout('layouts.app', ['title' => 'Exporter']);
    }
}
```

- [ ] **Step 4: Create `resources/views/livewire/export.blade.php`**

```blade
<div class="p-4 max-w-lg mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('home') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </a>
        <h1 class="text-xl font-extrabold text-slate-900">Exporter</h1>
    </div>

    @if($exportError)
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-4">
            <p class="text-sm text-red-700">{{ $exportError }}</p>
        </div>
    @endif

    @php
        $totalSelected = count($selectedTreatments);
        $profileCount  = count($selectedProfiles);
    @endphp

    {{-- Summary --}}
    <div class="bg-violet-50 border border-violet-100 rounded-2xl p-3 mb-4 flex items-center gap-2 text-violet-700 text-sm">
        <span>📦</span>
        <span>
            <strong>{{ $profileCount }}</strong> profil{{ $profileCount !== 1 ? 's' : '' }} ·
            <strong>{{ $totalSelected }}</strong> traitement{{ $totalSelected !== 1 ? 's' : '' }} ·
            {{ now()->locale('fr')->isoFormat('D MMM YYYY') }}
        </span>
    </div>

    {{-- Profiles list --}}
    <div class="space-y-4 mb-5">
        @foreach($profiles as $profile)
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">

                {{-- Profile row --}}
                <label class="flex items-center gap-3 p-4 cursor-pointer select-none">
                    <input type="checkbox"
                           wire:click="toggleProfile({{ $profile->id }})"
                           @checked(in_array($profile->id, $selectedProfiles, true))
                           class="w-4 h-4 cursor-pointer flex-shrink-0"
                           style="accent-color: {{ $profile->color }}">
                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $profile->color }};"></span>
                    <span class="font-semibold text-slate-900 flex-1 text-sm">{{ $profile->name }}</span>
                </label>

                {{-- Treatment rows --}}
                @if($profile->treatments->isNotEmpty())
                    <div class="border-t border-slate-100 divide-y divide-slate-50">
                        @foreach($profile->treatments as $treatment)
                            @php
                                $tKey = $profile->id . ':' . $treatment->id;
                            @endphp
                            <label class="flex items-center gap-3 px-4 py-3 cursor-pointer select-none">
                                <input type="checkbox"
                                       wire:click="toggleTreatment('{{ $tKey }}')"
                                       @checked(in_array($tKey, $selectedTreatments, true))
                                       class="w-4 h-4 cursor-pointer flex-shrink-0"
                                       style="accent-color: {{ $profile->color }}">
                                <span class="text-sm text-slate-800 flex-1">{{ $treatment->displayName() }}</span>
                                @if($treatment->current_dose && $treatment->unit)
                                    <span class="text-xs text-slate-400">{{ $treatment->current_dose }} {{ $treatment->unit }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @endif

            </div>
        @endforeach
    </div>

    {{-- Export button --}}
    <button wire:click="export"
            wire:loading.attr="disabled"
            wire:target="export"
            @disabled(count($selectedTreatments) === 0)
            class="w-full bg-indigo-600 text-white font-semibold py-3 rounded-2xl text-sm disabled:opacity-50">
        <span wire:loading.remove wire:target="export">
            Exporter ({{ $totalSelected }} traitement{{ $totalSelected !== 1 ? 's' : '' }})
        </span>
        <span wire:loading wire:target="export">Export en cours…</span>
    </button>

</div>
```

- [ ] **Step 5: Add the route to `routes/web.php`**

After the `/import` line, add:

```php
use App\Livewire\Export;

// …existing routes…
Route::get('/export', Export::class)->name('export');
```

Also add the import at the top:

```php
use App\Livewire\Export;
```

- [ ] **Step 6: Run tests**

```bash
php artisan test tests/Feature/Livewire/ExportTest.php
```

Expected: all green.

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/Export.php resources/views/livewire/export.blade.php routes/web.php tests/Feature/Livewire/ExportTest.php
git commit -m "feat: add Export Livewire component with profile/treatment selection"
```

---

### Task 3: Update Dashboard — replace export button with navigation to `/export`

**Goal:** The Dashboard export icon navigates to `/export` instead of triggering export directly. On return, display the `export_success` flash message.

**Files:**
- Modify: `resources/views/livewire/dashboard.blade.php`
- Modify: `app/Livewire/Dashboard.php`

**Acceptance Criteria:**
- [ ] Export icon navigates to `/export` (uses `wire:navigate` `href`)
- [ ] `export()` method and related properties removed from `Dashboard.php`
- [ ] Dashboard shows a success banner when `session('export_success')` is set
- [ ] No regression on existing dashboard rendering

**Verify:** `php artisan test` → full suite green

**Steps:**

- [ ] **Step 1: Update `resources/views/livewire/dashboard.blade.php`**

Replace the export `<button>` (lines 11–21) with a link:

```blade
<a href="{{ route('export') }}" wire:navigate
   class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
    </svg>
</a>
```

Replace the `$exportSuccess` banner (lines 24–28) with a flash-based banner:

```blade
@if(session('export_success'))
<div class="bg-green-50 border border-green-200 rounded-2xl p-3 mb-4 text-xs text-green-700">
    Export réussi — fichier partagé avec succès.
</div>
@endif
```

Remove the `$exportError` banner (lines 30–34) — errors are now displayed on `/export`.

- [ ] **Step 2: Clean up `app/Livewire/Dashboard.php`**

Remove the export-related properties and method:

```php
// Remove these properties:
public string $exportError = '';
public bool $exportLoading = false;
public bool $exportSuccess = false;

// Remove this method entirely:
public function export(ExportService $exportService): void { … }
```

Remove the `ExportService` use statement if it's no longer referenced.

The `mount()` signature stays the same (no `ExportService` parameter there).

- [ ] **Step 3: Run full test suite**

```bash
php artisan test
```

Expected: all green.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/dashboard.blade.php app/Livewire/Dashboard.php
git commit -m "feat: replace dashboard export button with navigation to /export"
```
