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

it('export runs without error and writes an alys file', function () {
    $crypto = new \App\Services\CryptoService();
    $key = $crypto->generateKey();

    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn($key);

    Livewire::test(Dashboard::class)
        ->call('export')
        ->assertStatus(200);

    $files = glob(storage_path('app/alys-traitement-*.alys'));
    expect($files)->not->toBeEmpty();

    $envelope = json_decode(file_get_contents($files[0]), true);
    expect($envelope)->toHaveKeys(['v', 'iv', 'tag', 'ct']);
    expect($envelope['v'])->toBe(2);

    $json = $crypto->decrypt(file_get_contents($files[0]), $key);
    $data = json_decode($json, true);
    expect($data)->toHaveKeys(['settings', 'treatments', 'posology_history', 'calendar_events', 'exported_at']);
});
