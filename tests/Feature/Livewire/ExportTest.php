<?php

use App\Livewire\Export;
use App\Services\CryptoService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

function mockExportStorageKey(): string
{
    $key = (new CryptoService())->generateKey();
    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn($key);
    return $key;
}

it('renders export component', function () {
    Livewire::test(Export::class)->assertStatus(200);
});

it('initializes with all active profiles and treatments selected', function () {
    $dbProfiles = \App\Models\Profile::active()
        ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
        ->get();

    $component = Livewire::test(Export::class);

    $component->assertSet('selectedProfiles', fn($v) => count($v) === $dbProfiles->count());
    $component->assertSet('selectedTreatments', fn($v) => ! empty($v));
});

it('does not include archived profiles in selection', function () {
    $archivedProfile = \App\Models\Profile::create([
        'name' => 'Archivé', 'color' => '#64748b', 'icon' => 'X',
        'archived_at' => now(),
    ]);

    $component = Livewire::test(Export::class);

    $component->assertSet('selectedProfiles', fn($v) => ! in_array($archivedProfile->id, $v, true));
});

it('toggleProfile deselects profile and all its treatments', function () {
    $firstProfile = \App\Models\Profile::active()
        ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
        ->first();

    $component = Livewire::test(Export::class);
    $component->call('toggleProfile', $firstProfile->id);

    $component->assertSet('selectedProfiles', fn($v) => ! in_array($firstProfile->id, $v, true));
    $component->assertSet('selectedTreatments', fn($v) => collect($v)->every(
        fn($key) => ! str_starts_with($key, $firstProfile->id . ':')
    ));
});

it('toggleProfile re-selects profile and all its treatments', function () {
    $firstProfile = \App\Models\Profile::active()->first();

    $component = Livewire::test(Export::class);
    $component->call('toggleProfile', $firstProfile->id);
    $component->call('toggleProfile', $firstProfile->id);

    $component->assertSet('selectedProfiles', fn($v) => in_array($firstProfile->id, $v, true));
});

it('toggleTreatment deselects treatment and removes profile from selectedProfiles', function () {
    $firstProfile = \App\Models\Profile::active()
        ->with(['treatments' => fn($q) => $q->withoutGlobalScopes()])
        ->first();
    $firstTreatment = $firstProfile->treatments->first();
    $key = $firstProfile->id . ':' . $firstTreatment->id;

    $component = Livewire::test(Export::class);
    $component->call('toggleTreatment', $key);

    $component->assertSet('selectedTreatments', fn($v) => ! in_array($key, $v, true));
    $component->assertSet('selectedProfiles', fn($v) => ! in_array($firstProfile->id, $v, true));
});

it('export button is disabled when no treatments selected', function () {
    $profiles = \App\Models\Profile::active()->get();

    $component = Livewire::test(Export::class);
    foreach ($profiles as $profile) {
        $component->call('toggleProfile', $profile->id);
    }

    $component->assertSet('selectedTreatments', []);
});

it('export calls Share and redirects to home with flash on success', function () {
    mockExportStorageKey();
    \Native\Mobile\Facades\Share::shouldReceive('file')->once();

    $component = Livewire::test(Export::class);
    $component->call('export');

    $component->assertRedirect(route('home'));
    expect(session('export_success'))->toBeTrue();
});

it('export sets error when key is missing', function () {
    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn(null);

    $component = Livewire::test(Export::class);
    $component->call('export');

    $component->assertSet('exportError', fn($v) => $v !== '');
});
