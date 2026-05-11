<?php

use App\Livewire\Dashboard;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

it('renders dashboard component', function () {
    Livewire::test(Dashboard::class)
        ->assertStatus(200);
});

it('shows four widget counters', function () {
    $component = Livewire::test(Dashboard::class);
    expect($component->get('counters'))->toHaveKeys(['hospital', 'vcr', 'it_mttx', 'mtx']);
});

it('shows widget data', function () {
    $component = Livewire::test(Dashboard::class);
    $widgets = $component->get('widgets');
    expect($widgets)->toBeArray()->not->toBeEmpty();
    expect($widgets[0])->toHaveKeys(['display_name', 'count', 'icon', 'color']);
});

it('shows next hospital visit date', function () {
    $component = Livewire::test(Dashboard::class);
    expect($component->get('nextHospitalDate'))->not->toBeNull();
});

it('shows days remaining', function () {
    $component = Livewire::test(Dashboard::class);
    expect($component->get('daysRemaining'))->toBeGreaterThan(0);
});

it('shows progress percent between 0 and 100', function () {
    $component = Livewire::test(Dashboard::class);
    expect($component->get('progressPercent'))->toBeGreaterThanOrEqual(0);
    expect($component->get('progressPercent'))->toBeLessThanOrEqual(100);
});

it('shows export_success flash banner when session is set', function () {
    session()->flash('export_success', true);

    Livewire::test(Dashboard::class)
        ->assertSee('Export réussi');
});
