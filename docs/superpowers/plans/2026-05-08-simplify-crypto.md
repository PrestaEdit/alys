# Simplification du chiffrement — AES-256 symétrique

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers-extended-cc:subagent-driven-development (recommended) or superpowers-extended-cc:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remplacer le chiffrement ECIES-P256 (fragile sur Android) par AES-256-GCM symétrique, corriger le scanner QR Livewire, et mettre à jour tous les tests.

**Architecture:** Une clé AES-256 (`random_bytes(32)` en base64) est générée une fois et stockée dans SecureStorage sous `device_key`. L'export chiffre avec AES-256-GCM (enveloppe v:2), l'import déchiffre avec la même clé, et le transfert inter-appareils encode cette clé dans un QR scanné via `Scanner::scan()` PHP.

**Tech Stack:** Laravel 11, Livewire 3, Pest, NativePHP Mobile (SecureStorage, Scanner), PHP `openssl_encrypt`/`openssl_decrypt`.

---

### Task 1 : Refactoriser CryptoService + ExportService

**Goal:** Remplacer ECIES-P256 par AES-256-GCM dans CryptoService, adapter la signature de ExportService, mettre les tests à jour.

**Files:**
- Modify: `app/Services/CryptoService.php`
- Modify: `app/Services/ExportService.php`
- Modify: `tests/Feature/CryptoServiceTest.php`

**Acceptance Criteria:**
- [ ] `CryptoService::generateKey()` retourne une chaîne base64 de 32 bytes décodés
- [ ] `encrypt()` produit une enveloppe JSON avec `v:2`, `iv`, `tag`, `ct`
- [ ] `decrypt()` retrouve le JSON original avec la même clé
- [ ] `decrypt()` lance une exception si la clé est différente
- [ ] `decrypt()` lance une exception si le ciphertext est altéré
- [ ] `ExportService::generateEncrypted()` accepte une clé AES base64
- [ ] Tous les tests Pest passent : `php artisan test --filter=CryptoServiceTest`

**Verify:** `php artisan test --filter=CryptoServiceTest` → 5 tests, 5 passed

**Steps:**

- [ ] **Étape 1 : Réécrire les tests CryptoService**

Remplacer entièrement `tests/Feature/CryptoServiceTest.php` :

```php
<?php

use App\Services\CryptoService;

it('generates a 32-byte AES key encoded as base64', function () {
    $crypto = new CryptoService();
    $key = $crypto->generateKey();

    expect(base64_decode($key, true))->toHaveLength(32);
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
```

- [ ] **Étape 2 : Vérifier que les tests échouent**

```
php artisan test --filter=CryptoServiceTest
```

Attendu : FAILED (méthodes `generateKey` inconnues, etc.)

- [ ] **Étape 3 : Réécrire CryptoService**

Remplacer entièrement `app/Services/CryptoService.php` :

```php
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
```

- [ ] **Étape 4 : Adapter ExportService**

Dans `app/Services/ExportService.php`, changer la signature de `generateEncrypted()` :

```php
public function generateEncrypted(string $keyBase64): string
{
    $json = $this->generate();
    return app(\App\Services\CryptoService::class)->encrypt($json, $keyBase64);
}
```

- [ ] **Étape 5 : Vérifier que les tests passent**

```
php artisan test --filter=CryptoServiceTest
```

Attendu : 5 tests, 5 passed

- [ ] **Étape 6 : Commit**

```bash
git add app/Services/CryptoService.php app/Services/ExportService.php tests/Feature/CryptoServiceTest.php
git commit -m "refactor: replace ECIES-P256 with AES-256-GCM in CryptoService"
```

---

### Task 2 : Adapter ImportService + ses tests

**Goal:** Renommer `$devicePrivatePem` en `$keyBase64` dans `ImportService::restore()` et mettre à jour les tests.

**Files:**
- Modify: `app/Services/ImportService.php`
- Modify: `tests/Feature/ImportServiceTest.php`

