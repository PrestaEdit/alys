<?php

use App\Models\CalendarEvent;
use App\Models\Treatment;
use App\Services\EventSkipService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->service = app(EventSkipService::class);
});

it('marks the target occurrence as cancelled', function () {
    $hopital = Treatment::where('name', 'Hôpital')->first();
    $event = CalendarEvent::where('treatment_id', $hopital->id)->first();

    $this->service->skip($event);

    $event->refresh();
    expect($event->is_cancelled)->toBeTrue();
});

it('restores an occurrence previously skipped', function () {
    $hopital = Treatment::where('name', 'Hôpital')->first();
    $event = CalendarEvent::where('treatment_id', $hopital->id)->first();

    $this->service->skip($event);
    $this->service->restore($event);

    $event->refresh();
    expect($event->is_cancelled)->toBeFalse();
});

it('restores the paired MTX when skipping IT MTTX on a Tuesday', function () {
    $itMttx = Treatment::where('name', 'IT MTTX')->first();
    $mtx = Treatment::where('name', 'MTX')->first();

    $mtxEvent = CalendarEvent::where('treatment_id', $mtx->id)->where('is_cancelled', false)->first();
    $tuesday = $mtxEvent->scheduled_date->toDateString();

    $itEvent = CalendarEvent::create([
        'treatment_id'   => $itMttx->id,
        'scheduled_date' => $tuesday,
        'is_cancelled'   => false,
    ]);
    $mtxEvent->update(['is_cancelled' => true]);

    $this->service->skip($itEvent);

    $mtxEvent->refresh();
    expect($mtxEvent->is_cancelled)->toBeFalse();
});

it('cascades the skip to VCR child events', function () {
    $vcr = Treatment::where('name', 'VCR')->first();
    $vcrEvent = CalendarEvent::where('treatment_id', $vcr->id)->first();

    $childIds = CalendarEvent::where('parent_event_id', $vcrEvent->id)->pluck('id');
    expect($childIds)->not->toBeEmpty();

    $this->service->skip($vcrEvent);

    $stillActive = CalendarEvent::whereIn('id', $childIds)
        ->where('is_cancelled', false)
        ->count();
    expect($stillActive)->toBe(0);
});

it('does not affect MTX when skipping IT MTTX off a Tuesday', function () {
    $itMttx = Treatment::where('name', 'IT MTTX')->first();
    $mtx = Treatment::where('name', 'MTX')->first();

    $mtxEvent = CalendarEvent::where('treatment_id', $mtx->id)->where('is_cancelled', false)->first();
    $nonTuesday = Carbon::parse($mtxEvent->scheduled_date)->next(Carbon::WEDNESDAY)->toDateString();

    $itEvent = CalendarEvent::create([
        'treatment_id'   => $itMttx->id,
        'scheduled_date' => $nonTuesday,
        'is_cancelled'   => false,
    ]);

    $mtxCountBefore = CalendarEvent::where('treatment_id', $mtx->id)->where('is_cancelled', false)->count();
    $this->service->skip($itEvent);
    $mtxCountAfter = CalendarEvent::where('treatment_id', $mtx->id)->where('is_cancelled', false)->count();

    expect($mtxCountAfter)->toBe($mtxCountBefore);
});
