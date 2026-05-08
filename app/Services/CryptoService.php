<?php

namespace App\Services;

class CryptoService
{
    public function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    public function encrypt(string $json, string $keyBase64): string
    {
        $key = base64_decode($keyBase64, true);
        if ($key === false || strlen($key) !== 32) {
            throw new \RuntimeException('Invalid AES key');
        }

        $iv  = random_bytes(12);
        $tag = '';
        $ct  = openssl_encrypt($json, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

        if ($ct === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return json_encode([
            'v'   => 2,
            'iv'  => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct'  => base64_encode($ct),
        ], JSON_THROW_ON_ERROR);
    }

    public function decrypt(string $envelopeJson, string $keyBase64): string
    {
        $env = json_decode($envelopeJson, true, 512, JSON_THROW_ON_ERROR);

        if (($env['v'] ?? null) !== 2) {
            throw new \RuntimeException('Unknown envelope format (expected v:2)');
        }

        foreach (['iv', 'tag', 'ct'] as $field) {
            if (! isset($env[$field])) {
                throw new \RuntimeException("Missing envelope field: {$field}");
            }
        }

        $key = base64_decode($keyBase64, true);
        if ($key === false || strlen($key) !== 32) {
            throw new \RuntimeException('Invalid AES key');
        }

        $iv        = base64_decode($env['iv']);
        $tag       = base64_decode($env['tag']);
        $ct        = base64_decode($env['ct']);

        $plaintext = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed — wrong key or tampered data');
        }

        return $plaintext;
    }
}
