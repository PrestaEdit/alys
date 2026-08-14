<?php

use App\Livewire\Calendar;
use App\Models\PersonalEvent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn() => $this->seed(DatabaseSeeder::class));

it('renders calendar component', function () {
    Livewire::test(Calendar::class)->assertStatus(200);
});

it('starts on current month', function () {
    $component = Livewire::test(Calendar::class);
    expect($component->get('month'))->toBe(now()->month);
    expect($component->get('year'))->toBe(now()->year);
});

it('can navigate to next month', function () {
    $component = Livewire::test(Calendar::class);
    $nextMonth = now()->addMonth()->month;
    $component->call('nextMonth');
    expect($component->get('month'))->toBe($nextMonth);
});

it('can navigate to previous month', function () {
    $component = Livewire::test(Calendar::class);
    $prevMonth = now()->subMonth()->month;
    $component->call('previousMonth');
    expect($component->get('month'))->toBe($prevMonth);
});

it('selecting a day loads day events', function () {
    $component = Livewire::test(Calendar::class);
    $component->call('selectDay', '2026-04-29'); // Hôpital visit day
    $events = $component->get('selectedDayEvents');
    $names = collect($events)->pluck('name')->toArray();
    expect($names)->toContain('Hôpital');
});

it('IT MTTX day events have requires_fasting true', function () {
    $component = Livewire::test(Calendar::class);
    $component->call('selectDay', '2026-07-08'); // IT MTTX day (2026-01-21 + 24 weeks)
    $events = collect($component->get('selectedDayEvents'));
    $itMttx = $events->firstWhere('name', 'IT MTTX');
    expect($itMttx)->not->toBeNull();
    expect($itMttx['requires_fasting'])->toBeTrue();
});

it('creates a personal event through the modal', function () {
    Livewire::test(Calendar::class)
        ->call('selectDay', '2026-04-10')
        ->call('openEventModal')
        ->assertSet('showEventModal', true)
        ->assertSet('eventStartDate', '2026-04-10')
        ->set('eventTitle', 'Vacances')
        ->set('eventEndDate', '2026-04-15')
        ->call('saveEvent')
        ->assertSet('showEventModal', false);

    expect(PersonalEvent::where('title', 'Vacances')->exists())->toBeTrue();
});

it('applies category defaults when selecting a category', function () {
    Livewire::test(Calendar::class)
        ->call('selectCategory', 'excursion')
        ->assertSet('eventCategory', 'excursion')
        ->assertSet('eventIcon', '🚌')
        ->assertSet('eventColor', '#10b981');
});

it('rejects an end date before the start date', function () {
    Livewire::test(Calendar::class)
        ->call('selectDay', '2026-04-10')
        ->call('openEventModal')
        ->set('eventTitle', 'Invalide')
        ->set('eventStartDate', '2026-04-10')
        ->set('eventEndDate', '2026-04-05')
        ->call('saveEvent')
        ->assertHasErrors(['eventEndDate']);

    expect(PersonalEvent::where('title', 'Invalide')->exists())->toBeFalse();
});

it('edits an existing personal event', function () {
    $event = PersonalEvent::create([
        'title' => 'Avant', 'category' => 'autre', 'color' => '#f59e0b', 'icon' => '📌',
        'start_date' => '2026-04-10', 'end_date' => '2026-04-10',
    ]);

    Livewire::test(Calendar::class)
        ->call('editEvent', $event->id)
        ->assertSet('eventTitle', 'Avant')
        ->set('eventTitle', 'Après')
        ->call('saveEvent');

    expect($event->fresh()->title)->toBe('Après');
});

it('deletes a personal event after confirmation', function () {
    $event = PersonalEvent::create([
        'title' => 'À supprimer', 'category' => 'autre', 'color' => '#f59e0b', 'icon' => '📌',
        'start_date' => '2026-04-10', 'end_date' => '2026-04-10',
    ]);

    Livewire::test(Calendar::class)
        ->call('openDeleteEventModal', $event->id)
        ->assertSet('showDeleteEventModal', true)
        ->assertSet('deletingEventId', $event->id)
        ->call('confirmDeleteEvent')
        ->assertSet('showDeleteEventModal', false)
        ->assertSet('deletingEventId', null);

    expect(PersonalEvent::find($event->id))->toBeNull();
});

it('cancelling the delete modal keeps the event', function () {
    $event = PersonalEvent::create([
        'title' => 'À garder', 'category' => 'autre', 'color' => '#f59e0b', 'icon' => '📌',
        'start_date' => '2026-04-10', 'end_date' => '2026-04-10',
    ]);

    Livewire::test(Calendar::class)
        ->call('openDeleteEventModal', $event->id)
        ->call('cancelDeleteEvent')
        ->assertSet('showDeleteEventModal', false)
        ->assertSet('deletingEventId', null);

    expect(PersonalEvent::find($event->id))->not->toBeNull();
});
