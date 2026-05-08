<?php

use App\Livewire\KeyTransfer;
use App\Services\CryptoService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

it('renders key transfer component', function () {
    Livewire::test(KeyTransfer::class)->assertStatus(200);
});

it('showQr exposes the AES key as qrContent', function () {
    $key = (new CryptoService())->generateKey();

    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn($key);

    $component = Livewire::test(KeyTransfer::class)
        ->call('showQr');

    expect($component->get('qrContent'))->toBe($key);
    expect($component->get('error'))->toBeEmpty();
});

it('showQr generates and stores a key when none exists', function () {
    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn(null);
    \Native\Mobile\Facades\SecureStorage::shouldReceive('set')
        ->with('device_key', \Mockery::type('string'))
        ->once();

    $component = Livewire::test(KeyTransfer::class)
        ->call('showQr');

    expect($component->get('error'))->toBeEmpty();
    expect($component->get('qrContent'))->not->toBeNull();
});

it('stores scanned AES key in SecureStorage when no existing key', function () {
    $newKey = (new CryptoService())->generateKey();

    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn(null);
    \Native\Mobile\Facades\SecureStorage::shouldReceive('set')
        ->with('device_key', $newKey)
        ->once();

    Livewire::test(KeyTransfer::class)
        ->call('handleScan', $newKey, 'qr', 'key-transfer')
        ->assertSet('importSuccess', true);
});

it('requires confirmation when a key already exists', function () {
    $existingKey = (new CryptoService())->generateKey();
    $newKey      = (new CryptoService())->generateKey();

    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn($existingKey);

    Livewire::test(KeyTransfer::class)
        ->call('handleScan', $newKey, 'qr', 'key-transfer')
        ->assertSet('pendingKey', $newKey)
        ->assertSet('confirmReplace', true);
});

it('replaces key after confirmation', function () {
    $existingKey = (new CryptoService())->generateKey();
    $newKey      = (new CryptoService())->generateKey();

    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn($existingKey);
    \Native\Mobile\Facades\SecureStorage::shouldReceive('set')
        ->with('device_key', $newKey)
        ->once();

    Livewire::test(KeyTransfer::class)
        ->set('pendingKey', $newKey)
        ->call('confirmReplaceKeys')
        ->assertSet('importSuccess', true);
});

it('shows error on invalid scanned key', function () {
    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn(null);

    Livewire::test(KeyTransfer::class)
        ->call('handleScan', 'not-a-valid-aes-key', 'qr', 'key-transfer')
        ->assertSet('error', 'Clé invalide — le QR code ne contient pas une clé valide.');
});
