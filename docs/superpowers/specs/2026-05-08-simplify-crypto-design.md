# Simplification de la gestion des clés de chiffrement

**Date :** 2026-05-08  
**Statut :** Approuvé

## Contexte

Le système actuel utilise un chiffrement ECIES-P256 (clé éphémère + ECDH + HKDF + AES-256-GCM) avec une paire de clés asymétriques générées via OpenSSL EC (`prime256v1`). Deux bugs bloquants sur Android :

1. **La génération de clés EC échoue silencieusement sur Android** — `openssl_pkey_new(['curve_name' => 'prime256v1'])` retourne `false`, l'erreur est avalée par le `try/catch` global de `AppServiceProvider::boot()`. Résultat : `device_public_key` et `device_private_key` restent `null` dans SecureStorage → export impossible.

2. **Le scanner QR ne s'ouvre pas** — Le bouton utilise `import('#nativephp')` (API JavaScript pour Vue/React/Inertia), une syntaxe d'Import Map absente du layout Blade. La Promise échoue silencieusement. En Livewire, la bonne API est `Scanner::scan()` côté PHP.

## Décision

- Chiffrement **obligatoire** (données de santé)
- Mécanisme **simplifié** : clé symétrique AES-256 au lieu d'une paire EC asymétrique
- Transfert inter-appareils via **QR code** conservé

## Architecture cible

```
SecureStorage : "device_key" → 32 bytes aléatoires en base64 (~44 chars)

Export  : clé AES → AES-256-GCM → fichier .alys  (enveloppe v:2)
Import  : fichier .alys → AES-256-GCM → JSON
Transfert : clé AES base64 → QR → scan Livewire → SecureStorage
```

## Composants

### CryptoService (refactorisé)

Supprime : `generateKeyPair()`, `opensslConfig()`, ECIES, ECDH, HKDF, clé éphémère.

```php
class CryptoService
{
    public function generateKey(): string;
    // random_bytes(32) encodé en base64

    public function encrypt(string $json, string $keyBase64): string;
    // AES-256-GCM, IV aléatoire 12 bytes
    // Enveloppe JSON : { v:2, iv, tag, ct } — tout en base64

    public function decrypt(string $envelope, string $keyBase64): string;
    // Vérifie v:2, déchiffre AES-256-GCM
}
```

Le format `v:2` distingue les anciens fichiers `v:1` (EC) devenus illisibles — comportement identique au remplacement de clé actuel.

### AppServiceProvider

```php
private function bootstrapDeviceKeys(): void
{
    if (SecureStorage::get('device_key') !== null) return;
    SecureStorage::set('device_key', app(CryptoService::class)->generateKey());
}
```

Supprime : dérivation `public_key` depuis `private_key`, double stockage `device_private_key` / `device_public_key`.

### Dashboard (export)

```php
$key = SecureStorage::get('device_key');
if ($key === null) {
    $this->exportError = 'Clés non initialisées. Allez dans Réglages > Transfert de clés.';
    return;
}
$envelope = $exportService->generateEncrypted($key);
```

### KeyTransfer (scanner)

Le scanner passe entièrement côté PHP via la facade NativePHP :

```php
public function startScan(): void
{
    Scanner::scan()
        ->prompt('Scannez le QR code de votre ancien appareil')
        ->formats(['qr'])
        ->id(self::SCAN_ID);
}

#[OnNative(CodeScanned::class)]
public function handleScan(string $data, string $format, ?string $id = null): void
{ /* identique à aujourd'hui */ }
```

`showQr()` encode `device_key` directement — la clé AES base64 est déjà le QR content (~44 chars vs ~400 pour un PEM EC).

La logique de remplacement (`confirmReplace` / `pendingKey`) reste identique.

### ExportService / ImportService

Signatures inchangées. `generateEncrypted(string $keyBase64)` et `restore(string $content, string $keyBase64)` reçoivent la clé AES — seul l'intérieur de `CryptoService` change.

## Fichiers touchés

| Fichier | Changement |
|---|---|
| `app/Services/CryptoService.php` | Refactorisé entièrement (~30 lignes) |
| `app/Providers/AppServiceProvider.php` | `bootstrapDeviceKeys()` simplifié |
| `app/Livewire/Dashboard.php` | `device_key` au lieu de `device_public_key` |
| `app/Livewire/KeyTransfer.php` | `startScan()` + `showQr()` adapté |
| `resources/views/livewire/key-transfer.blade.php` | Bouton scanner → `wire:click="startScan"` |

## Ce qui ne change pas

- Flux utilisateur (export, import, transfert QR)
- Format du fichier `.alys` (JSON chiffré)
- `ExportService` et `ImportService` (signatures)
- Le message d'erreur si la clé est absente

## Migrations / rétrocompatibilité

Les fichiers `.alys` exportés avec `v:1` (EC) ne seront plus déchiffrables après la migration. C'est acceptable : le comportement est identique à un remplacement de clé dans le système actuel, et les utilisateurs n'ont pas encore de fichiers exportés valides (le bug empêche tout export).
