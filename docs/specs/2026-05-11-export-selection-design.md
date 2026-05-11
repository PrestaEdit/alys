# Export Selection Screen — Design Spec

Date: 2026-05-11

## Overview

Add a selection screen to the export flow, mirroring the import preview pattern. Instead of exporting all data immediately, the user navigates to `/export`, selects which profiles and treatments to include, then triggers the file share.

## User Flow

1. User taps the export icon on the Dashboard
2. App navigates to `/export`
3. User sees a summary header + list of active (non-archived) profiles with their treatments, all pre-selected
4. User adjusts selection if needed (cascade: toggling a profile selects/deselects all its treatments)
5. User taps "Exporter (N traitements)"
6. Native share dialog opens with the `.alys` file
7. After share, app redirects to Dashboard with a success flash message

## Architecture

### New files

- `app/Livewire/Export.php` — Livewire component handling state and export logic
- `resources/views/livewire/export.blade.php` — Selection UI

### Modified files

- `app/Services/ExportService.php` — Add optional `$selectedTreatmentIds` filter parameter
- `routes/web.php` — Add `GET /export` route
- `resources/views/livewire/dashboard.blade.php` — Export icon navigates to `/export` instead of triggering export directly

## Component: Export.php

### State

```php
public array $selectedProfiles = [];     // [profile_id, ...]
public array $selectedTreatments = [];   // ["profile_id:treatment_id", ...]
public bool $exporting = false;
```

### Computed

```php
public function getProfilesProperty(): Collection
// Returns active (non-archived) profiles, each with their treatments eager-loaded
```

Initialized on `mount()`: all profile IDs → `$selectedProfiles`, all `"profile_id:treatment_id"` keys → `$selectedTreatments`.

### Actions

```php
public function toggleProfile(int $profileId): void
// Adds or removes profileId from selectedProfiles
// Adds or removes all "profileId:treatmentId" keys from selectedTreatments

public function toggleTreatment(string $key): void
// "profile_id:treatment_id" format
// Adds or removes from selectedTreatments
// Recomputes selectedProfiles: profile is selected only if ALL its treatments are selected

public function export(): void
// Sets $exporting = true
// Extracts treatment IDs from selectedTreatments keys
// Calls ExportService::generateEncrypted($key, $treatmentIds)
// Calls Share::file(...)
// Redirects to '/' with session flash 'export_success'
```

### Disabled state

The "Exporter" button is disabled when `$selectedTreatments` is empty.

## Service: ExportService

```php
public function generateEncrypted(string $key, array $selectedTreatmentIds = []): string
```

When `$selectedTreatmentIds` is empty, behavior is unchanged (exports everything — backward compatible).

When non-empty:
- `profiles`: only profiles that have at least one treatment in `$selectedTreatmentIds`
- `treatments`: filtered to `$selectedTreatmentIds`
- `posology_history`: filtered to `$selectedTreatmentIds`
- `calendar_events`: filtered to `$selectedTreatmentIds`
- `settings`: always exported in full (not filterable)

## View: export.blade.php

### Summary header

Purple badge showing: `N profils · N traitements · [date du jour]`

Count updates reactively as the user changes selection.

### Profile row

- Checkbox (accent color = profile color)
- Color dot
- Profile name

### Treatment row (indented under profile)

- Checkbox
- Treatment display name
- Secondary info: dose + frequency (right-aligned, muted)

### Cascade behavior

Matches import exactly:
- Toggle profile → all its treatment checkboxes follow
- Toggle treatment → parent profile checkbox: checked only if ALL treatments selected, unchecked otherwise (no indeterminate state needed for MVP)

### Export button

```
Exporter (N traitements)
```

Full-width, primary color, disabled when count = 0. Shows `exporting` spinner while processing.

## Dashboard changes

- Export icon: `wire:navigate` to `/export` (replaces direct `export()` call)
- Flash message: display `session('export_success')` banner (same pattern as other success messages in the app)

## Routes

```php
Route::get('/export', Export::class)->name('export');
```

## Scope exclusions

- Archived profiles are excluded from the list (not shown, not exported)
- Settings are always exported in full regardless of selection
- No export history / `exported_at` tracking per-profile

## Testing

- `ExportTest` unit: `generateEncrypted` with empty filter exports all; with filter exports only selected treatments and their related records
- `Livewire/ExportTest` feature: mount initializes all selected; toggleProfile cascades to treatments; toggleTreatment recomputes profile state; export button disabled when nothing selected; successful export redirects with flash
