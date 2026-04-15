<?php

use App\Models\Treatment;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('seeds 6 treatments', function () {
    expect(Treatment::count())->toBe(6);
});

it('generates 36 hospital visits', function () {
    $hopital = Treatment::where('name', 'Hôpital')->first();
    expect(CalendarEvent::where('treatment_id', $hopital->id)->count())->toBe(36);
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

    $mtxDates = CalendarEvent::where('treatment_id', $mtx->id)->pluck('scheduled_date')
        ->map(fn($d) => (string) $d)->toArray();
    $itDates = CalendarEvent::where('treatment_id', $itMttx->id)->pluck('scheduled_date')
        ->map(fn($d) => (string) $d)->toArray();

    $overlap = array_intersect($mtxDates, $itDates);
    expect($overlap)->toBeEmpty();
});

it('all MTX events fall on a Tuesday', function () {
    $mtx = Treatment::where('name', 'MTX')->first();
    $nonTuesdays = CalendarEvent::where('treatment_id', $mtx->id)
        ->get()
        ->filter(fn($e) => Carbon::parse($e->scheduled_date)->dayOfWeek !== Carbon::TUESDAY)
        ->count();
    expect($nonTuesdays)->toBe(0);
});

it('seeds 6-TG posology history with 2 entries', function () {
    $sixTg = Treatment::where('name', '6-TG')->first();
    expect($sixTg->posologyHistory->count())->toBe(2);
});
