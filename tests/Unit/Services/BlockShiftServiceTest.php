<?php

use App\Models\CalendarEvent;
use App\Models\Treatment;
use App\Services\BlockShiftService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->service = app(BlockShiftService::class);
});

it('shifts a dexa block to evening: 10 doses preserved with 1 skip start and 1 skip end', function () {
    $vcr = Treatment::where('name', 'VCR')->first();
    $dexa = Treatment::where('name', 'Dexaméthasone')->first();

    $vcrEvent = CalendarEvent::where('treatment_id', $vcr->id)->orderBy('scheduled_date')->first();
    $blockBefore = CalendarEvent::where('parent_event_id', $vcrEvent->id)
        ->orderBy('scheduled_date')->get();
    expect($blockBefore->count())->toBe(5); // 5 days

    // Shift starting from evening one day later
    $originalFirst = $blockBefore->first();
    $newFirstDate = Carbon::parse($originalFirst->scheduled_date)->addDay()->toDateString();
    $this->service->shift($originalFirst, $newFirstDate, 'evening');

    $blockAfter = CalendarEvent::where('parent_event_id', $vcrEvent->id)
        ->orderBy('scheduled_date')->orderBy('id')->get();

    // Should now have 6 events (5 + 1 extra day for compensation)
    expect($blockAfter->count())->toBe(6);

    // First event: skip_morning = true, skip_evening = false
    $first = $blockAfter->first();
    expect($first->skip_morning)->toBeTrue();
    expect($first->skip_evening)->toBeFalse();

    // Middle 4 events: no skips
    foreach ($blockAfter->slice(1, 4) as $mid) {
        expect($mid->skip_morning)->toBeFalse();
        expect($mid->skip_evening)->toBeFalse();
    }

    // Last event: skip_morning = false, skip_evening = true
    $last = $blockAfter->last();
    expect($last->skip_morning)->toBeFalse();
    expect($last->skip_evening)->toBeTrue();

    // Total non-skipped doses = 10
    $totalDoses = $blockAfter->sum(fn($e) => (int)(! $e->skip_morning) + (int)(! $e->skip_evening));
    expect($totalDoses)->toBe(10);
});

it('shifts a dexa block starting from morning: no skip, no extension', function () {
    $vcr = Treatment::where('name', 'VCR')->first();
    $vcrEvent = CalendarEvent::where('treatment_id', $vcr->id)->orderBy('scheduled_date')->first();
    $blockBefore = CalendarEvent::where('parent_event_id', $vcrEvent->id)
        ->orderBy('scheduled_date')->get();

    $first = $blockBefore->first();
    $newFirstDate = Carbon::parse($first->scheduled_date)->addDays(3)->toDateString();
    $this->service->shift($first, $newFirstDate, 'morning');

    $blockAfter = CalendarEvent::where('parent_event_id', $vcrEvent->id)
        ->orderBy('scheduled_date')->get();

    // Same count, no skips
    expect($blockAfter->count())->toBe($blockBefore->count());
    foreach ($blockAfter as $e) {
        expect($e->skip_morning)->toBeFalse();
        expect($e->skip_evening)->toBeFalse();
    }
    // Shifted by +3 days
    expect($blockAfter->first()->scheduled_date->toDateString())
        ->toBe($newFirstDate);
});

it('throws on parent event (non-child)', function () {
    $vcr = Treatment::where('name', 'VCR')->first();
    $vcrEvent = CalendarEvent::where('treatment_id', $vcr->id)->orderBy('scheduled_date')->first();

    $this->service->shift($vcrEvent, '2026-01-01', 'morning');
})->throws(\InvalidArgumentException::class);
