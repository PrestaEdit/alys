<?php

use App\Models\CalendarEvent;
use App\Models\Treatment;
use App\Services\EventSkipService;
use App\Services\LinkedEventSync;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Carbon::setTestNow('2026-11-05 10:00:00');

    $this->parent = Treatment::create([
        'name' => 'Vincristine', 'type' => 'cyclic', 'color' => '#f97316',
        'frequency_weeks' => 4, 'recurrence_start' => '2026-11-04',
    ]);
    $this->child = Treatment::create([
        'name' => 'Dexamethasone', 'type' => 'daily', 'color' => '#3b82f6',
        'unit' => 'gélule', 'parent_treatment_id' => $this->parent->id, 'linked_days' => 5,
    ]);
    $this->sync = app(LinkedEventSync::class);
});

afterEach(fn () => Carbon::setTestNow());

function childDates(Treatment $child): array
{
    return CalendarEvent::where('treatment_id', $child->id)
        ->where('is_cancelled', false)
        ->orderBy('scheduled_date')
        ->get()
        ->map(fn ($e) => $e->scheduled_date->toDateString())
        ->all();
}

it('regenerates the linked block when parent occurrences were recreated', function () {
    // Parent recréé (ex : recalcul de récurrence) → l'enfant a perdu son bloc.
    CalendarEvent::create(['treatment_id' => $this->parent->id, 'scheduled_date' => '2026-11-05']);
    CalendarEvent::create(['treatment_id' => $this->parent->id, 'scheduled_date' => '2026-12-03']);

    $this->sync->syncChild($this->child);

    expect(childDates($this->child))->toBe([
        '2026-11-05', '2026-11-06', '2026-11-07', '2026-11-08', '2026-11-09',
        '2026-12-03', '2026-12-04', '2026-12-05', '2026-12-06', '2026-12-07',
    ]);
});

it('only generates the remaining days for a block started in the past', function () {
    CalendarEvent::create(['treatment_id' => $this->parent->id, 'scheduled_date' => '2026-11-03']);

    $this->sync->syncChild($this->child);

    expect(childDates($this->child))->toBe(['2026-11-05', '2026-11-06', '2026-11-07']);
});

it('removes future orphans left by a deleted parent occurrence', function () {
    $parentEvent = CalendarEvent::create(['treatment_id' => $this->parent->id, 'scheduled_date' => '2026-11-10']);
    $this->sync->syncChild($this->child);

    // Le parent est supprimé puis recréé à une autre date (nullOnDelete sur l'enfant).
    $parentEvent->delete();
    CalendarEvent::create(['treatment_id' => $this->parent->id, 'scheduled_date' => '2026-11-12']);

    $this->sync->syncChild($this->child);

    expect(childDates($this->child))->toBe([
        '2026-11-12', '2026-11-13', '2026-11-14', '2026-11-15', '2026-11-16',
    ]);
});

it('is idempotent and keeps an already shifted block untouched', function () {
    CalendarEvent::create(['treatment_id' => $this->parent->id, 'scheduled_date' => '2026-11-10']);
    $this->sync->syncChild($this->child);
    $this->sync->syncChild($this->child);

    expect(CalendarEvent::where('treatment_id', $this->child->id)->count())->toBe(5);
});

it('ignores cancelled parent occurrences', function () {
    CalendarEvent::create(['treatment_id' => $this->parent->id, 'scheduled_date' => '2026-11-10', 'is_cancelled' => true]);

    $this->sync->syncChild($this->child);

    expect(childDates($this->child))->toBe([]);
});

it('cascades a skip to linked events whatever the parent name', function () {
    $parentEvent = CalendarEvent::create(['treatment_id' => $this->parent->id, 'scheduled_date' => '2026-11-10']);
    $this->sync->syncChild($this->child);

    app(EventSkipService::class)->skip($parentEvent->fresh());
    expect(childDates($this->child))->toBe([]);

    app(EventSkipService::class)->restore($parentEvent->fresh());
    expect(childDates($this->child))->toHaveCount(5);
});
