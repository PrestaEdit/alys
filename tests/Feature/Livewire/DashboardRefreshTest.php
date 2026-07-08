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
        'widget_icon' => '💊',
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
