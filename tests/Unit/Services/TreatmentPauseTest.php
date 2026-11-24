<?php

use App\Livewire\TreatmentEdit;
use App\Models\CalendarEvent;
use App\Models\Setting;
use App\Models\Treatment;
use App\Services\CalendarService;
use App\Services\LinkedEventSync;
use App\Services\NotificationScheduler;
use Carbon\Carbon;
use Ikromjon\LocalNotifications\Facades\LocalNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
    // Mardi 24 novembre 2026, 10:00
    Carbon::setTestNow('2026-11-24 10:00:00');

    $this->daily = Treatment::create([
        'name' => 'Purinéthol', 'type' => 'daily', 'color' => '#6366f1',
        'notification_enabled' => true, 'notification_time_morning' => '08:00',
        'notification_time_evening' => '20:00', 'dose_morning' => 1, 'dose_evening' => 1,
    ]);
    $this->cyclic = Treatment::create([
        'name' => 'Vincristine', 'type' => 'cyclic', 'color' => '#f97316',
        'frequency_weeks' => 1, 'recurrence_start' => '2026-11-24',
        'notification_enabled' => true, 'notification_time_morning' => '09:00',
    ]);
    foreach (['2026-11-26', '2026-12-03', '2026-12-10'] as $date) {
        CalendarEvent::create(['treatment_id' => $this->cyclic->id, 'scheduled_date' => $date]);
    }

    $this->calendar = app(CalendarService::class);
    $this->namesOn  = fn (string $d) => collect($this->calendar->getEventsForDay(Carbon::parse($d)))->pluck('name')->all();
});

afterEach(fn () => Carbon::setTestNow());

function captureNotifications(): ArrayObject
{
    $scheduled = new ArrayObject();
    LocalNotifications::shouldReceive('cancel')->andReturn([]);
    LocalNotifications::shouldReceive('schedule')->andReturnUsing(function ($opts) use ($scheduled) {
        $scheduled[] = $opts;
        return [];
    });
    return $scheduled;
}

function fireTimes(ArrayObject $scheduled): array
{
    return collect($scheduled->getArrayCopy())
        ->map(fn ($o) => Carbon::createFromTimestamp($o['at'], config('app.timezone'))->format('Y-m-d H:i'))
        ->values()->all();
}

it('hides a paused daily treatment from the calendar during the pause only', function () {
    $this->daily->pause(Carbon::parse('2026-11-24'), Carbon::parse('2026-11-27'));

    expect(($this->namesOn)('2026-11-23'))->toContain('Purinéthol')
        ->and(($this->namesOn)('2026-11-24'))->not->toContain('Purinéthol')
        ->and(($this->namesOn)('2026-11-27'))->not->toContain('Purinéthol')
        ->and(($this->namesOn)('2026-11-28'))->toContain('Purinéthol');
});

it('keeps an open-ended pause until resumed', function () {
    $this->daily->pause(Carbon::parse('2026-11-24'));

    expect(($this->namesOn)('2027-03-01'))->not->toContain('Purinéthol');

    Carbon::setTestNow('2026-11-30 10:00:00');
    $this->daily->fresh()->resume();

    $t = $this->daily->fresh();
    expect($t->paused_until->toDateString())->toBe('2026-11-29')
        ->and(($this->namesOn)('2026-11-27'))->not->toContain('Purinéthol')
        ->and(($this->namesOn)('2026-11-30'))->toContain('Purinéthol');
});

it('cancels a pause that has not started yet', function () {
    $this->daily->pause(Carbon::parse('2026-11-28'), Carbon::parse('2026-11-30'));
    $this->daily->fresh()->resume();

    $t = $this->daily->fresh();
    expect($t->paused_from)->toBeNull()->and($t->paused_until)->toBeNull();
});

it('hides calendar occurrences in the pause without cancelling them', function () {
    $this->cyclic->pause(Carbon::parse('2026-11-24'), Carbon::parse('2026-12-05'));

    expect(($this->namesOn)('2026-11-26'))->not->toContain('Vincristine')
        ->and(($this->namesOn)('2026-12-03'))->not->toContain('Vincristine')
        ->and(($this->namesOn)('2026-12-10'))->toContain('Vincristine');

    $month = $this->calendar->getEventsForMonth(2026, 12);
    expect(collect($month['2026-12-03'] ?? [])->pluck('name'))->not->toContain('Vincristine')
        ->and(collect($month['2026-12-10'])->pluck('name'))->toContain('Vincristine')
        ->and(CalendarEvent::where('is_cancelled', true)->count())->toBe(0);
});

it('pauses linked treatments along with their parent', function () {
    $child = Treatment::create([
        'name' => 'Dexamethasone', 'type' => 'daily', 'color' => '#3b82f6',
        'parent_treatment_id' => $this->cyclic->id, 'linked_days' => 2,
    ]);
    app(LinkedEventSync::class)->syncChild($child);

    expect(($this->namesOn)('2026-11-27'))->toContain('Dexamethasone');

    $this->cyclic->pause(Carbon::parse('2026-11-24'), Carbon::parse('2026-11-30'));

    expect(($this->namesOn)('2026-11-27'))->not->toContain('Dexamethasone')
        ->and(($this->namesOn)('2026-12-04'))->toContain('Dexamethasone');
});

