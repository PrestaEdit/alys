<?php

namespace App\Services;

class CryptoService
{
    private const HKDF_INFO = 'alys-v1';

    private function opensslConfig(): string
    {
        return base_path('resources/openssl.cnf');
    }

    public function generateKeyPair(): array
    {
        $key = openssl_pkey_new([
            'curve_name'       => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'config'           => $this->opensslConfig(),
        ]);

        if ($key === false) {
            throw new \RuntimeException('Key generation failed: ' . openssl_error_string());
        }

        // PHP 8.2 + OpenSSL 3.x exports PKCS#8 ("BEGIN PRIVATE KEY").
        // openssl_pkey_get_private() accepts both PKCS#8 and SEC1 transparently.
        if (! openssl_pkey_export($key, $privatePem, null, ['config' => $this->opensslConfig()])) {
            throw new \RuntimeException('Key export failed: ' . openssl_error_string());
        }

        $details   = openssl_pkey_get_details($key);
        $publicPem = $details['key'];

        return ['private' => $privatePem, 'public' => $publicPem];
    }

    public function encrypt(string $json, string $recipientPublicPem): string
    {
        $ephemeral = openssl_pkey_new([
            'curve_name'       => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        $recipientPub = openssl_pkey_get_public($recipientPublicPem);

        if ($recipientPub === false) {
            throw new \RuntimeException('Invalid recipient public key');
        }

        $sharedSecret = openssl_pkey_derive($recipientPub, $ephemeral);
        if ($sharedSecret === false) {
            throw new \RuntimeException('ECDH failed: ' . openssl_error_string());
        }

        $aesKey = hash_hkdf('sha256', $sharedSecret, 32, self::HKDF_INFO);
        $iv     = random_bytes(12);
        $tag    = '';
        $ct     = openssl_encrypt($json, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

        if ($ct === false) {
            throw new \RuntimeException('Encryption failed');
        }

        $ephemeralDetails = openssl_pkey_get_details($ephemeral);

        return json_encode([
            'v'   => 1,
            'alg' => 'ECIES-P256-HKDF-AES256GCM',
            'epk' => base64_encode($ephemeralDetails['key']),
            'iv'  => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct'  => base64_encode($ct),
        ], JSON_THROW_ON_ERROR);
    }

    public function decrypt(string $envelopeJson, string $devicePrivatePem): string
    {
        $env = json_decode($envelopeJson, true, 512, JSON_THROW_ON_ERROR);

        if (($env['v'] ?? null) !== 1 || ($env['alg'] ?? '') !== 'ECIES-P256-HKDF-AES256GCM') {
            throw new \RuntimeException('Unknown envelope format');
        }

        foreach (['epk', 'iv', 'tag', 'ct'] as $field) {
            if (! isset($env[$field])) {
                throw new \RuntimeException("Missing envelope field: {$field}");
            }
        }

        $privKey      = openssl_pkey_get_private($devicePrivatePem);
        $ephemeralPub = openssl_pkey_get_public(base64_decode($env['epk']));

        if ($privKey === false || $ephemeralPub === false) {
            throw new \RuntimeException('Invalid key material');
        }

        $sharedSecret = openssl_pkey_derive($ephemeralPub, $privKey);
        if ($sharedSecret === false) {
            throw new \RuntimeException('ECDH failed');
        }

        $aesKey    = hash_hkdf('sha256', $sharedSecret, 32, self::HKDF_INFO);
        $iv        = base64_decode($env['iv']);
        $tag       = base64_decode($env['tag']);
        $ct        = base64_decode($env['ct']);

        $plaintext = openssl_decrypt($ct, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $iv, $tag);

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed — wrong key or tampered data');
        }

        return $plaintext;
    }
}
