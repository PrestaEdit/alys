<?php

use App\Services\CryptoService;

it('generates an EC P-256 key pair', function () {
    $crypto = new CryptoService();
    $pair = $crypto->generateKeyPair();

    expect($pair)->toHaveKeys(['private', 'public']);
    expect($pair['private'])->toContain('EC PRIVATE KEY');
    expect($pair['public'])->toContain('PUBLIC KEY');
});

it('encrypts to a valid envelope JSON', function () {
    $crypto = new CryptoService();
    $pair = $crypto->generateKeyPair();
    $envelope = $crypto->encrypt('{"hello":"world"}', $pair['public']);

    $parsed = json_decode($envelope, true);
    expect($parsed)->toHaveKeys(['v', 'alg', 'epk', 'iv', 'tag', 'ct']);
    expect($parsed['v'])->toBe(1);
    expect($parsed['alg'])->toBe('ECIES-P256-HKDF-AES256GCM');
});

it('decrypts back to original JSON', function () {
    $crypto = new CryptoService();
    $pair = $crypto->generateKeyPair();
    $original = '{"foo":"bar","num":42}';
    $envelope = $crypto->encrypt($original, $pair['public']);

    expect($crypto->decrypt($envelope, $pair['private']))->toBe($original);
});

it('throws on wrong private key', function () {
    $crypto = new CryptoService();
    $pair1 = $crypto->generateKeyPair();
    $pair2 = $crypto->generateKeyPair();
    $envelope = $crypto->encrypt('{"x":1}', $pair1['public']);

    expect(fn () => $crypto->decrypt($envelope, $pair2['private']))
        ->toThrow(\RuntimeException::class);
});

it('throws on tampered ciphertext', function () {
    $crypto = new CryptoService();
    $pair = $crypto->generateKeyPair();
    $envelope = $crypto->encrypt('{"x":1}', $pair['public']);

    $parsed = json_decode($envelope, true);
    $parsed['ct'] = base64_encode('tampered');
    $tampered = json_encode($parsed);

    expect(fn () => $crypto->decrypt($tampered, $pair['private']))
        ->toThrow(\RuntimeException::class);
});
