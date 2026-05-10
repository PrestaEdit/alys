<?php

use App\Events\Native\FileChosen;
use App\Livewire\Import;
use App\Services\CryptoService;
use App\Services\ExportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

// ── helpers ──────────────────────────────────────────────────────────────────

function makeValidAlys(): array
{
    $crypto = new CryptoService();
    $key    = $crypto->generateKey();
    $alys   = (new ExportService())->generateEncrypted($key);

    return ['key' => $key, 'alys' => $alys];
}

function mockStorageKey(string $key): void
{
    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn($key);
}

// ── tests ─────────────────────────────────────────────────────────────────────

it('renders import component', function () {
    Livewire::test(Import::class)->assertStatus(200);
});

it('shows error on invalid alys file', function () {
    mockStorageKey((new CryptoService())->generateKey());

    Livewire::test(Import::class)
        ->dispatch('native:' . FileChosen::class, filename: 'bad.alys', content: base64_encode('not-valid-json'))
        ->assertSet('error', true);
});

it('shows error when key is missing', function () {
    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn(null);

    Livewire::test(Import::class)
        ->dispatch('native:' . FileChosen::class, filename: 'backup.alys', content: base64_encode('{}'))
        ->assertSet('error', true);
});

it('transitions to previewing state after valid FileChosen', function () {
    ['key' => $key, 'alys' => $alys] = makeValidAlys();
    mockStorageKey($key);

    Livewire::test(Import::class)
        ->dispatch('native:' . FileChosen::class, filename: 'backup.alys', content: base64_encode($alys))
        ->assertSet('previewing', true)
        ->assertSet('previewData', fn($v) => ! empty($v));
});

it('imports successfully after FileChosen then confirmImport', function () {
    ['key' => $key, 'alys' => $alys] = makeValidAlys();
    mockStorageKey($key);

    Livewire::test(Import::class)
        ->dispatch('native:' . FileChosen::class, filename: 'backup.alys', content: base64_encode($alys))
        ->call('confirmImport')
        ->assertSet('success', true)
        ->assertDispatched('import-complete');
});

it('cancelPreview resets to idle', function () {
    ['key' => $key, 'alys' => $alys] = makeValidAlys();
    mockStorageKey($key);

    Livewire::test(Import::class)
        ->dispatch('native:' . FileChosen::class, filename: 'backup.alys', content: base64_encode($alys))
        ->assertSet('previewing', true)
        ->call('cancelPreview')
        ->assertSet('previewing', false)
        ->assertSet('previewData', []);
});

it('toggleProfile deselects profile and its treatments', function () {
    ['key' => $key, 'alys' => $alys] = makeValidAlys();
    mockStorageKey($key);

    $component = Livewire::test(Import::class)
        ->dispatch('native:' . FileChosen::class, filename: 'backup.alys', content: base64_encode($alys));

    $previewData    = $component->get('previewData');
    $firstProfileId = $previewData[0]['old_id'];

    $component->call('toggleProfile', $firstProfileId);

    $component->assertSet('selectedProfiles', fn($v) => ! in_array($firstProfileId, $v, true));
});