it('skips one-shot reminders that fall in the pause', function () {
    $this->cyclic->pause(Carbon::parse('2026-11-24'), Carbon::parse('2026-12-05'));
    $scheduled = captureNotifications();

    app(NotificationScheduler::class)->scheduleForTreatment($this->cyclic->fresh());

    expect(fireTimes($scheduled))->toBe(['2026-12-10 09:00']);
});

it('replaces daily repeating reminders with one-shots around a dated pause', function () {
    $this->daily->pause(Carbon::parse('2026-11-26'), Carbon::parse('2026-11-28'));
    $scheduled = captureNotifications();

    app(NotificationScheduler::class)->scheduleForTreatment($this->daily->fresh());

    $times = fireTimes($scheduled);
    $repeating = collect($scheduled->getArrayCopy())->filter(fn ($o) => isset($o['repeat']));

    expect($repeating)->toBeEmpty()
        // Aujourd'hui : 8h passé, reste 20h ; puis le 25 ; pause 26-28 ; reprise le 29.
        ->and(array_slice($times, 0, 4))->toBe(['2026-11-24 20:00', '2026-11-25 08:00', '2026-11-25 20:00', '2026-11-29 08:00'])
        ->and(collect($times)->filter(fn ($t) => $t >= '2026-11-26' && $t < '2026-11-29'))->toBeEmpty()
        // 14 jours de rappels ponctuels après la reprise
        ->and(end($times))->toBe('2026-12-13 20:00');

    $marker = json_decode(Setting::find('pause_notif_' . $this->daily->id)->value, true);
    expect($marker['through'])->toBe('2026-12-13');
});

it('schedules no daily reminder during an open-ended pause', function () {
    $this->daily->pause(Carbon::parse('2026-11-24'));
    $scheduled = captureNotifications();

    app(NotificationScheduler::class)->scheduleForTreatment($this->daily->fresh());

    expect(fireTimes($scheduled))->toBe([]);
});

it('switches back to repeating reminders once the pause is over', function () {
    $this->daily->pause(Carbon::parse('2026-11-24'), Carbon::parse('2026-11-25'));
    $scheduled = captureNotifications();
    app(NotificationScheduler::class)->scheduleForTreatment($this->daily->fresh());
    expect(Setting::find('pause_notif_' . $this->daily->id))->not->toBeNull();
    $before = count($scheduled);

    Carbon::setTestNow('2026-12-01 10:00:00');
    app(NotificationScheduler::class)->refreshPausedTreatments();

    $repeating = collect(array_slice($scheduled->getArrayCopy(), $before))->filter(fn ($o) => isset($o['repeat']));
    expect($repeating)->toHaveCount(2)
        ->and(Setting::find('pause_notif_' . $this->daily->id))->toBeNull();
});

it('pauses and resumes from the treatment page', function () {
    captureNotifications();

    Livewire::test(TreatmentEdit::class, ['treatment' => $this->daily])
        ->call('openPauseForm')
        ->assertSet('pauseFrom', '2026-11-24')
        ->set('pauseUntil', '2026-11-30')
        ->call('pauseTreatment')
        ->assertHasNoErrors()
        ->assertSee(__('treatments.pause_resume'));

    $t = $this->daily->fresh();
    expect($t->paused_from->toDateString())->toBe('2026-11-24')
        ->and($t->paused_until->toDateString())->toBe('2026-11-30')
        ->and($t->isPaused())->toBeTrue();

    Livewire::test(TreatmentEdit::class, ['treatment' => $t])
        ->call('resumeTreatment');

    expect($this->daily->fresh()->isPaused())->toBeFalse();
});

it('rejects a pause ending before it starts or starting too far ahead', function () {
    Livewire::test(TreatmentEdit::class, ['treatment' => $this->daily])
        ->call('openPauseForm')
        ->set('pauseFrom', '2026-11-28')
        ->set('pauseUntil', '2026-11-26')
        ->call('pauseTreatment')
        ->assertHasErrors(['pauseUntil'])
        ->set('pauseFrom', '2027-01-15')
        ->set('pauseUntil', '')
        ->call('pauseTreatment')
        ->assertHasErrors(['pauseFrom']);

    expect($this->daily->fresh()->paused_from)->toBeNull();
});

it('keeps the pause through export and import', function () {
    $this->daily->pause(Carbon::parse('2026-11-24'), Carbon::parse('2026-11-30'));

    $crypto = new \App\Services\CryptoService();
    $key    = $crypto->generateKey();
    $alys   = (new \App\Services\ExportService())->generateEncrypted($key);

    $this->daily->update(['paused_from' => null, 'paused_until' => null]);

    LocalNotifications::shouldReceive('cancelAll')->andReturn([]);
    captureNotifications();
    (new \App\Services\ImportService($crypto))->restore($alys, $key);

    $t = $this->daily->fresh();
    expect($t->paused_from?->toDateString())->toBe('2026-11-24')
        ->and($t->paused_until?->toDateString())->toBe('2026-11-30');
});
