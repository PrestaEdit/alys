<?php

use App\Livewire\Dashboard;
use App\Models\Treatment;
use App\Services\ActiveProfile;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

it('refresh() re-hydrate les widgets du Dashboard', function () {
    $component = Livewire::test(Dashboard::class);
    $before = count($component->instance()->widgets);

    // Ajouter un widget après le mount initial
    $activeProfile = app(ActiveProfile::class)->get();
    Treatment::create([
        'profile_id'  => $activeProfile->id,
        'name'        => 'Nouveau widget refresh',
        'type'        => 'daily',
        'color'       => '#000000',
        'unit'        => 'mg',
        'show_widget' => true,
        'widget_icon' => 'pill',
    ]);

    $component->call('refresh');
    $after = count($component->instance()->widgets);

    expect($after)->toBeGreaterThan($before);
});

it('la vue contient le wrapper Alpine visibilitychange avec debounce 1s', function () {
    $html = Livewire::test(Dashboard::class)->html();

    expect($html)->toContain("visibilitychange");
    expect($html)->toContain('$wire.refresh()');
    expect($html)->toMatch('/Date\.now\(\)\s*-\s*last\s*>\s*1000/');
});

it('affiche le prochain événement quand rien aujourd\'hui', function () {
    \App\Models\CalendarEvent::query()->delete();
    \App\Models\Treatment::query()->update(['archived_at' => now()]);

    $activeProfile = app(\App\Services\ActiveProfile::class)->get();
    $treatment = \App\Models\Treatment::create([
        'profile_id'  => $activeProfile->id,
        'name'        => 'Hôpital',
        'type'        => 'cyclic',
        'color'       => '#0ea5e9',
        'unit'        => null,
    ]);
    \App\Models\CalendarEvent::create([
        'treatment_id'   => $treatment->id,
        'scheduled_date' => \Carbon\Carbon::today()->addDays(2),
        'is_cancelled'   => false,
    ]);

    $html = Livewire::test(Dashboard::class)->html();

    expect($html)->toContain('Rien de prévu aujourd');
    expect($html)->toContain('Hôpital');
});

it('affiche le message vide 60j si aucun événement à venir', function () {
    \App\Models\CalendarEvent::query()->delete();
    \App\Models\Treatment::query()->update(['archived_at' => now()]);

    $html = Livewire::test(Dashboard::class)->html();

    expect($html)->toContain('Rien de prévu dans les 60 prochains jours');
});

it('les widgets du Dashboard rendent via alys-icon en 40px', function () {
    // Isolation : on désactive les widgets du seeder pour ne tester QUE le nôtre.
    // Certains widget_icon seedés (💊, 💉, 🔬) n'ont pas de SVG Twemoji embarqué
    // en attendant Task 8 (migration vers clés), donc ils rendraient le fallback
    // et fausseraient l'assertion `not->toContain('alys-icon-fallback')`.
    \App\Models\Treatment::query()->update(['show_widget' => false]);

    $activeProfile = app(\App\Services\ActiveProfile::class)->get();
    \App\Models\Treatment::create([
        'profile_id'  => $activeProfile->id,
        'name'        => 'Widget test',
        'type'        => 'daily',
        'color'       => '#0ea5e9',
        'unit'        => 'mg',
        'show_widget' => true,
        'widget_icon' => 'pill',   // clé médicale : SVG présent dans public/icons/medical/
    ]);

    $html = Livewire::test(Dashboard::class)->html();

    // Container widget en 40px
    expect($html)->toContain('w-10 h-10');
    // SVG rendu par alys-icon
    expect($html)->toContain('<svg');
    // Ce n'est PAS le fallback — l'asset réel a été trouvé et rendu
    expect($html)->not->toContain('alys-icon-fallback');
});
