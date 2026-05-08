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
