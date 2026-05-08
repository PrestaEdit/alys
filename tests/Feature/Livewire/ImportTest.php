<?php

use App\Livewire\Import;
use App\Services\CryptoService;
use App\Services\ExportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

it('renders import component', function () {
    Livewire::test(Import::class)->assertStatus(200);
});

it('imports successfully with valid alys file', function () {
    $crypto = new CryptoService();
    $key    = $crypto->generateKey();
    $alys   = (new ExportService())->generateEncrypted($key);

    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn($key);

    $file = UploadedFile::fake()->createWithContent('backup.alys', $alys);

    Livewire::test(Import::class)
        ->set('file', $file)
        ->call('import')
        ->assertDispatched('import-complete');
});

it('shows error on invalid alys file', function () {
    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn((new CryptoService())->generateKey());

    $file = UploadedFile::fake()->createWithContent('bad.alys', 'not-valid-json');

    Livewire::test(Import::class)
        ->set('file', $file)
        ->call('import')
        ->assertSet('error', true);
});

it('shows error when key is missing', function () {
    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn(null);

    $file = UploadedFile::fake()->createWithContent('backup.alys', '{}');

    Livewire::test(Import::class)
        ->set('file', $file)
        ->call('import')
        ->assertSet('error', true);
});
