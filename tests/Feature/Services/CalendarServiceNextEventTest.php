<?php

use App\Models\CalendarEvent;
use App\Models\Treatment;
use App\Services\ActiveProfile;
use App\Services\CalendarService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

it('retourne null si aucun événement futur dans la fenêtre', function () {
    // Le seeder de base peut créer des événements — on efface pour ce test précis
    CalendarEvent::query()->delete();
    Treatment::query()->update(['type' => 'cyclic', 'archived_at' => now()]);

    $result = app(CalendarService::class)->getNextEventAfter(Carbon::today());

    expect($result)->toBeNull();
});

it('retourne le prochain événement avec sa date', function () {
    CalendarEvent::query()->delete();
    // Neutralise les traitements quotidiens du seeder pour isoler l'événement testé
    Treatment::query()->update(['archived_at' => now()]);

    // On crée un traitement lié au profil actif et un événement dans 3 jours
    $profile = app(ActiveProfile::class)->get();
    $treatment = Treatment::create([
        'profile_id'  => $profile->id,
        'name'        => 'IT MTTX',
        'type'        => 'cyclic',
        'color'       => '#3b82f6',
        'unit'        => 'mg',
    ]);
    CalendarEvent::create([
        'treatment_id'   => $treatment->id,
        'scheduled_date' => Carbon::today()->addDays(3),
        'is_cancelled'   => false,
    ]);

    $result = app(CalendarService::class)->getNextEventAfter(Carbon::today());

    expect($result)->not->toBeNull();
    expect($result['date']->toDateString())->toBe(Carbon::today()->addDays(3)->toDateString());
    expect($result['event']['name'])->toBe('IT MTTX');
});

it('ne teste pas le jour from lui-même', function () {
    CalendarEvent::query()->delete();
    Treatment::query()->update(['archived_at' => now()]);

    $profile = app(ActiveProfile::class)->get();
    $treatment = Treatment::create([
        'profile_id'  => $profile->id,
        'name'        => 'Hôpital',
        'type'        => 'cyclic',
        'color'       => '#0ea5e9',
        'unit'        => null,
    ]);
    // Événement pile aujourd'hui
    CalendarEvent::create([
        'treatment_id'   => $treatment->id,
        'scheduled_date' => Carbon::today(),
        'is_cancelled'   => false,
    ]);

    $result = app(CalendarService::class)->getNextEventAfter(Carbon::today());

    expect($result)->toBeNull();
});

it('respecte le paramètre maxDays', function () {
    CalendarEvent::query()->delete();
    Treatment::query()->update(['archived_at' => now()]);

    $profile = app(ActiveProfile::class)->get();
    $treatment = Treatment::create([
        'profile_id'  => $profile->id,
        'name'        => 'Hôpital',
        'type'        => 'cyclic',
        'color'       => '#0ea5e9',
        'unit'        => null,
    ]);
    // Événement dans 30 jours mais on limite à 10
    CalendarEvent::create([
        'treatment_id'   => $treatment->id,
        'scheduled_date' => Carbon::today()->addDays(30),
        'is_cancelled'   => false,
    ]);

    $result = app(CalendarService::class)->getNextEventAfter(Carbon::today(), maxDays: 10);

    expect($result)->toBeNull();
});

it('retourne null si maxDays est 0 (aucune itération)', function () {
    $result = app(CalendarService::class)->getNextEventAfter(Carbon::today(), maxDays: 0);

    expect($result)->toBeNull();
});

it('avec maxDays=1, ne regarde que le jour from+1', function () {
    CalendarEvent::query()->delete();
    Treatment::query()->update(['archived_at' => now()]);

    $profile = app(ActiveProfile::class)->get();
    $treatment = Treatment::create([
        'profile_id'  => $profile->id,
        'name'        => 'IT MTTX',
        'type'        => 'cyclic',
        'color'       => '#3b82f6',
        'unit'        => 'mg',
    ]);
    // Un événement à J+1 (visible) et un à J+2 (hors fenêtre maxDays=1)
    CalendarEvent::create([
        'treatment_id'   => $treatment->id,
        'scheduled_date' => Carbon::today()->addDays(1),
        'is_cancelled'   => false,
    ]);
    CalendarEvent::create([
        'treatment_id'   => $treatment->id,
        'scheduled_date' => Carbon::today()->addDays(2),
        'is_cancelled'   => false,
    ]);

    $result = app(CalendarService::class)->getNextEventAfter(Carbon::today(), maxDays: 1);

    expect($result)->not->toBeNull();
    expect($result['date']->toDateString())->toBe(Carbon::today()->addDays(1)->toDateString());
});
