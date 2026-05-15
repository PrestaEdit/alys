<?php

use App\Livewire\Calendar;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

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
    $component->call('selectDay', '2026-04-29'); // Hôpital visit day
    $events = $component->get('selectedDayEvents');
    $names = collect($events)->pluck('name')->toArray();
    expect($names)->toContain('Hôpital');
});

it('IT MTTX day events have requires_fasting true', function () {
    $component = Livewire::test(Calendar::class);
    $component->call('selectDay', '2026-07-08'); // IT MTTX day (2026-01-21 + 24 weeks)
    $component->assertSee('Alys doit être à jeun');
    $component->assertDontSee(Calendar::FASTING_SUBJECT_FALLBACK . ' doit être à jeun');
    $events = collect($component->get('selectedDayEvents'));
    $itMttx = $events->firstWhere('name', 'IT MTTX');
    expect($itMttx)->not->toBeNull();
    expect($itMttx['requires_fasting'])->toBeTrue();
});
