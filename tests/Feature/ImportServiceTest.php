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
    $pair = $crypto->generateKeyPair();
    $export = new ExportService();
    $alys = $export->generateEncrypted($pair['public']);
    return ['alys' => $alys, 'private' => $pair['private']];
}

it('imports treatments from alys file', function () {
    ['alys' => $alys, 'private' => $priv] = makeAlysFile();

    $originalCount = Treatment::count();
    expect($originalCount)->toBeGreaterThan(0);

    Treatment::withoutGlobalScopes()->forceDelete();
    expect(Treatment::withoutGlobalScopes()->count())->toBe(0);

    $importer = new ImportService(new CryptoService());
    $importer->restore($alys, $priv);

    expect(Treatment::count())->toBe($originalCount);
});

it('imports posology_history from alys file', function () {
    ['alys' => $alys, 'private' => $priv] = makeAlysFile();

    $originalCount = PosologyHistory::count();
    PosologyHistory::withoutGlobalScopes()->delete();

    $importer = new ImportService(new CryptoService());
    $importer->restore($alys, $priv);

    expect(PosologyHistory::count())->toBe($originalCount);
});

it('imports calendar_events from alys file', function () {
    ['alys' => $alys, 'private' => $priv] = makeAlysFile();

    $originalCount = CalendarEvent::count();
    CalendarEvent::withoutGlobalScopes()->delete();

    $importer = new ImportService(new CryptoService());
    $importer->restore($alys, $priv);

    expect(CalendarEvent::count())->toBe($originalCount);
});

it('does not duplicate on second import', function () {
    ['alys' => $alys, 'private' => $priv] = makeAlysFile();

    $importer = new ImportService(new CryptoService());
    $importer->restore($alys, $priv);
    $countAfterFirst = Treatment::count();

    $importer->restore($alys, $priv);
    expect(Treatment::count())->toBe($countAfterFirst);
});

it('throws on wrong private key', function () {
    ['alys' => $alys] = makeAlysFile();
    $otherPair = (new CryptoService())->generateKeyPair();

    expect(fn () => (new ImportService(new CryptoService()))->restore($alys, $otherPair['private']))
        ->toThrow(\RuntimeException::class);
});

it('throws on malformed alys content', function () {
    expect(fn () => (new ImportService(new CryptoService()))->restore('not-json', 'fake'))
        ->toThrow(\RuntimeException::class);
});