**Acceptance Criteria:**
- [ ] `ImportService::restore()` accepte une clé AES base64 en second paramètre
- [ ] Tous les tests ImportService passent

**Verify:** `php artisan test --filter=ImportServiceTest` → 6 tests, 6 passed

**Steps:**

- [ ] **Étape 1 : Mettre à jour les tests ImportService**

Remplacer entièrement `tests/Feature/ImportServiceTest.php` :

```php
<?php

use App\Models\CalendarEvent;
use App\Models\PosologyHistory;
use App\Models\Treatment;
use App\Services\CryptoService;
use App\Services\ExportService;
use App\Services\ImportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

function makeAlysFile(): array
{
    $crypto = new CryptoService();
    $key    = $crypto->generateKey();
    $alys   = (new ExportService())->generateEncrypted($key);
    return ['alys' => $alys, 'key' => $key];
}

it('imports treatments from alys file', function () {
    ['alys' => $alys, 'key' => $key] = makeAlysFile();

    $originalCount = Treatment::count();
    expect($originalCount)->toBeGreaterThan(0);

    Treatment::withoutGlobalScopes()->forceDelete();
    expect(Treatment::withoutGlobalScopes()->count())->toBe(0);

    $importer = new ImportService(new CryptoService());
    $importer->restore($alys, $key);

    expect(Treatment::count())->toBe($originalCount);
});

it('imports posology_history from alys file', function () {
    ['alys' => $alys, 'key' => $key] = makeAlysFile();

    $originalCount = PosologyHistory::count();
    PosologyHistory::withoutGlobalScopes()->delete();

    $importer = new ImportService(new CryptoService());
    $importer->restore($alys, $key);

    expect(PosologyHistory::count())->toBe($originalCount);
});

it('imports calendar_events from alys file', function () {
    ['alys' => $alys, 'key' => $key] = makeAlysFile();

    $originalCount = CalendarEvent::count();
    CalendarEvent::withoutGlobalScopes()->delete();

    $importer = new ImportService(new CryptoService());
    $importer->restore($alys, $key);

    expect(CalendarEvent::count())->toBe($originalCount);
});

it('does not duplicate on second import', function () {
    ['alys' => $alys, 'key' => $key] = makeAlysFile();

    $importer = new ImportService(new CryptoService());
    $importer->restore($alys, $key);
    $countAfterFirst = Treatment::count();

    $importer->restore($alys, $key);
    expect(Treatment::count())->toBe($countAfterFirst);
});

it('throws on wrong key', function () {
    ['alys' => $alys] = makeAlysFile();
    $otherKey = (new CryptoService())->generateKey();

    expect(fn () => (new ImportService(new CryptoService()))->restore($alys, $otherKey))
        ->toThrow(\RuntimeException::class);
});

it('throws on malformed alys content', function () {
    expect(fn () => (new ImportService(new CryptoService()))->restore('not-json', 'fakekey'))
        ->toThrow(\RuntimeException::class);
});
```

- [ ] **Étape 2 : Vérifier que les tests échouent**

```
php artisan test --filter=ImportServiceTest
```

Attendu : FAILED (signature mismatch)

- [ ] **Étape 3 : Modifier ImportService**

Dans `app/Services/ImportService.php`, changer uniquement la signature de `restore()` :

```php
public function restore(string $alysContent, string $keyBase64): void
{
    try {
        $json = $this->crypto->decrypt($alysContent, $keyBase64);
    } catch (\Throwable $e) {
        throw new \RuntimeException('Decryption failed: ' . $e->getMessage(), 0, $e);
    }

    try {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        throw new \RuntimeException('Malformed JSON in alys file: ' . $e->getMessage(), 0, $e);
    }

    if (! isset($data['treatments'], $data['posology_history'], $data['calendar_events'])) {
        throw new \RuntimeException('Malformed export data');
    }

    $this->importTreatments($data['treatments']);
    $this->importHistory($data['posology_history']);
    $this->importEvents($data['calendar_events']);
}
```

