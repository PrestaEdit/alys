<?php

use App\Services\CryptoService;

it('generates a 32-byte AES key encoded as base64', function () {
    $crypto = new CryptoService();
    $key = $crypto->generateKey();
    $decoded = base64_decode($key, true);

    expect(strlen($decoded))->toBe(32);
});

it('encrypts to a valid v:2 envelope JSON', function () {
    $crypto = new CryptoService();
    $key = $crypto->generateKey();
    $envelope = $crypto->encrypt('{"hello":"world"}', $key);

    $parsed = json_decode($envelope, true);
    expect($parsed)->toHaveKeys(['v', 'iv', 'tag', 'ct']);
    expect($parsed['v'])->toBe(2);
});

it('decrypts back to original JSON', function () {
    $crypto = new CryptoService();
    $key = $crypto->generateKey();
    $original = '{"foo":"bar","num":42}';
    $envelope = $crypto->encrypt($original, $key);

    expect($crypto->decrypt($envelope, $key))->toBe($original);
});

it('throws on wrong key', function () {
    $crypto = new CryptoService();
    $key1 = $crypto->generateKey();
    $key2 = $crypto->generateKey();
    $envelope = $crypto->encrypt('{"x":1}', $key1);

    expect(fn () => $crypto->decrypt($envelope, $key2))
        ->toThrow(\RuntimeException::class);
});

it('throws on tampered ciphertext', function () {
    $crypto = new CryptoService();
    $key = $crypto->generateKey();
    $envelope = $crypto->encrypt('{"x":1}', $key);

    $parsed = json_decode($envelope, true);
    $parsed['ct'] = base64_encode('tampered');
    $tampered = json_encode($parsed);

    expect(fn () => $crypto->decrypt($tampered, $key))
        ->toThrow(\RuntimeException::class);
});
