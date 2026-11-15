<?php

use App\Livewire\TreatmentEdit;
use App\Models\CalendarEvent;
use App\Models\Treatment;
use App\Services\LinkedEventSync;
use App\Services\NotificationScheduler;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Ikromjon\LocalNotifications\Facades\LocalNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Carbon::setTestNow('2026-11-05 10:00:00');

    $this->parent = Treatment::create([
        'name' => 'Vincristine', 'type' => 'cyclic', 'color' => '#f97316',
        'frequency_weeks' => 4, 'recurrence_start' => '2026-11-05',
    ]);
    $this->child = Treatment::create([
        'name' => 'Dexamethasone', 'type' => 'daily', 'color' => '#3b82f6',
        'parent_treatment_id' => $this->parent->id, 'linked_days' => 5,
        'order_reminder_enabled' => true, 'order_reminder_days_before' => 3,
        'order_reminder_time' => '09:00',
    ]);

    // Bloc déjà commencé aujourd'hui (pas de rappel) + deux blocs à venir.
    foreach (['2026-11-05', '2026-12-03', '2026-12-31'] as $date) {
        CalendarEvent::create(['treatment_id' => $this->parent->id, 'scheduled_date' => $date]);
    }
    app(LinkedEventSync::class)->syncChild($this->child);

    $this->scheduler = app(NotificationScheduler::class);
});

afterEach(fn () => Carbon::setTestNow());

it('uses the first day of each upcoming linked block', function () {
    $starts = collect($this->scheduler->upcomingBlockStarts($this->child->fresh()))
        ->map(fn ($d) => $d->toDateString())->values()->all();

    expect($starts)->toBe(['2026-11-05', '2026-12-03', '2026-12-31']);
});

it('schedules an order reminder N days before each future block', function () {
    $scheduled = [];
    LocalNotifications::shouldReceive('cancel')->andReturn([]);
    LocalNotifications::shouldReceive('schedule')->andReturnUsing(function ($opts) use (&$scheduled) {
        $scheduled[] = $opts;
        return [];
    });

    $this->scheduler->scheduleForTreatment($this->child->fresh());

    $times = collect($scheduled)
        ->filter(fn ($o) => str_contains($o['id'], '-order-'))
        ->map(fn ($o) => Carbon::createFromTimestamp($o['at'])->format('Y-m-d H:i'))
        ->values()->all();

    // Le bloc du 5 nov. est déjà commencé (rappel le 2 nov. passé) : ignoré.
    expect($times)->toBe(['2026-11-30 09:00', '2026-12-28 09:00']);
    expect($scheduled[0]['body'])->toContain('Dexamethasone');
});

it('schedules nothing when the order reminder is disabled', function () {
    $this->child->update(['order_reminder_enabled' => false]);
    LocalNotifications::shouldReceive('cancel')->andReturn([]);
    LocalNotifications::shouldReceive('schedule')->never();

    $this->scheduler->scheduleForTreatment($this->child->fresh());
});

it('has no block for a standalone daily treatment', function () {
    $daily = Treatment::create(['name' => 'Purinéthol', 'type' => 'daily', 'color' => '#6366f1']);

    expect($this->scheduler->upcomingBlockStarts($daily))->toBe([]);
});

it('saves the order reminder from the treatment page, 3 days by default', function () {
    $other = Treatment::create([
        'name' => 'Aérosol', 'type' => 'cyclic', 'color' => '#ec4899',
        'frequency_weeks' => 4, 'recurrence_start' => '2026-11-05',
    ]);

    Livewire::test(TreatmentEdit::class, ['treatment' => $other])
        ->assertSet('orderReminderDaysBefore', 3)
        ->assertSee(__('treatments.order_reminder'))
        ->set('orderReminderEnabled', true)
        ->call('incrementOrderReminderDays')
        ->set('orderReminderTime', '18:30')
        ->call('saveNotification')
        ->assertHasNoErrors();

    $other->refresh();
    expect($other->order_reminder_enabled)->toBeTrue()
        ->and($other->order_reminder_days_before)->toBe(4)
        ->and($other->order_reminder_time)->toBe('18:30');
});

it('enables the order reminder from the creation wizard for a linked treatment', function () {
    Livewire::test(\App\Livewire\TreatmentCreate::class)
        ->set('name', 'Zophren')
        ->set('color', '#3b82f6')
        ->set('parentTreatmentId', $this->parent->id)
        ->set('linkedDays', 2)
        ->set('isMedicalAct', true)
        ->assertSet('orderReminderDaysBefore', 3)
        ->set('step', 6)
        ->assertSee(__('treatments.order_reminder'))
        ->set('orderReminderEnabled', true)
        ->call('save');

    $t = Treatment::where('name', 'Zophren')->first();
    expect($t->order_reminder_enabled)->toBeTrue()
        ->and($t->order_reminder_days_before)->toBe(3)
        ->and($t->order_reminder_time)->toBe('09:00');
});

it('does not offer the order reminder at creation for a standalone daily treatment', function () {
    Livewire::test(\App\Livewire\TreatmentCreate::class)
        ->set('type', 'daily')
        ->set('step', 6)
        ->assertDontSee(__('treatments.order_reminder'));
});

it('keeps reminder settings through export and import', function () {
    $this->child->update([
        'order_reminder_days_before' => 5, 'order_reminder_time' => '18:00',
        'notification_enabled' => true, 'notification_time_morning' => '08:15',
    ]);

    $crypto = new \App\Services\CryptoService();
    $key    = $crypto->generateKey();
    $alys   = (new \App\Services\ExportService())->generateEncrypted($key);

    $this->child->update([
        'order_reminder_enabled' => false, 'order_reminder_days_before' => 3,
        'order_reminder_time' => null, 'notification_enabled' => false,
        'notification_time_morning' => null,
    ]);

    (new \App\Services\ImportService($crypto))->restore($alys, $key);

    $this->child->refresh();
    expect($this->child->order_reminder_enabled)->toBeTrue()
        ->and($this->child->order_reminder_days_before)->toBe(5)
        ->and($this->child->order_reminder_time)->toBe('18:00')
        ->and($this->child->notification_enabled)->toBeTrue()
        ->and($this->child->notification_time_morning)->toBe('08:15');
});