Le reste de la classe (`importTreatments`, `importHistory`, `importEvents`) reste identique.

- [ ] **Étape 4 : Vérifier que les tests passent**

```
php artisan test --filter=ImportServiceTest
```

Attendu : 6 tests, 6 passed

- [ ] **Étape 5 : Commit**

```bash
git add app/Services/ImportService.php tests/Feature/ImportServiceTest.php
git commit -m "refactor: ImportService restore() accepts AES key instead of private PEM"
```

---

### Task 3 : Simplifier AppServiceProvider

**Goal:** `bootstrapDeviceKeys()` génère une seule clé AES sous `device_key` et supprime `device_private_key` / `device_public_key`.

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`

**Acceptance Criteria:**
- [ ] `bootstrapDeviceKeys()` stocke `device_key` dans SecureStorage si absent
- [ ] Plus de référence à `device_private_key` ou `device_public_key` dans AppServiceProvider
- [ ] La suite de tests complète passe : `php artisan test`

**Verify:** `php artisan test` → all passed (nombre identique ou supérieur)

**Steps:**

- [ ] **Étape 1 : Modifier AppServiceProvider**

Dans `app/Providers/AppServiceProvider.php`, remplacer la méthode `bootstrapDeviceKeys()` :

```php
private function bootstrapDeviceKeys(): void
{
    if (\Native\Mobile\Facades\SecureStorage::get('device_key') !== null) {
        return;
    }

    $key = $this->app->make(\App\Services\CryptoService::class)->generateKey();
    \Native\Mobile\Facades\SecureStorage::set('device_key', $key);
}
```

La méthode `boot()` et `register()` restent identiques. Seule `bootstrapDeviceKeys()` change.

- [ ] **Étape 2 : Vérifier**

```
php artisan test
```

Attendu : tous les tests passent (les tests Livewire mockent SecureStorage, ils ne dépendent pas de l'ancienne clé)

- [ ] **Étape 3 : Commit**

```bash
git add app/Providers/AppServiceProvider.php
git commit -m "refactor: AppServiceProvider generates single AES device_key"
```

---

### Task 4 : Adapter Dashboard (export) + test

**Goal:** `Dashboard::export()` lit `device_key` au lieu de `device_public_key`, et le test est mis à jour.

**Files:**
- Modify: `app/Livewire/Dashboard.php`
- Modify: `tests/Feature/Livewire/DashboardTest.php`

**Acceptance Criteria:**
- [ ] `export()` lit `SecureStorage::get('device_key')`
- [ ] Si `device_key` est null, `exportError` contient le message d'erreur approprié
- [ ] Le test d'export mock `device_key` et vérifie l'enveloppe v:2
- [ ] `php artisan test --filter=DashboardTest` passe

**Verify:** `php artisan test --filter=DashboardTest` → all passed

**Steps:**

- [ ] **Étape 1 : Mettre à jour le test DashboardTest**

Dans `tests/Feature/Livewire/DashboardTest.php`, remplacer le test `export` :

```php
it('export runs without error and writes an alys file', function () {
    $crypto = new \App\Services\CryptoService();
    $key = $crypto->generateKey();

    \Native\Mobile\Facades\SecureStorage::shouldReceive('get')
        ->with('device_key')
        ->andReturn($key);

    Livewire::test(Dashboard::class)
        ->call('export')
        ->assertStatus(200);

    $files = glob(storage_path('app/alys-traitement-*.alys'));
    expect($files)->not->toBeEmpty();

    $envelope = json_decode(file_get_contents($files[0]), true);
    expect($envelope)->toHaveKeys(['v', 'iv', 'tag', 'ct']);
    expect($envelope['v'])->toBe(2);

    $json = $crypto->decrypt(file_get_contents($files[0]), $key);
    $data = json_decode($json, true);
    expect($data)->toHaveKeys(['settings', 'treatments', 'posology_history', 'calendar_events', 'exported_at']);
});
```

- [ ] **Étape 2 : Vérifier que le test échoue**

```
php artisan test --filter="export runs without error"
```

Attendu : FAILED

- [ ] **Étape 3 : Modifier Dashboard::export()**

Dans `app/Livewire/Dashboard.php`, remplacer le corps de la méthode `export()` :

```php
public function export(ExportService $exportService): void
{
    $this->exportError = '';
    $this->exportLoading = true;

    try {
        $key = \Native\Mobile\Facades\SecureStorage::get('device_key');

        if ($key === null) {
            $this->exportError = 'Clés non initialisées. Allez dans Réglages > Transfert de clés.';
            return;
        }

        $envelope = $exportService->generateEncrypted($key);

        $tempDir  = config('nativephp-internal.tempdir') ?: sys_get_temp_dir();
        $filename = 'alys-traitement-' . now()->format('Y-m-d') . '.alys';
        $path     = rtrim($tempDir, '/') . '/' . $filename;
        $written  = file_put_contents($path, $envelope);

        if ($written === false) {
            $this->exportError = 'Impossible d\'écrire dans : ' . $path;
            return;
        }

        \Native\Mobile\Facades\Share::file(
            'Alys Traitement',
            'Export chiffré du calendrier de traitement',
            $path
        );
    } catch (\Throwable $e) {
        $this->exportError = get_class($e) . ': ' . $e->getMessage() . ' — ' . basename($e->getFile()) . ':' . $e->getLine();
    } finally {
        $this->exportLoading = false;
    }
}
```

- [ ] **Étape 4 : Vérifier**

```
php artisan test --filter=DashboardTest
```

Attendu : all passed

- [ ] **Étape 5 : Commit**

```bash
git add app/Livewire/Dashboard.php tests/Feature/Livewire/DashboardTest.php
git commit -m "refactor: Dashboard export uses device_key (AES) instead of device_public_key"
```

---

### Task 5 : Adapter Import.php Livewire + test

**Goal:** `Import::import()` lit `device_key` au lieu de `device_private_key`.

**Files:**
- Modify: `app/Livewire/Import.php`
- Modify: `tests/Feature/Livewire/ImportTest.php`

**Acceptance Criteria:**
- [ ] `Import::import()` lit `SecureStorage::get('device_key')`
- [ ] Si absent, message d'erreur affiché
- [ ] `php artisan test --filter=ImportTest` passe

**Verify:** `php artisan test --filter=ImportTest` → all passed

**Steps:**

- [ ] **Étape 1 : Mettre à jour les tests ImportTest**

Remplacer entièrement `tests/Feature/Livewire/ImportTest.php` :

```php
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
```

- [ ] **Étape 2 : Vérifier que les tests échouent**

```
php artisan test --filter=ImportTest
```

Attendu : FAILED (mock `device_private_key` ne correspond pas)

- [ ] **Étape 3 : Modifier Import.php**

Dans `app/Livewire/Import.php`, remplacer le début de la méthode `import()` :

```php
public function import(ImportService $importer): void
{
    $this->validate(['file' => 'required|file|max:10240']);

    $key = SecureStorage::get('device_key');

    if ($key === null) {
        $this->error = true;
        $this->errorMessage = 'Clés de chiffrement introuvables. Effectuez un transfert de clés depuis votre ancien appareil.';
        return;
    }

    try {
        $content = file_get_contents($this->file->getRealPath());
        $importer->restore($content, $key);
        $this->dispatch('import-complete');
        $this->redirectRoute('home');
    } catch (\Throwable $e) {
        $this->error = true;
        $this->errorMessage = 'Fichier invalide ou chiffré avec une autre clé.';
    }
}
```

- [ ] **Étape 4 : Vérifier**

```
php artisan test --filter=ImportTest
```

Attendu : all passed

- [ ] **Étape 5 : Commit**

```bash
git add app/Livewire/Import.php tests/Feature/Livewire/ImportTest.php
git commit -m "refactor: Import Livewire reads device_key instead of device_private_key"
```

---

### Task 6 : Adapter KeyTransfer — scanner PHP + QR + tests + vue

**Goal:** Déclencher le scanner via `Scanner::scan()` PHP (corrige le bug de l'`import('#nativephp')`), adapter `showQr()` pour encoder `device_key`, et mettre tous les tests à jour.

**Files:**
- Modify: `app/Livewire/KeyTransfer.php`
- Modify: `resources/views/livewire/key-transfer.blade.php`
- Modify: `tests/Feature/Livewire/KeyTransferTest.php`

**Acceptance Criteria:**
- [ ] `KeyTransfer::startScan()` appelle `Scanner::scan()` avec prompt, formats et id
- [ ] `KeyTransfer::showQr()` expose `device_key` dans `$qrContent`
- [ ] `storeScannedKey()` valide la clé AES base64 (32 bytes décodés) et stocke `device_key`
- [ ] Le bouton scanner dans la vue utilise `wire:click="startScan"` sans JavaScript
- [ ] `handleScan()` vérifie `device_key` (et non `device_private_key`)
- [ ] `php artisan test --filter=KeyTransferTest` passe

**Verify:** `php artisan test --filter=KeyTransferTest` → all passed

**Steps:**

- [ ] **Étape 1 : Mettre à jour les tests KeyTransferTest**

Remplacer entièrement `tests/Feature/Livewire/KeyTransferTest.php` :

```php
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
```

- [ ] **Étape 2 : Vérifier que les tests échouent**

```
php artisan test --filter=KeyTransferTest
```

Attendu : FAILED

- [ ] **Étape 3 : Réécrire KeyTransfer.php**

Remplacer entièrement `app/Livewire/KeyTransfer.php` :

```php
<?php

