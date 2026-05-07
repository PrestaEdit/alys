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

it('showQr generates a QR data URI', function () {
    $pair = (new CryptoService())->generateKeyPair();

    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_private_key')
        ->andReturn($pair['private']);

    $component = Livewire::test(KeyTransfer::class)
        ->call('showQr');

    expect($component->get('qrDataUri'))->toStartWith('data:image/png;base64,');
});

it('showQr shows error when no private key exists', function () {
    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_private_key')
        ->andReturn(null);

    $component = Livewire::test(KeyTransfer::class)
        ->call('showQr');

    expect($component->get('error'))->not->toBeEmpty();
});

it('stores scanned key in SecureStorage when no existing keys', function () {
    $newPair = (new CryptoService())->generateKeyPair();

    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_private_key')
        ->andReturn(null);
    \Native\Mobile\Facades\SecureStorage::shouldReceive('set')
        ->with('device_private_key', $newPair['private'])
        ->once();
    \Native\Mobile\Facades\SecureStorage::shouldReceive('set')
        ->with('device_public_key', \Mockery::any())
        ->once();

    Livewire::test(KeyTransfer::class)
        ->call('handleScan', $newPair['private'], 'qr', 'key-transfer')
        ->assertSet('importSuccess', true);
});

it('requires confirmation when keys already exist', function () {
    $existingPair = (new CryptoService())->generateKeyPair();
    $newPair = (new CryptoService())->generateKeyPair();

    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_private_key')
        ->andReturn($existingPair['private']);

    Livewire::test(KeyTransfer::class)
        ->call('handleScan', $newPair['private'], 'qr', 'key-transfer')
        ->assertSet('pendingKey', $newPair['private'])
        ->assertSet('confirmReplace', true);
});

it('replaces keys after confirmation', function () {
    $existingPair = (new CryptoService())->generateKeyPair();
    $newPair = (new CryptoService())->generateKeyPair();

    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_private_key')
        ->andReturn($existingPair['private']);
    \Native\Mobile\Facades\SecureStorage::shouldReceive('set')->twice();

    Livewire::test(KeyTransfer::class)
        ->set('pendingKey', $newPair['private'])
        ->call('confirmReplaceKeys')
        ->assertSet('importSuccess', true);
});
