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
    PersonalEvent::create([
        'title' => 'Pont', 'category' => 'autre', 'color' => '#f59e0b', 'icon' => '📌',
        'start_date' => '2026-06-28', 'end_date' => '2026-07-02',
    ]);
    PersonalEvent::create([
        'title' => 'Août', 'category' => 'autre', 'color' => '#f59e0b', 'icon' => '📌',
        'start_date' => '2026-08-01', 'end_date' => '2026-08-05',
    ]);

    $july = PersonalEvent::forMonth(2026, 7)->get();
    expect($july)->toHaveCount(1);
    expect($july->first()->title)->toBe('Pont');
});