namespace App\Livewire;

use App\Services\CryptoService;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Scanner\CodeScanned;
use Native\Mobile\Facades\Scanner;
use Native\Mobile\Facades\SecureStorage;

class KeyTransfer extends Component
{
    public ?string $qrContent = null;
    public bool $confirmReplace = false;
    public ?string $pendingKey = null;
    public bool $importSuccess = false;
    public string $error = '';

    private const SCAN_ID = 'key-transfer';

    public function showQr(): void
    {
        $this->error = '';
        $key = SecureStorage::get('device_key');

        if ($key === null) {
            try {
                $key = app(CryptoService::class)->generateKey();
                SecureStorage::set('device_key', $key);
            } catch (\Throwable) {
                $this->error = 'Impossible de générer les clés. Veuillez relancer l\'application.';
                return;
            }
        }

        $this->qrContent = $key;
    }

    public function startScan(): void
    {
        Scanner::scan()
            ->prompt('Scannez le QR code de votre ancien appareil')
            ->formats(['qr'])
            ->id(self::SCAN_ID);
    }

    #[OnNative(CodeScanned::class)]
    public function handleScan(string $data, string $format, ?string $id = null): void
    {
        if ($id !== self::SCAN_ID) {
            return;
        }

        $existingKey = SecureStorage::get('device_key');

        if ($existingKey !== null) {
            $this->pendingKey     = $data;
            $this->confirmReplace = true;
            return;
        }

        $this->storeScannedKey($data);
    }

