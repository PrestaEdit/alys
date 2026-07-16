<?php

use App\Models\CalendarEvent;
use App\Models\Treatment;
use App\Services\EventMoveService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->service = app(EventMoveService::class);
});

it('moves an event and stores original_date', function () {
    $hopital = Treatment::where('name', 'Hôpital')->first();
    $event = CalendarEvent::where('treatment_id', $hopital->id)
        ->whereDate('scheduled_date', '2026-04-29')
        ->first();

    $this->service->move($event, '2026-04-30');

    $event->refresh();
    expect($event->scheduled_date->toDateString())->toBe('2026-04-30');
    expect($event->original_date->toDateString())->toBe('2026-04-29');
});

it('original_date is only set once on first move', function () {
    $hopital = Treatment::where('name', 'Hôpital')->first();
    $event = CalendarEvent::where('treatment_id', $hopital->id)
        ->whereDate('scheduled_date', '2026-04-29')
        ->first();

    $this->service->move($event, '2026-04-30');
    $event->refresh();
    $this->service->move($event, '2026-05-01');
    $event->refresh();

    // original_date should still be 2026-04-29, not 2026-04-30
    expect($event->original_date->toDateString())->toBe('2026-04-29');
});

it('cancels MTX when IT MTTX moves to a Tuesday', function () {
    $itMttx = Treatment::where('name', 'IT MTTX')->first();
    $mtx = Treatment::where('name', 'MTX')->first();

    $itEvent = CalendarEvent::where('treatment_id', $itMttx->id)->first();

    // Find a Tuesday that has an MTX event
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

    // Setup: move IT MTTX onto a Tuesday (MTX gets cancelled)
    $itEvent = CalendarEvent::where('treatment_id', $itMttx->id)->first();
    $mtxEvent = CalendarEvent::where('treatment_id', $mtx->id)->where('is_cancelled', false)->first();
    $tuesday = $mtxEvent->scheduled_date->toDateString();
    $this->service->move($itEvent, $tuesday);
    $mtxEvent->refresh();
    expect($mtxEvent->is_cancelled)->toBeTrue();

    // Now move IT MTTX off the Tuesday
    $itEvent->refresh();
    $wednesday = Carbon::parse($tuesday)->addDay()->toDateString();
    $this->service->move($itEvent, $wednesday);

    $mtxEvent->refresh();
    expect($mtxEvent->is_cancelled)->toBeFalse();
});

it('moving VCR shifts its dexamethasone children by the same delta', function () {
    $vcr = Treatment::where('name', 'VCR')->first();
    $dexa = Treatment::where('name', 'Dexaméthasone')->first();

    $vcrEvent = CalendarEvent::where('treatment_id', $vcr->id)->first();
    $originalVcrDate = $vcrEvent->scheduled_date->toDateString();

    // Dexamethasone children are on J0..J4
    $childrenBefore = CalendarEvent::where('parent_event_id', $vcrEvent->id)
        ->orderBy('scheduled_date')
        ->pluck('scheduled_date')
        ->map(fn($d) => Carbon::parse($d)->toDateString())
        ->toArray();

    // Move VCR 3 days forward
    $newVcrDate = Carbon::parse($originalVcrDate)->addDays(3)->toDateString();
    $this->service->move($vcrEvent, $newVcrDate);

    $childrenAfter = CalendarEvent::where('parent_event_id', $vcrEvent->id)
        ->orderBy('scheduled_date')
        ->pluck('scheduled_date')
        ->map(fn($d) => Carbon::parse($d)->toDateString())
        ->toArray();

    foreach ($childrenBefore as $i => $before) {
        $expected = Carbon::parse($before)->addDays(3)->toDateString();
        expect($childrenAfter[$i])->toBe($expected);
    }
});

it('moving the first day of a child block shifts its siblings and leaves the parent untouched', function () {
    $vcr = Treatment::where('name', 'VCR')->first();

    $vcrEvent = CalendarEvent::where('treatment_id', $vcr->id)->first();
    $vcrDateBefore = $vcrEvent->scheduled_date->toDateString();

    // Snapshot [id => scheduled_date] BEFORE the move — the Eloquent models
    // in $childrenBefore share references that get mutated by move().
    $datesBefore = CalendarEvent::where('parent_event_id', $vcrEvent->id)
        ->orderBy('scheduled_date')
        ->get()
        ->mapWithKeys(fn($e) => [$e->id => $e->scheduled_date->toDateString()])
        ->toArray();

    $day0Id = array_key_first($datesBefore);
    $day0 = CalendarEvent::find($day0Id);
    $newDay0Date = Carbon::parse($datesBefore[$day0Id])->addDays(2)->toDateString();

    $this->service->move($day0, $newDay0Date);

    // Parent (VCR) untouched
    $vcrEvent->refresh();
    expect($vcrEvent->scheduled_date->toDateString())->toBe($vcrDateBefore);

    // All siblings (including the moved day-0) shifted by +2 days
    foreach ($datesBefore as $id => $before) {
        $expected = Carbon::parse($before)->addDays(2)->toDateString();
        $after = CalendarEvent::find($id)->scheduled_date->toDateString();
        expect($after)->toBe($expected);
    }
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
