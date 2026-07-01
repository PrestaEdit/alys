<?php

use App\Services\CalendarService;
use App\Models\Treatment;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
    // À partir du 16 avril 2026 (après la visite du 15), il reste 25 visites (visits 12–36)
    $counters = $this->service->getCounters(Carbon::parse('2026-04-16'));
    expect($counters['hospital'])->toBe(25);
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
        ->whereDate('scheduled_date', '2026-04-29')
        ->update(['is_cancelled' => true]);

    $events = $this->service->getEventsForDay(Carbon::parse('2026-04-29'));
    $names = collect($events)->pluck('name')->toArray();
    expect($names)->not->toContain('Hôpital');
});

it('getEventsForDay flags IT MTTX as requiring fasting', function () {
    // 2026-07-08 is an IT MTTX day (2026-01-21 + 24 weeks)
    $events = $this->service->getEventsForDay(Carbon::parse('2026-07-08'));
    $itMttx = collect($events)->firstWhere('name', 'IT MTTX');
    expect($itMttx)->not->toBeNull();
    expect($itMttx['requires_fasting'])->toBeTrue();
});

it('getEventsForMonth returns array indexed by date string', function () {
    $month = $this->service->getEventsForMonth(2026, 4);
    expect($month)->toBeArray();
    expect($month)->toHaveKey('2026-04-15'); // visite hôpital + VCR on this day
});

it('getEventsForDay returns correct dose for a date between two posology history entries', function () {
    // 6-TG has history: 2.80ml from 2025-11-26, then 3.00ml from 2026-04-15
    // On 2026-03-01 (between the two), it should return 2.80ml
    $events = $this->service->getEventsForDay(Carbon::parse('2026-03-01'));
    $sixTg = collect($events)->firstWhere('name', '6-TG');
    expect($sixTg)->not->toBeNull();
    expect($sixTg['dose'])->toBe('2,8 ml');
});

it('getEventsForMonth includes a personal event on each day of its range', function () {
    \App\Models\PersonalEvent::create([
        'title' => 'Vacances', 'category' => 'vacances', 'color' => '#0ea5e9', 'icon' => '🏖️',
        'start_date' => '2026-04-10', 'end_date' => '2026-04-12',
    ]);

    $month = $this->service->getEventsForMonth(2026, 4);

    foreach (['2026-04-10', '2026-04-11', '2026-04-12'] as $day) {
        $titles = collect($month[$day] ?? [])->where('kind', 'personal')->pluck('name')->toArray();
        expect($titles)->toContain('Vacances');
    }
    // Hors plage : pas d'événement personnel le 13
    $day13 = collect($month['2026-04-13'] ?? [])->where('kind', 'personal')->pluck('name')->toArray();
    expect($day13)->not->toContain('Vacances');
});

it('getEventsForDay returns a personal event with its metadata', function () {
    \App\Models\PersonalEvent::create([
        'title' => 'Excursion', 'category' => 'excursion', 'color' => '#10b981', 'icon' => '🚌',
        'start_date' => '2026-04-16', 'end_date' => '2026-04-18',
    ]);

    $events = collect($this->service->getEventsForDay(\Carbon\Carbon::parse('2026-04-17')));
    $personal = $events->firstWhere('kind', 'personal');

    expect($personal)->not->toBeNull();
    expect($personal['title'])->toBe('Excursion');
    expect($personal['icon'])->toBe('🚌');
    expect($personal['is_multi_day'])->toBeTrue();
    expect($personal['start_date'])->toBe('2026-04-16');
    expect($personal['end_date'])->toBe('2026-04-18');
});

it('treatment entries are flagged with kind=treatment', function () {
    $events = collect($this->service->getEventsForDay(\Carbon\Carbon::parse('2026-04-29')));
    $hospital = $events->firstWhere('name', 'Hôpital');
    expect($hospital['kind'])->toBe('treatment');
});

// Robustesse : sur un appareil dont le build précède la migration, la table
// personal_events peut être absente. Le calendrier doit alors afficher les
// traitements sans planter (pas de 500), plutôt que d'échouer sur la table manquante.
it('getEventsForDay still returns treatments when personal_events table is missing', function () {
    \Illuminate\Support\Facades\Schema::drop('personal_events');

    $events = $this->service->getEventsForDay(Carbon::parse('2026-04-29'));

    $names = collect($events)->pluck('name')->toArray();
    expect($names)->toContain('Hôpital');
});

it('getEventsForMonth still returns treatments when personal_events table is missing', function () {
    \Illuminate\Support\Facades\Schema::drop('personal_events');

    $month = $this->service->getEventsForMonth(2026, 4);

    expect($month)->toBeArray();
    expect($month)->toHaveKey('2026-04-15');
});