    public function confirmReplaceKeys(): void
    {
        if ($this->pendingKey === null) {
            return;
        }

        $this->storeScannedKey($this->pendingKey);
        $this->confirmReplace = false;
        $this->pendingKey     = null;
    }

    public function cancelReplace(): void
    {
        $this->confirmReplace = false;
        $this->pendingKey     = null;
    }

    private function storeScannedKey(string $keyBase64): void
    {
        $decoded = base64_decode($keyBase64, true);
        if ($decoded === false || strlen($decoded) !== 32) {
            $this->error = 'Clé invalide — le QR code ne contient pas une clé valide.';
            return;
        }

        SecureStorage::set('device_key', $keyBase64);
        $this->importSuccess = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.key-transfer')
            ->layout('layouts.app', ['title' => 'Transfert de clés']);
    }
}
```

- [ ] **Étape 4 : Mettre à jour la vue key-transfer.blade.php**

Remplacer le bouton scanner (lignes 73-82 actuellement) dans `resources/views/livewire/key-transfer.blade.php` :

```html
<button wire:click="startScan"
        class="w-full bg-blue-600 text-white font-semibold py-3 rounded-2xl text-sm">
    Scanner le QR code d'un autre appareil
</button>
```

La section `@if($qrContent)` dans la vue reste identique — elle passe déjà `$qrContent` à `QRCode.toCanvas()` via Alpine.js. La clé AES base64 (~44 chars) est un contenu QR valide et bien plus léger qu'un PEM EC.

La vue complète finale de `resources/views/livewire/key-transfer.blade.php` :

```html
<div class="p-4 max-w-lg mx-auto">

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('settings') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </a>
        <h1 class="text-xl font-extrabold text-slate-900">Transfert de clés</h1>
    </div>

    @if($error)
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-4 text-sm text-red-700">
            {{ $error }}
        </div>
    @endif

    @if($importSuccess)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-4 mb-4 text-sm text-green-700">
            Clés importées avec succès. Vos exports seront maintenant déchiffrables sur cet appareil.
        </div>
    @endif

    @if($confirmReplace)
        <div class="bg-amber-50 border border-amber-300 rounded-2xl p-5 mb-4">
            <p class="text-sm font-semibold text-amber-800 mb-3">
                Des clés existent déjà sur cet appareil. Les remplacer rendra illisibles les exports précédents chiffrés avec ces clés. Confirmer ?
            </p>
            <div class="flex gap-3">
                <button wire:click="confirmReplaceKeys"
                        class="flex-1 bg-amber-600 text-white font-semibold py-2 rounded-xl text-sm">
                    Remplacer
                </button>
                <button wire:click="cancelReplace"
                        class="flex-1 bg-white border border-amber-300 text-amber-700 font-semibold py-2 rounded-xl text-sm">
                    Annuler
                </button>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Appareil source</p>
        <p class="text-sm text-slate-600 mb-4">
            Générez le QR code de votre clé, puis scannez-le depuis votre nouvel appareil pour lui transférer l'accès.
        </p>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4">
            <p class="text-xs text-amber-700">
                ⚠️ Ce QR code donne accès à tous vos exports. Ne le montrez qu'à votre nouvel appareil.
            </p>
        </div>

        <button wire:click="showQr"
                class="w-full bg-slate-800 text-white font-semibold py-3 rounded-2xl text-sm">
            Afficher le QR code de ma clé
        </button>

        @if($qrContent)
            <div class="mt-4 flex justify-center"
                 x-data
                 x-init="QRCode.toCanvas($refs.qrCanvas, @js($qrContent), { width: 224, margin: 1 })">
                <canvas x-ref="qrCanvas" class="rounded-xl"></canvas>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-sm">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Nouvel appareil</p>
        <p class="text-sm text-slate-600 mb-4">
            Scannez le QR code affiché sur votre ancien appareil pour récupérer les clés de chiffrement.
        </p>

        <button wire:click="startScan"
                class="w-full bg-blue-600 text-white font-semibold py-3 rounded-2xl text-sm">
            Scanner le QR code d'un autre appareil
        </button>
    </div>

</div>
```

- [ ] **Étape 5 : Vérifier**

```
php artisan test --filter=KeyTransferTest
```

Attendu : 6 tests, 6 passed

- [ ] **Étape 6 : Vérifier la suite complète**

```
php artisan test
```

Attendu : tous les tests passent

- [ ] **Étape 7 : Commit**

```bash
git add app/Livewire/KeyTransfer.php resources/views/livewire/key-transfer.blade.php tests/Feature/Livewire/KeyTransferTest.php
git commit -m "fix: KeyTransfer uses Scanner::scan() PHP API and AES device_key"
```
