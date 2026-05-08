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

it('renders import component', function () {
    Livewire::test(Import::class)->assertStatus(200);
});

it('imports successfully via FileChosen event', function () {
    $crypto = new CryptoService();
    $key    = $crypto->generateKey();
    $alys   = (new ExportService())->generateEncrypted($key);

    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn($key);

    Livewire::test(Import::class)
        ->dispatch('native:' . FileChosen::class, filename: 'backup.alys', content: base64_encode($alys))
        ->assertSet('success', true)
        ->assertDispatched('import-complete');
});

it('shows error on invalid alys file', function () {
    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn((new CryptoService())->generateKey());

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
